<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\StaffMessage;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffChatController extends Controller
{
    /**
     * Render the main Staff Chat page.
     */
    public function index(Request $request): Response
    {
        $currentUser = auth()->user();

        // Get all staff members except current user
        $query = User::staff()
            ->where('id', '!=', $currentUser->id)
            ->where('is_active', true);

        // Superadmin chat is scoped exclusively to Admin SWA and Admin 4Ps (excluding Barangay Assistants)
        if ($currentUser->isSuperadmin()) {
            $query->whereIn('role', ['admin_swa', 'admin_4ps', 'admin']);
        }

        $contacts = $query
            ->orderByRaw("
                CASE role
                    WHEN 'superadmin' THEN 1
                    WHEN 'admin_swa' THEN 2
                    WHEN 'admin_4ps' THEN 3
                    WHEN 'barangay_assistant' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('name')
            ->get()
            ->map(function ($u) use ($currentUser) {
                $unread = StaffMessage::where('sender_id', $u->id)
                    ->where('recipient_id', $currentUser->id)
                    ->whereNull('read_at')
                    ->count();

                $lastMsg = StaffMessage::betweenUsers($currentUser->id, $u->id)
                    ->latest()
                    ->first();

                return [
                    'id'               => $u->id,
                    'name'             => $u->name,
                    'email'            => $u->email,
                    'username'         => $u->username,
                    'role'             => $u->role,
                    'role_display'     => $u->role_display,
                    'assigned_barangay'=> $u->assigned_barangay,
                    'unread_count'     => $unread,
                    'last_message'     => $lastMsg ? [
                        'text'       => $lastMsg->message,
                        'created_at' => $lastMsg->created_at->diffForHumans(),
                        'sender_id'  => $lastMsg->sender_id,
                    ] : null,
                ];
            });

        // Determine active contact
        $activeContactId = $request->query('contact_id');
        if (!$activeContactId && $contacts->isNotEmpty()) {
            $activeContactId = $contacts->first()['id'];
        }

        $messages = [];
        $activeContact = null;

        if ($activeContactId) {
            $activeUser = User::find($activeContactId);
            if ($activeUser) {
                // Mark messages from active contact to current user as read
                StaffMessage::where('sender_id', $activeContactId)
                    ->where('recipient_id', $currentUser->id)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);

                $messages = StaffMessage::betweenUsers($currentUser->id, $activeContactId)
                    ->with(['sender:id,name,role', 'recipient:id,name,role'])
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(fn($m) => [
                        'id'            => $m->id,
                        'sender_id'     => $m->sender_id,
                        'recipient_id'  => $m->recipient_id,
                        'sender_name'   => $m->sender?->name,
                        'sender_role'   => $m->sender?->role_display,
                        'message'       => $m->message,
                        'created_at'    => $m->created_at->format('g:i A'),
                        'full_time'     => $m->created_at->format('M d, Y g:i A'),
                        'is_me'         => $m->sender_id === $currentUser->id,
                        'read_at'       => $m->read_at?->format('g:i A'),
                    ]);

                $activeContact = [
                    'id'               => $activeUser->id,
                    'name'             => $activeUser->name,
                    'role_display'     => $activeUser->role_display,
                    'assigned_barangay'=> $activeUser->assigned_barangay,
                ];
            }
        }

        $totalUnread = StaffMessage::unreadForUser($currentUser->id)->count();

        return Inertia::render('Staff/Chat/Index', [
            'contacts'         => $contacts,
            'activeContact'    => $activeContact,
            'messages'         => $messages,
            'totalUnreadCount' => $totalUnread,
        ]);
    }

    /**
     * Poll / fetch latest messages with active contact.
     */
    public function fetchMessages(Request $request, int $contactId): JsonResponse
    {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Mark incoming messages as read
        StaffMessage::where('sender_id', $contactId)
            ->where('recipient_id', $currentUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = StaffMessage::betweenUsers($currentUser->id, $contactId)
            ->with(['sender:id,name,role'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id'           => $m->id,
                'sender_id'    => $m->sender_id,
                'recipient_id' => $m->recipient_id,
                'sender_name'  => $m->sender?->name,
                'sender_role'  => $m->sender?->role_display,
                'message'      => $m->message,
                'created_at'   => $m->created_at->format('g:i A'),
                'full_time'    => $m->created_at->format('M d, Y g:i A'),
                'is_me'        => $m->sender_id === $currentUser->id,
                'read_at'      => $m->read_at?->format('g:i A'),
            ]);

        $totalUnread = StaffMessage::unreadForUser($currentUser->id)->count();

        return response()->json([
            'success'     => true,
            'messages'    => $messages,
            'totalUnread' => $totalUnread,
        ]);
    }

    /**
     * Send a new message to a staff contact.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message'      => 'required|string|max:2000',
        ]);

        $currentUser = auth()->user();
        if (!$currentUser) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($currentUser->isSuperadmin()) {
            $recipient = User::find($request->recipient_id);
            if ($recipient && $recipient->isBarangayAssistant()) {
                return response()->json(['error' => 'Superadmin can only contact Admin SWA and Admin 4Ps.'], 403);
            }
        }

        $msg = StaffMessage::create([
            'sender_id'    => $currentUser->id,
            'recipient_id' => $request->recipient_id,
            'message'      => trim($request->message),
        ]);

        AuditLogService::log('staff_message_sent', $msg, [], [], "Message sent to staff user ID {$request->recipient_id}");

        return response()->json([
            'success' => true,
            'message' => [
                'id'           => $msg->id,
                'sender_id'    => $msg->sender_id,
                'recipient_id' => $msg->recipient_id,
                'sender_name'  => $currentUser->name,
                'sender_role'  => $currentUser->role_display,
                'message'      => $msg->message,
                'created_at'   => $msg->created_at->format('g:i A'),
                'full_time'    => $msg->created_at->format('M d, Y g:i A'),
                'is_me'        => true,
                'read_at'      => null,
            ],
        ]);
    }

    /**
     * Get total unread messages count for logged in staff user.
     */
    public function unreadCount(): JsonResponse
    {
        $unread = StaffMessage::unreadForUser(auth()->id())->count();

        return response()->json(['unread_count' => $unread]);
    }
}
