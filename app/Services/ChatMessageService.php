<?php

namespace App\Services;

use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatMessage;

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
        $data['sender_type'] = auth()->user()->isClient() ? 'client' : 'support';

        return ChatMessage::create($data);
    }

    public function getUserMessages(string $userId)
    {
        return ChatMessage::where('user_id', $userId)
            ->with('user')
            ->latest()
            ->get();
    }

    public function markAsRead(string $id)
    {
        $message = ChatMessage::findOrFail($id);
        $message->update(['is_read' => true]);
        return $message;
    }

    public function getUnreadMessages()
    {
        return ChatMessage::unread()->with('user')->latest()->get();
    }
}
