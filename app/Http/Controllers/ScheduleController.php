<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Associate;
use App\Models\ScheduleDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function generateMonth(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $month = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $associates = Associate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($associates->isEmpty()) {
            return back()->withErrors(['schedule' => 'Please add at least one associate before generating schedule.']);
        }

        $offset = (int) (AppSetting::query()->where('key', 'schedule_rotation_offset')->value('value') ?? 0);
        $index = max(0, $offset);
        $daysInMonth = $month->daysInMonth;

        for ($dayNumber = 1; $dayNumber <= $daysInMonth; $dayNumber++) {
            $currentDate = $month->copy()->day($dayNumber)->toDateString();

            [$shiftAId, $shiftBId] = $this->resolveRotation($associates, $index);

            ScheduleDay::query()->updateOrCreate(
                ['schedule_date' => $currentDate],
                [
                    'shift_a_associate_id' => $shiftAId,
                    'shift_b_associate_id' => $shiftBId,
                ]
            );

            $index++;
        }

        AppSetting::query()->updateOrCreate(
            ['key' => 'schedule_rotation_offset'],
            ['value' => (string) $index]
        );

        return redirect()
            ->route('dashboard', $this->dashboardContext($request, ['month' => $validated['month']]))
            ->with('success', 'Monthly schedule auto-generated.');
    }

    public function updateDay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'schedule_date' => ['required', 'date'],
            'shift_a_associate_id' => ['nullable', 'integer', Rule::exists('associates', 'id')],
            'shift_b_associate_id' => ['nullable', 'integer', Rule::exists('associates', 'id')],
        ]);

        ScheduleDay::query()->updateOrCreate(
            ['schedule_date' => $validated['schedule_date']],
            [
                'shift_a_associate_id' => $validated['shift_a_associate_id'] ?? null,
                'shift_b_associate_id' => $validated['shift_b_associate_id'] ?? null,
            ]
        );

        return redirect()
            ->route('dashboard', $this->dashboardContext($request))
            ->with('success', 'Schedule updated.');
    }

    public function setTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'schedule_theme' => ['required', Rule::in(['blue', 'green', 'gray'])],
        ]);

        AppSetting::query()->updateOrCreate(
            ['key' => 'schedule_theme'],
            ['value' => $validated['schedule_theme']]
        );

        return redirect()
            ->route('dashboard', $this->dashboardContext($request))
            ->with('success', 'Schedule theme changed.');
    }

    private function resolveRotation(Collection $associates, int $index): array
    {
        $count = $associates->count();

        $shiftA = $associates[$index % $count];
        $shiftB = $count > 1 ? $associates[($index + 1) % $count] : null;

        return [$shiftA->id, $shiftB?->id];
    }
}
