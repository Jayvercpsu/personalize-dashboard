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
            'assignments.*.associate_name' => ['required', 'string', 'max:80'],
            'assignments.*.path_1' => ['nullable', 'string', 'max:80'],
            'assignments.*.path_2' => ['nullable', 'string', 'max:80'],
            'assignments.*.path_3' => ['nullable', 'string', 'max:80'],
        ]);

        $submittedNames = [];

        foreach ($validated['assignments'] as $row) {
            $normalized = mb_strtolower(trim((string) $row['associate_name']));
            if ($normalized === '') {
                continue;
            }

            if (isset($submittedNames[$normalized])) {
                return back()
                    ->withErrors(['process' => 'Associate names in Process Path must be unique.'])
                    ->withInput();
            }

            $submittedNames[$normalized] = true;
        }

        foreach ($validated['assignments'] as $row) {
            $associateId = (int) $row['associate_id'];
            $associateName = trim((string) $row['associate_name']);
            $path1 = $this->sanitizePathValue($row['path_1'] ?? null);
            $path2 = $this->sanitizePathValue($row['path_2'] ?? null);
            $path3 = $this->sanitizePathValue($row['path_3'] ?? null);

            $nameTaken = Associate::query()
                ->where('id', '!=', $associateId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($associateName)])
                ->exists();

            if ($nameTaken) {
                return back()
                    ->withErrors(['process' => "The associate name \"{$associateName}\" is already in use."])
                    ->withInput();
            }

            Associate::query()
                ->whereKey($associateId)
                ->update(['name' => $associateName]);

            ProcessPathAssignment::query()->updateOrCreate(
                [
                    'associate_id' => $associateId,
                    'year' => $validated['year'],
                    'quarter' => $validated['quarter'],
                ],
                [
                    'start_path' => $this->legacyPathValue($path1, false),
                    'end_path' => $this->legacyPathValue($path2, true),
                    'path_1' => $path1,
                    'path_2' => $path2,
                    'path_3' => $path3,
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

    private function sanitizePathValue(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function legacyPathValue(?string $value, bool $nullable): ?string
    {
        $allowed = ['SBC', 'SRC', 'CC'];

        if ($value !== null && in_array($value, $allowed, true)) {
            return $value;
        }

        return $nullable ? null : 'SBC';
    }
}
