<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class SupportController extends Controller
{
    // --- LIST TICKETS (FIXED SORTING) ---
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = SupportTicket::with(['lastMessage', 'user', 'shipment']);

        // Admin sees ALL, User sees Company's
        if ($user->role === 'super_admin') {
            if ($request->status) $query->where('status', $request->status);
        } else {
            $query->where('company_id', $user->company_id);
        }

        // FIX: Sort by 'last_reply_at' so new messages bump ticket to top
        return response()->json($query->orderBy('last_reply_at', 'desc')->paginate(20));
    }

    // --- CREATE TICKET ---
    public function store(Request $request)
    {
        // ... validation ...
        $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,critical',
            'message' => 'required|string',
            'shipment_id' => 'nullable|exists:shipments,id',
            'file' => 'nullable|file|max:5120'
        ]);

        $ticket = SupportTicket::create([
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id,
            'shipment_id' => $request->shipment_id,
            'subject' => $request->subject,
            'priority' => $request->priority,
            'status' => 'open',
            'last_reply_at' => now(), // Initialize timestamp
        ]);

        $this->createMessage($ticket->id, $request->message, $request->file('file'));

        // Notify Admins about new ticket
        $admins = User::where('role', 'super_admin')->get();
        Notification::send($admins, new SupportTicketUpdated($ticket, Auth::user(), $request->message));

        return response()->json(['message' => 'Ticket created', 'ticket' => $ticket]);
    }

    // --- SHOW TICKET ---
    public function show($id)
    {
        $user = Auth::user();
        $ticket = SupportTicket::with(['messages.user', 'shipment'])->findOrFail($id);

        if ($user->role !== 'super_admin' && $ticket->company_id !== $user->company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($ticket);
    }

    // --- REPLY TO TICKET (NOTIFICATIONS ADDED) ---
    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'file' => 'nullable|file|max:5120'
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $user = Auth::user();
        
        // 1. Determine Status & Recipient
        $recipient = null;

        if ($user->role === 'super_admin') {
            $ticket->update(['status' => 'in_progress']);
            $recipient = $ticket->user; // Notify the Customer
        } else {
            $ticket->update(['status' => 'open']);
            $recipient = User::where('role', 'super_admin')->get(); // Notify Support Team
        }

        // 2. Bump the timestamp
        $ticket->update(['last_reply_at' => now()]);

        // 3. Save Message
        $this->createMessage($id, $request->message, $request->file('file'));

        // 4. Send Notification
        if ($recipient) {
            Notification::send($recipient, new SupportTicketUpdated($ticket, $user, $request->message));
        }

        return response()->json(['message' => 'Reply sent']);
    }

    // --- HELPER (Keep existing) ---
    private function createMessage($ticketId, $text, $file = null)
    {
        $path = null;
        $name = null;
        if ($file) {
            $name = $file->getClientOriginalName();
            $path = $file->store('support_attachments', 'public');
        }
        return TicketMessage::create([
            'support_ticket_id' => $ticketId,
            'user_id' => Auth::id(),
            'message' => $text,
            'attachment_path' => $path,
            'attachment_name' => $name
        ]);
    }

    // --- STATUS UPDATE (Keep existing) ---
    public function updateStatus(Request $request, $id) {
        if (Auth::user()->role !== 'super_admin') return response()->json(['message' => 'Unauthorized'], 403);
        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['status' => $request->status]);
        return response()->json(['message' => 'Status updated']);
    }
}