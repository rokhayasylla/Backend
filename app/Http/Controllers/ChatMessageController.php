<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
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
        return response()->json($messages, 200);
    }
}
