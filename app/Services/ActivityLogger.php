<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ActivityLogger
{
    /**
     * Log an activity.
     *
     * @param string $action The action performed (e.g., 'login', 'create', 'update')
     * @param string $module The module affected (e.g., 'auth', 'pickup', 'payment')
     * @param string $description Human-readable description
     * @param array|null $payload Request payload or relevant data
     * @param mixed $causer Optional user performing the action (defaults to Auth::user())
     * @return Activity|null
     */
    public static function log(string $action, string $module, string $description, ?array $payload = null, $causer = null)
    {
        $user = $causer ?: Auth::user();

        $properties = [
            'action' => $action,
            'module' => $module,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'role' => $user ? $user->getRoleNames()->first() : 'guest',
            'request_payload' => $payload,
        ];

        return activity()
            ->causedBy($user)
            ->withProperties($properties)
            ->event($action) // Standard Spatie event column
            ->log($description);
    }
}
