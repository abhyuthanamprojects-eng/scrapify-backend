<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Display a listing of pickups that are at the warehouse and their payment status.
     */
    public function index(Request $request)
    {
        $query = PickupRequest::with(['customer', 'pickupBoy'])
            ->whereIn('status', ['delivered_to_warehouse', 'completed'])
            ->orderByRaw("FIELD(status, 'delivered_to_warehouse', 'completed')")
            ->orderBy('warehouse_received_at', 'desc')
            ->orderBy('payment_completed_at', 'desc');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status']),
        ]);
    }
}
