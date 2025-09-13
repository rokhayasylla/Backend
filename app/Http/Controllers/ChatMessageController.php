<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Models\ChatMessage;
use App\Services\ChatMessageService;
use Illuminate\Http\Request;

class ChatMessageController extends Controller
{
    protected $chatMessageService;

    public function __construct(ChatMessageService $chatMessageService)
    {
        $this->chatMessageService = $chatMessageService;
    }

    public function index()
    {
        $messages = $this->chatMessageService->index();
        return response()->json($messages, 200);
    }

    public function store(ChatMessageRequest $request)
    {
        $message = $this->chatMessageService->store($request);
        return response()->json($message, 201);
    }

    public function myMessages()
    {
        $messages = $this->chatMessageService->getUserMessages(auth()->id());
        return response()->json($messages, 200);
    }

    public function markAsRead(string $id)
    {
        $message = $this->chatMessageService->markAsRead($id);
        return response()->json($message, 200);
    }

    public function unread()
    {
        $messages = $this->chatMessageService->getUnreadMessages();
        $count = $messages->count();

        return response()->json([
            'count' => $count,
            'messages' => $messages
        ], 200);
    }

    // ========== NOUVELLES MÉTHODES À AJOUTER ==========

    public function getAllConversations()
    {
        $conversations = $this->chatMessageService->getAllConversations();
        return response()->json($conversations, 200);
    }

    public function getUserMessages($userId)
    {
        $messages = $this->chatMessageService->getUserMessages($userId);
        return response()->json($messages, 200);
    }

    public function markUserMessagesAsRead($userId)
    {
        ChatMessage::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read']);
    }

}
