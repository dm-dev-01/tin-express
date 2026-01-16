<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications
     */
    public function index()
    {
        // Return top 10 unread notifications
        return response()->json(
            Auth::user()->unreadNotifications()->latest()->take(10)->get()
        );
    }

    /**
     * Mark all as read
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'All marked as read']);
    }

    /**
     * Mark specific notification as read
     */
    public function markRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['message' => 'Marked as read']);
    }
}