<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KycController extends Controller
{
    public function index(Request $request)
    {
        $query = KycDocument::with('user');

        if ($request->status) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $kycDocuments = $query->latest()->paginate(20);

        return Inertia::render('Admin/Kyc/Index', [
            'kycDocuments' => $kycDocuments,
            'filters' => $request->only('status')
        ]);
    }

    public function verify(Request $request, KycDocument $kyc)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string',
        ]);

        $kyc->update([
            'status' => $validated['status'],
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'remarks' => $validated['remarks'],
        ]);

        return redirect()->back()
            ->with('success', 'KYC document ' . $validated['status'] . ' successfully.');
    }
}
