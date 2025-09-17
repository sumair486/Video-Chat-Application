<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
     public function index()
    {
        $messages = Message::with('user')->latest()->take(50)->get()->reverse();
        return view('chat.index', compact('messages'));
    }

    public function store(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $message = Message::create([
            'user_id' => auth()->id(),
            'message' => $request->message,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message->load('user'));
    }
}
