<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettlementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Settlement::with(['partner', 'pickupRequest']);

        if (!$user->hasRole('admin') && $user->hasRole('channel_partner')) {
            $query->where('partner_id', $user->id);
        }

        if ($request->status) {
            $query->where(fn($q) => $q->where('status', $request->status)->orWhere('payout_status', $request->status));
        }

        if ($request->partner_id) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $settlements = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Settlements/Index', [
            'settlements' => $settlements,
            'filters' => $request->only(['status', 'partner_id', 'from_date', 'to_date']),
            'partners' => $user->hasRole('admin') ? \App\Models\User::role('channel_partner')->get(['id', 'name']) : [],
        ]);
    }

    public function show(Settlement $settlement)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && $user->hasRole('channel_partner') && $settlement->partner_id !== $user->id) {
            abort(403, 'Unauthorized access to this settlement.');
        }

        $settlement->load(['partner', 'pickupRequest.items.category', 'pickupRequest.statusLogs']);

        return Inertia::render('Admin/Settlements/Show', [
            'settlement' => $settlement
        ]);
    }

    public function approve(Settlement $settlement)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $settlement->update(['status' => 'approved', 'payout_status' => 'processing']);

        return redirect()->back()
            ->with('success', 'Settlement approved successfully.');
    }

    public function updatePayoutStatus(Request $request, Settlement $settlement)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'payout_status' => 'required|in:pending,processing,paid,hold,rejected',
            'notes' => 'nullable|string',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $updateData = [
            'payout_status' => $validated['payout_status'],
            'notes' => $validated['notes'] ?? $settlement->notes,
        ];

        if ($validated['payout_status'] === 'paid') {
            $updateData['status'] = 'paid';
            $updateData['payout_date'] = now()->toDateString();
        }

        if ($request->hasFile('payment_proof')) {
            $updateData['payment_proof'] = $request->file('payment_proof')->store('settlement_proofs', 'public');
        }

        $settlement->update($updateData);

        return redirect()->back()->with('success', 'Payout status updated successfully.');
    }

    public function markAsPaid(Request $request, Settlement $settlement)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'payment_type' => 'required|in:bank_transfer,upi,cash',
            'notes' => 'nullable|string',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $updateData = [
            'status' => 'paid',
            'payout_status' => 'paid',
            'payment_id' => $validated['transaction_id'],
            'payout_date' => now()->toDateString(),
            'notes' => $validated['notes'],
        ];

        if ($request->hasFile('payment_proof')) {
            $updateData['payment_proof'] = $request->file('payment_proof')->store('settlement_proofs', 'public');
        }

        $settlement->update($updateData);

        return redirect()->back()
            ->with('success', 'Settlement marked as paid successfully.');
    }

    public function reject(Request $request, Settlement $settlement)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $settlement->update([
            'status' => 'rejected',
            'payout_status' => 'rejected',
            'notes' => $validated['notes'],
        ]);

        return redirect()->back()
            ->with('success', 'Settlement rejected.');
    }
}

