<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('properties->module', 'like', '%' . $request->search . '%')
                  ->orWhere('properties->action', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->log_name) {
            $query->where('log_name', $request->log_name);
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'log_name']),
            'logNames' => Activity::select('log_name')->distinct()->pluck('log_name'),
        ]);
    }
}
