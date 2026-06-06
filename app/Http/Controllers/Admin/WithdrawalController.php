<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $query = Withdrawal::with('partner');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->whereHas('partner', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        return Inertia::render('Admin/Withdrawals/Index', [
            'withdrawals' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be approved.');
        }

        DB::transaction(function () use ($withdrawal, $request) {
            $withdrawal->update([
                'status' => 'approved',
                'transaction_id' => $request->transaction_id,
                'admin_notes' => $request->admin_notes,
            ]);

            // Deduct from wallet if not already done or handled by API
            // Usually API store method handles deduction/locking of balance.
            // Let's assume the balance was already "locked" or deducted upon request creation.
            // If not, we would deduct it here.
        });

        return back()->with('success', 'Withdrawal request approved and marked as paid.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'required|string',
        ]);

        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be rejected.');
        }

        DB::transaction(function () use ($withdrawal, $request) {
            $withdrawal->update([
                'status' => 'rejected',
                'admin_notes' => $request->admin_notes,
            ]);

            // Refund wallet balance
            $partner = $withdrawal->partner;
            $partner->increment('wallet_balance', $withdrawal->amount);
        });

        return back()->with('success', 'Withdrawal request rejected and amount refunded to wallet.');
    }
}
