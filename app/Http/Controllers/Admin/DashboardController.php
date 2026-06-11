<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\PickupRequest;
use App\Models\Warehouse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $period = strtolower((string) $request->query('period', 'month'));

        if ($user->hasAnyRole(['admin', 'payment_admin'])) {
            $stats = [
                'users_count' => User::count(),
                'total_customers' => User::role('customer')->count() + \App\Models\ChannelPartnerCustomer::count(),
                'total_pickups' => PickupRequest::count(),
                'pending_requests' => PickupRequest::where('status', 'pending')->count(),
                'assigned_pickups' => PickupRequest::where('status', 'assigned')->count(),
                'completed_requests' => PickupRequest::where('status', 'completed')->count(),
                'delivered_to_warehouse' => PickupRequest::where('status', 'delivered_to_warehouse')->count(),
                'pending_settlement' => \App\Models\Settlement::whereIn('payout_status', ['pending', 'processing', 'hold'])->sum('net_amount'),
                'paid_settlement' => \App\Models\Settlement::where('payout_status', 'paid')->sum('net_amount'),
                'warehouses_count' => Warehouse::count(),
                'recent_pickups' => PickupRequest::with('customer')->latest()->take(10)->get(),
                'partner_requests_count' => \App\Models\ChannelPartner::where('registration_status', 'pending')->count(),
                'pending_approvals_count' => \App\Models\ApprovalRequest::where('status', 'pending')->count(),
                'today_pickups_count' => PickupRequest::whereDate('created_at', now()->toDateString())->count(),
                'online_drivers_count' => User::role('pickup_boy')->online()->count(),
                'capacity_full_drivers_count' => User::role('pickup_boy')->get()->filter->is_capacity_full->count(),
            ];
        } else {
            // Union Logic for data scoping
            $pickupQuery = PickupRequest::query();
            $pickupQuery->where(function ($q) use ($user) {
                $hasScope = false;
                if ($user->hasRole('warehouse')) {
                    $warehouseIds = $user->getAccessibleWarehouseIds();
                    $q->orWhereIn('warehouse_id', $warehouseIds);
                    $hasScope = true;
                }
                if ($user->hasRole('channel_partner')) {
                    $q->orWhere('channel_partner_id', $user->channel_partner_id);
                    $hasScope = true;
                }
                if ($user->hasRole('pickup_boy')) {
                    $q->orWhereHas('assignment', function ($sub) use ($user) {
                        $sub->where('pickup_boy_id', $user->id);
                    });
                    $hasScope = true;
                }
                if (!$hasScope) {
                    $q->whereRaw('1=0');
                }
            });

            if ($user->hasRole('pickup_boy')) {
                if ($period === 'today') {
                    $pickupQuery->whereDate('created_at', now()->toDateString());
                } elseif ($period === 'month') {
                    $pickupQuery->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ]);
                }
            }

            $stats = [
                'users_count' => null,
                'total_customers' => $user->hasRole('channel_partner') ? \App\Models\ChannelPartnerCustomer::where('channel_partner_id', $user->channel_partner_id)->count() : null,
                'total_pickups' => (clone $pickupQuery)->count(),
                'pending_requests' => (clone $pickupQuery)->where('status', 'pending')->count(),
                'assigned_pickups' => (clone $pickupQuery)->where('status', 'assigned')->count(),
                'completed_requests' => (clone $pickupQuery)->where('status', 'completed')->count(),
                'delivered_to_warehouse' => (clone $pickupQuery)->where('status', 'delivered_to_warehouse')->count(),
                'pending_settlement' => $user->hasRole('channel_partner') ? \App\Models\Settlement::where('partner_id', $user->id)->whereIn('payout_status', ['pending', 'processing', 'hold'])->sum('net_amount') : 0,
                'paid_settlement' => $user->hasRole('channel_partner') ? \App\Models\Settlement::where('partner_id', $user->id)->where('payout_status', 'paid')->sum('net_amount') : 0,
                'warehouses_count' => $user->hasRole('warehouse') ? count($user->getAccessibleWarehouseIds()) : null,
                'recent_pickups' => $pickupQuery->with('customer')->latest()->take(10)->get(),
                'partner_requests_count' => 0,
                'pending_approvals_count' => 0,
                'today_pickups_count' => (clone $pickupQuery)->whereDate('created_at', now()->toDateString())->count(),
                'online_drivers_count' => 0, // Scope this later if needed
                'capacity_full_drivers_count' => 0,
                'period' => $user->hasRole('pickup_boy') ? $period : null,
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'selectedPeriod' => $user->hasRole('pickup_boy') ? $period : null,
            'isPickupBoy' => $user->hasRole('pickup_boy'),
        ]);
    }
}
