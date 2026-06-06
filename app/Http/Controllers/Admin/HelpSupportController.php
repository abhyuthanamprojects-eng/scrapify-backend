<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HelpSupportController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::whereIn('type', ['general', 'order'])
            ->with(['user:id,name,email,phone', 'pickupRequest:id,request_type,status,scheduled_at']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('subject', 'like', '%' . $request->search . '%')
                    ->orWhere('pickup_request_id', $request->search);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/HelpSupport/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'status', 'type']),
        ]);
    }

    public function show($id)
    {
        $ticket = ContactMessage::with(['user:id,name,email,phone', 'pickupRequest'])->findOrFail($id);

        return Inertia::render('Admin/HelpSupport/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
        ]);

        $ticket = ContactMessage::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        return back()->with('success', 'Ticket status updated successfully.');
    }

    public function destroy($id)
    {
        $ticket = ContactMessage::findOrFail($id);
        $ticket->delete();

        return redirect()->route('admin.help-support.index')->with('success', 'Ticket deleted successfully.');
    }
}
