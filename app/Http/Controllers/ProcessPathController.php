<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\ProcessPathAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProcessPathController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'quarter' => ['required', 'integer', 'between:1,4'],
            'assignments' => ['required', 'array'],
            'assignments.*.associate_id' => ['required', 'integer', Rule::exists('associates', 'id')],
            'assignments.*.start_path' => ['required', Rule::in(['SBC', 'SRC', 'CC'])],
            'assignments.*.end_path' => ['nullable', Rule::in(['SBC', 'SRC', 'CC'])],
        ]);

        foreach ($validated['assignments'] as $row) {
            ProcessPathAssignment::query()->updateOrCreate(
                [
                    'associate_id' => $row['associate_id'],
                    'year' => $validated['year'],
                    'quarter' => $validated['quarter'],
                ],
                [
                    'start_path' => $row['start_path'],
                    'end_path' => $row['end_path'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('dashboard', $this->dashboardContext($request, [
                'year' => $validated['year'],
                'quarter' => $validated['quarter'],
            ]))
            ->with('success', 'Process path updated.');
    }

    public function print(Request $request): View
    {
        $context = $this->dashboardContext($request);
        $year = (int) $context['year'];
        $quarter = (int) $context['quarter'];

        $associates = Associate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $assignments = ProcessPathAssignment::query()
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->get()
            ->keyBy('associate_id');

        return view('process-path.print', [
            'year' => $year,
            'quarter' => $quarter,
            'associates' => $associates,
            'assignments' => $assignments,
        ]);
    }
}
