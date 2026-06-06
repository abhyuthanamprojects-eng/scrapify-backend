<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get user notifications.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->when($request->has('unread_only') && $request->unread_only, function ($query) {
                $query->whereNull('read_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginatedResponse('notifications.fetched', $notifications);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount()
    {
        $count = Auth::user()->unreadNotifications()->count();
        return $this->successResponse('notifications.unread_count', ['count' => $count]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return $this->errorResponse('notification.not_found', 404);
        }

        $notification->markAsRead();

        return $this->successResponse('notification.marked_as_read');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return $this->successResponse('notifications.all_marked_as_read');
    }

    /**
     * Update FCM token.
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = Auth::user();
        $user->update(['fcm_token' => $request->fcm_token]);

        return $this->successResponse('fcm_token.updated');
    }

    /**
     * Delete notification.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return $this->errorResponse('notification.not_found', 404);
        }

        $notification->delete();

        return $this->successResponse('notification.deleted');
    }
}
