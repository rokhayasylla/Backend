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

    // ✅ CORRECTION PRINCIPALE ICI
    public function store(ChatMessageRequest $request)
    {
        $data = $request->validated();
        $user = auth()->user();

        // ✅ CORRECTION : Gérer différemment client et support
        if ($user->role === 'client') {
            // Pour un client : user_id = ID du client lui-même
            $data['user_id'] = $user->id;
            $data['sender_type'] = 'client';
        } else {
            // Pour le support : user_id = ID du client destinataire (fourni dans la requête)
            // ✅ NE PAS écraser le user_id fourni dans la requête
            if (!isset($data['user_id'])) {
                throw new \Exception('Le user_id du destinataire est requis pour les messages de support');
            }
            $data['sender_type'] = 'support';
        }

        $data['is_read'] = false;

        $message = ChatMessage::create($data);
        return $message->load('user');
    }

    public function getUserMessages($userId)
    {
        return ChatMessage::where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'asc')
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
