<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AskModeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(): View
    {
        $conversations = Conversation::query()
            ->addSelect([
                'last_message_content' => Message::query()
                    ->select('content')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->withCount('messages')
            ->latest()
            ->get();

        return view('chats.index', [
            'conversations' => $conversations,
        ]);
    }

    public function store(): RedirectResponse
    {
        $conversation = Conversation::create([
            'title' => 'New chat',
        ]);

        return redirect()->route('chats.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation, AskModeResolver $resolver): View
    {
        $messages = $conversation->messages()
            ->orderBy('id')
            ->get();

        return view('chats.show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'askMode' => $resolver->resolve($request),
        ]);
    }
}
