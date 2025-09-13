<?php

namespace App\Services;

use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatMessage;
use App\Models\User;

class ChatMessageService
{
    public function index()
    {
        return ChatMessage::with('user')->latest()->get();
    }

    public function store(ChatMessageRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // Déterminer le sender_type selon le rôle de l'utilisateur
        $user = auth()->user();
        $data['sender_type'] = $user->role === 'client' ? 'client' : 'support';
        $data['is_read'] = false;

        $message = ChatMessage::create($data);
        return $message->load('user');
    }

    public function getUserMessages($userId)
    {
        return ChatMessage::where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'asc') // Changer de latest() à orderBy pour chronologie
            ->get();
    }

    public function markAsRead(string $id)
    {
        $message = ChatMessage::findOrFail($id);
        $message->update(['is_read' => true]);
        return $message->load('user');
    }

    public function getUnreadMessages()
    {
        // Pour les clients : messages du support non lus
        if (auth()->user()->role === 'client') {
            return ChatMessage::where('user_id', auth()->id())
                ->where('sender_type', 'support')
                ->unread()
                ->with('user')
                ->latest()
                ->get();
        }

        // Pour le support : tous les messages clients non lus
        return ChatMessage::where('sender_type', 'client')
            ->unread()
            ->with('user')
            ->latest()
            ->get();
    }

    // ========== NOUVELLES MÉTHODES ==========

    public function getAllConversations()
    {
        $conversations = User::where('role', 'client')
            ->whereHas('chatMessages')
            ->with(['chatMessages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->get()
            ->map(function($user) {
                $latestMessage = $user->chatMessages->first();
                $unreadCount = $user->chatMessages()
                    ->where('sender_type', 'client')
                    ->where('is_read', false)
                    ->count();

                return [
                    'user_id' => $user->id,
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email
                    ],
                    'latest_message' => $latestMessage ? [
                        'id' => $latestMessage->id,
                        'message' => $latestMessage->message,
                        'sender_type' => $latestMessage->sender_type,
                        'created_at' => $latestMessage->created_at->toISOString(),
                        'is_read' => $latestMessage->is_read
                    ] : null,
                    'unread_count' => $unreadCount
                ];
            })
            ->sortByDesc(function($conversation) {
                return $conversation['latest_message'] ? $conversation['latest_message']['created_at'] : null;
            })
            ->values();

        return $conversations;
    }
}
