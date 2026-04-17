<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatMessageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sender_role' => ['required', Rule::in(['associate', 'manager'])],
            'message' => ['required', 'string', 'max:600'],
        ]);

        ChatMessage::query()->create([
            'sender_role' => $validated['sender_role'],
            'message' => trim($validated['message']),
        ]);

        return redirect()
            ->route('dashboard', $this->dashboardContext($request))
            ->with('success', 'Message sent.');
    }
}
