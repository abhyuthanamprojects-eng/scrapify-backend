<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AppSetting;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class PickupAssignmentService
{
    /**
     * Assign a pickup boy to a pickup. Validates pickup boy is mapped to pickup's warehouse.
     *
     * @param bool $overrideCapacity Whether to skip capacity/online checks
     * @return array{ok:bool, message:string, assignment?:Assignment}
     */
    public function assign(PickupRequest $pickup, User $pickupBoy, User $assignedBy, string $assignedByType, bool $overrideCapacity = false): array
    {
        if (in_array($pickup->status, ['completed', 'cancelled', 'pickup_completed'])) {
            return ['ok' => false, 'message' => 'pickup.cannot_assign'];
        }

        // Corporate workflow gate:
        // Do not allow pickup-boy assignment until admin/warehouse sets quote.
        if ($pickup->request_type === 'corporate') {
            $meta = is_array($pickup->metadata) ? $pickup->metadata : [];
            $hasQuote = !is_null($pickup->estimated_amount) || !empty($meta['quoted_at']);
            if (!$hasQuote) {
                return ['ok' => false, 'message' => 'corporate.quote_required_before_assignment'];
            }
        }

        // If today's slot has already been missed after booking hours and still unassigned,
        // move it to tomorrow automatically before assignment.
        $this->autoRescheduleMissedTodayPickup($pickup, $assignedBy->id);

        if (!$pickupBoy->hasRole('pickup_boy')) {
            return ['ok' => false, 'message' => 'pickup_boy.invalid'];
        }

        $isFuturePickup = $this->isFuturePickup($pickup);
        $enforceRealtimeChecks = !$overrideCapacity && !$isFuturePickup;

        // Capacity and Online checks (unless overridden)
        if ($enforceRealtimeChecks) {
            if (!$pickupBoy->is_online) {
                return ['ok' => false, 'message' => 'pickup_boy.offline_outside_hours'];
            }
            if ($pickupBoy->is_manual_offline) {
                return ['ok' => false, 'message' => 'pickup_boy.manually_offline'];
            }
            if ($pickupBoy->is_capacity_full) {
                return ['ok' => false, 'message' => 'pickup_boy.capacity_full'];
            }
        }

        if ($assignedByType === 'channel_partner') {
            if (!$pickup->channel_partner_id || $pickupBoy->channel_partner_id !== $pickup->channel_partner_id) {
                return ['ok' => false, 'message' => 'pickup_boy.not_linked_to_partner'];
            }
        }

        $warehouseId = $pickup->warehouse_id;
        if ($warehouseId && $assignedByType !== 'channel_partner') {
            $mapped = $pickupBoy->warehouses()
                ->wherePivot('status', 'active')
                ->where('warehouses.id', $warehouseId)
                ->exists();

            // Legacy fallback: users.warehouse_id matches
            if (!$mapped && $pickupBoy->warehouse_id != $warehouseId) {
                return ['ok' => false, 'message' => 'pickup_boy.not_mapped_to_warehouse'];
            }
        }

        return DB::transaction(function () use ($pickup, $pickupBoy, $assignedBy, $assignedByType, $warehouseId) {
            // Cancel any active prior assignment for this pickup
            Assignment::where('pickup_request_id', $pickup->id)
                ->whereNotIn('status', ['completed', 'pickup_completed', 'cancelled', 'reassigned'])
                ->update(['status' => 'reassigned']);

            $assignment = Assignment::create([
                'pickup_request_id' => $pickup->id,
                'pickup_boy_id'     => $pickupBoy->id,
                'warehouse_id'      => $warehouseId,
                'status'            => 'assigned',
                'assigned_by'       => $assignedBy->id,
                'assigned_by_type'  => $assignedByType,
                'assigned_at'       => now(),
            ]);

            $pickup->update(['status' => 'assigned']);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'assigned',
                'notes' => 'Assigned to ' . $pickupBoy->name,
                'created_by' => $assignedBy->id,
            ]);

            return ['ok' => true, 'message' => 'pickup.assigned', 'assignment' => $assignment];
        });
    }

    public function autoRescheduleMissedTodayPickup(PickupRequest $pickup, ?int $actorUserId = null): bool
    {
        if (!$pickup->scheduled_at) {
            return false;
        }

        $tz = 'Asia/Kolkata';
        $now = now($tz);
        $scheduled = $pickup->scheduled_at->copy()->timezone($tz);

        if (!$scheduled->isToday()) {
            return false;
        }

        $cutoffHour = (int) AppSetting::get('pickup_booking_end_hour', 19);
        $cutoffMinute = (int) AppSetting::get('pickup_booking_end_minute', 0);
        $cutoff = $now->copy()->setTime($cutoffHour, $cutoffMinute);

        if ($now->lt($cutoff) || $scheduled->gt($now)) {
            return false;
        }

        $hasActiveAssignment = $pickup->assignments()
            ->whereNotIn('status', ['completed', 'pickup_completed', 'cancelled', 'reassigned', 'rejected'])
            ->exists();
        if ($hasActiveAssignment) {
            return false;
        }

        $newScheduledAt = $scheduled->copy()->addDay();
        $currentMetadata = $pickup->metadata ?? [];
        $currentMetadata['auto_reschedule'] = [
            'enabled' => true,
            'rescheduled_at' => $now->format('Y-m-d H:i:s'),
            'reason' => 'Missed today booking after cutoff; auto moved to next day.',
            'old_scheduled_at' => $scheduled->format('Y-m-d H:i:s'),
            'new_scheduled_at' => $newScheduledAt->format('Y-m-d H:i:s'),
        ];

        $pickup->update([
            'scheduled_at' => $newScheduledAt->timezone(config('app.timezone')),
            'status' => 'rescheduled',
            'metadata' => $currentMetadata,
        ]);

        \App\Models\PickupStatusLog::create([
            'pickup_request_id' => $pickup->id,
            'status' => 'rescheduled',
            'notes' => 'Auto-rescheduled to next day because booking was unassigned after cutoff hours.',
            'created_by' => $actorUserId,
        ]);

        return true;
    }

    protected function isFuturePickup(PickupRequest $pickup): bool
    {
        if (!$pickup->scheduled_at) {
            return false;
        }

        $pickupDate = $pickup->scheduled_at->copy()->timezone('Asia/Kolkata')->toDateString();
        $today = now('Asia/Kolkata')->toDateString();
        return $pickupDate > $today;
    }

    public function updateStatus(Assignment $assignment, string $status, ?string $remarks = null): Assignment
    {
        $assignment->update([
            'status'  => $status,
            'remarks' => $remarks ?? $assignment->remarks,
            'completed_at' => in_array($status, ['pickup_completed', 'completed']) ? now() : $assignment->completed_at,
        ]);
        $assignment->pickupRequest?->update(['status' => $status]);
        if ($assignment->pickupRequest) {
            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $assignment->pickup_request_id,
                'status' => $status,
                'notes' => $remarks ?? 'Status updated.',
                'created_by' => auth()->id(),
            ]);
        }
        return $assignment;
    }
}
