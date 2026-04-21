<?php

namespace App\Http\Controllers;

use App\Models\HourlyNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HourlyNoteController extends Controller
{
    public function upsert(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'note_date' => ['required', 'date'],
            'hour_slot' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'note' => ['nullable', 'string', 'max:5000'],
            'manager_comment' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['resolved', 'pending', 'needs_manager_attention'])],
        ]);

        $note = trim((string) ($validated['note'] ?? ''));
        $managerComment = trim((string) ($validated['manager_comment'] ?? ''));
        $wordCount = str_word_count($note);
        $managerWordCount = str_word_count($managerComment);

        if ($wordCount > 600) {
            return back()
                ->withErrors(['note' => 'Hourly note must not exceed 600 words.'])
                ->withInput();
        }

        if ($managerWordCount > 600) {
            return back()
                ->withErrors(['manager_comment' => 'Manager comment must not exceed 600 words.'])
                ->withInput();
        }

        HourlyNote::query()->updateOrCreate(
            [
                'note_date' => $validated['note_date'],
                'hour_slot' => $validated['hour_slot'],
            ],
            [
                'note' => $note,
                'manager_comment' => $managerComment,
                'status' => $validated['status'],
            ]
        );

        return redirect()
            ->route('dashboard', $this->dashboardContext($request, ['date' => $validated['note_date']]))
            ->with('success', 'Hourly note saved.');
    }
}
