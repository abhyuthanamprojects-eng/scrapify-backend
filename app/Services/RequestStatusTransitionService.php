<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Enums\UserRole;
use App\Models\PickupRequest;
use App\Models\RequestStatusLog;
use Illuminate\Support\Facades\Auth;

class RequestStatusTransitionService
{
    /**
     * Transition request to new status
     */
    public static function transition(
        PickupRequest $request,
        RequestStatus $newStatus,
        $userId = null,
        $role = null,
        $notes = null,
        $metadata = null
    ): bool {
        $userId = $userId ?? Auth::id();
        $role = $role ?? Auth::user()?->getRole();

        // Validate transition
        if (!self::isTransitionAllowed($request, $newStatus, $role)) {
            throw new \Exception("Transition from {$request->status} to {$newStatus->value} is not allowed");
        }

        $oldStatus = $request->status;

        // Update request status
        $request->update([
            'status_new' => $newStatus->value,
        ]);

        // Update timeline fields based on new status
        self::updateTimelineFields($request, $newStatus);

        // Log the change
        RequestStatusLog::logStatusChange(
            $request->id,
            $oldStatus,
            $newStatus->value,
            $userId,
            $role,
            $notes,
            $metadata
        );

        return true;
    }

    /**
     * Check if transition is allowed
     */
    public static function isTransitionAllowed(
        PickupRequest $request,
        RequestStatus $newStatus,
        $role = null
    ): bool {
        $currentStatus = RequestStatus::tryFrom($request->status_new ?? $request->status);
        $requestType = RequestType::tryFrom($request->request_type);

        if (!$currentStatus || !$requestType) {
            return false;
        }

        // Get allowed next statuses
        $allowedNextStatuses = RequestStatus::getNextAllowedStatuses($currentStatus, $requestType);

        // Check if the new status is in allowed list
        if (!in_array($newStatus, $allowedNextStatuses)) {
            return false;
        }

        // Role-based validation
        return self::validateRolePermission($currentStatus, $newStatus, $role, $requestType);
    }

    /**
     * Validate role-based permissions for status transition
     */
    private static function validateRolePermission(
        RequestStatus $currentStatus,
        RequestStatus $newStatus,
        $role,
        RequestType $requestType
    ): bool {
        $role = UserRole::tryFrom($role);

        // Admin can do anything
        if ($role === UserRole::ADMIN) {
            return true;
        }

        // Check specific role permissions
        return match ($newStatus) {
            // Warehouse operations
            RequestStatus::PICKUP_BOY_ASSIGNED => in_array($role, [UserRole::WAREHOUSE]),
            RequestStatus::WAREHOUSE_RECEIVED => in_array($role, [UserRole::WAREHOUSE]),
            RequestStatus::PAYMENT_PENDING => in_array($role, [UserRole::WAREHOUSE]),

            // Pickup boy operations
            RequestStatus::PICKUP_STARTED => in_array($role, [UserRole::PICKUP_BOY]),
            RequestStatus::PICKUP_COMPLETED => in_array($role, [UserRole::PICKUP_BOY]),

            // Payment operations (admin & payment_admin)
            RequestStatus::PAYMENT_PROCESSING,
            RequestStatus::PAYMENT_COMPLETED => in_array($role, [UserRole::ADMIN, UserRole::PAYMENT_ADMIN]),

            // Completion
            RequestStatus::COMPLETED => in_array($role, [UserRole::ADMIN, UserRole::WAREHOUSE]),

            // Cancellation
            RequestStatus::CANCELLED => true, // Multiple roles can cancel

            // Estimate operations (corporate only)
            RequestStatus::ESTIMATE_PENDING => in_array($role, [UserRole::ADMIN, UserRole::WAREHOUSE, UserRole::CUSTOMER]),
            RequestStatus::ESTIMATE_SHARED => in_array($role, [UserRole::ADMIN, UserRole::WAREHOUSE]),
            RequestStatus::ESTIMATE_APPROVED,
            RequestStatus::ESTIMATE_REJECTED => in_array($role, [UserRole::ADMIN, UserRole::WAREHOUSE, UserRole::CUSTOMER]),

            // Default - only admin
            default => $role === UserRole::ADMIN,
        };
    }

    /**
     * Update timeline fields based on status change
     */
    private static function updateTimelineFields(PickupRequest $request, RequestStatus $newStatus): void
    {
        $updates = [];

        match ($newStatus) {
            RequestStatus::PICKUP_STARTED => $updates['pickup_started_at'] = now(),
            RequestStatus::PICKUP_COMPLETED => $updates['pickup_completed_at'] = now(),
            RequestStatus::WAREHOUSE_RECEIVED => $updates['warehouse_received_at'] = now(),
            RequestStatus::PAYMENT_PENDING => $updates['payment_pending_at'] = now(),
            RequestStatus::PAYMENT_COMPLETED => $updates['payment_completed_at'] = now(),
            RequestStatus::COMPLETED => $updates['completed_at'] = now(),
            default => null,
        };

        if (!empty($updates)) {
            $request->update($updates);
        }
    }

    /**
     * Get next allowed actions for a request based on user role
     */
    public static function getNextAllowedActions(PickupRequest $request, $role = null): array
    {
        $role = UserRole::tryFrom($role ?? Auth::user()?->getRole());
        $currentStatus = RequestStatus::tryFrom($request->status_new ?? $request->status);
        $requestType = RequestType::tryFrom($request->request_type);

        if (!$currentStatus || !$requestType) {
            return [];
        }

        $allowedStatuses = RequestStatus::getNextAllowedStatuses($currentStatus, $requestType);
        $actions = [];

        foreach ($allowedStatuses as $status) {
            // Check if user role can perform this transition
            if (self::validateRolePermission($currentStatus, $status, $role, $requestType)) {
                $actions[] = [
                    'status' => $status->value,
                    'label' => $status->label(),
                    'endpoint' => self::getEndpointForStatus($status),
                ];
            }
        }

        // Add cancellation if allowed
        if ($currentStatus->isModifiable() && $role !== UserRole::PICKUP_BOY) {
            $actions[] = [
                'status' => RequestStatus::CANCELLED->value,
                'label' => 'Cancel Request',
                'endpoint' => "/api/requests/{$request->id}/cancel",
            ];
        }

        return $actions;
    }

    /**
     * Get API endpoint for status transition
     */
    private static function getEndpointForStatus(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::PICKUP_BOY_ASSIGNED => '/api/requests/{id}/assign-pickup-boy',
            RequestStatus::PICKUP_STARTED => '/api/requests/{id}/start-pickup',
            RequestStatus::PICKUP_COMPLETED => '/api/requests/{id}/complete-pickup',
            RequestStatus::WAREHOUSE_RECEIVED => '/api/requests/{id}/confirm-received',
            RequestStatus::PAYMENT_PENDING => '/api/requests/{id}/move-to-payment',
            RequestStatus::PAYMENT_PROCESSING => '/api/requests/{id}/process-payment',
            RequestStatus::PAYMENT_COMPLETED => '/api/requests/{id}/complete-payment',
            RequestStatus::COMPLETED => '/api/requests/{id}/complete',
            RequestStatus::CANCELLED => '/api/requests/{id}/cancel',
            default => '/api/requests/{id}/status',
        };
    }

    /**
     * Get status history for a request
     */
    public static function getStatusHistory(PickupRequest $request): array
    {
        return RequestStatusLog::getHistoryForRequest($request->id)
            ->map(fn($log) => [
                'old_status' => $log->old_status,
                'new_status' => $log->new_status,
                'new_status_label' => RequestStatus::tryFrom($log->new_status)?->label(),
                'changed_by' => $log->changedBy->name ?? 'System',
                'changed_by_role' => $log->changed_by_role,
                'notes' => $log->notes,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }

    /**
     * Validate if request can be cancelled
     */
    public static function canBeCancelled(PickupRequest $request): bool
    {
        $status = RequestStatus::tryFrom($request->status_new ?? $request->status);
        return $status && $status->isModifiable();
    }

    /**
     * Validate if request requires estimate (corporate only)
     */
    public static function requiresEstimate(PickupRequest $request): bool
    {
        $type = RequestType::tryFrom($request->request_type);
        return $type && $type->requiresEstimate();
    }

    /**
     * Validate if estimate is approved before pickup assignment (corporate only)
     */
    public static function isEstimateApprovedForPickup(PickupRequest $request): bool
    {
        if ($request->request_type !== 'corporate') {
            return true; // Not applicable for non-corporate requests
        }

        $estimate = $request->corporateEstimate;
        return $estimate && $estimate->isApproved();
    }
}
