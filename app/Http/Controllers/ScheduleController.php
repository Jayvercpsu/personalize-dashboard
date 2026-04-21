<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Associate;
use App\Models\ScheduleDay;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    private const RAFFLE_POOL_SETTING_KEY = 'schedule_raffle_pools';

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

        $poolConfig = $this->loadPoolConfig($associates);
        $availableAssociates = $this->buildPoolFromIds(
            $associates,
            array_diff($associates->pluck('id')->all(), $poolConfig['unavailable'])
        );

        if ($availableAssociates->isEmpty()) {
            return back()->withErrors(['schedule' => 'All associates are marked as unavailable. Uncheck vacation first.']);
        }

        $sunWedPool = $this->resolveActivePool($associates, $availableAssociates, $poolConfig['sun_wed'], true);
        $wedSatPool = $this->resolveActivePool($associates, $availableAssociates, $poolConfig['wed_sat'], true);
        $partTimePool = $this->resolveActivePool($associates, $availableAssociates, $poolConfig['part_time'], false);

        $sunWedQueue = $this->buildQueue($sunWedPool);
        $wedSatQueue = $this->buildQueue($wedSatPool);
        $partTimeQueue = $this->buildQueue($partTimePool);

        $daysInMonth = $month->daysInMonth;

        for ($dayNumber = 1; $dayNumber <= $daysInMonth; $dayNumber++) {
            $date = $month->copy()->day($dayNumber);
            $currentDate = $date->toDateString();
            $dayOfWeek = (int) $date->dayOfWeek;

            $shiftAId = in_array($dayOfWeek, [0, 1, 2, 3], true)
                ? $this->drawFromQueue($sunWedQueue, $sunWedPool)
                : null;

            $shiftBId = in_array($dayOfWeek, [3, 4, 5, 6], true)
                ? $this->drawFromQueue($wedSatQueue, $wedSatPool)
                : null;

            $partTimeId = in_array($dayOfWeek, [0, 6], true)
                ? $this->drawFromQueue($partTimeQueue, $partTimePool)
                : null;

            ScheduleDay::query()->updateOrCreate(
                ['schedule_date' => $currentDate],
                [
                    'shift_a_associate_id' => $shiftAId,
                    'shift_b_associate_id' => $shiftBId,
                    'part_time_associate_id' => $partTimeId,
                ]
            );
        }

        return redirect()
            ->route('dashboard', $this->dashboardContext($request, ['month' => $validated['month']]))
            ->with('success', 'Monthly schedule raffle completed.');
    }

    public function updateDay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'schedule_date' => ['required', 'date'],
            'shift_a_associate_id' => ['nullable', 'integer', Rule::exists('associates', 'id')],
            'shift_b_associate_id' => ['nullable', 'integer', Rule::exists('associates', 'id')],
            'part_time_associate_id' => ['nullable', 'integer', Rule::exists('associates', 'id')],
        ]);

        ScheduleDay::query()->updateOrCreate(
            ['schedule_date' => $validated['schedule_date']],
            [
                'shift_a_associate_id' => $validated['shift_a_associate_id'] ?? null,
                'shift_b_associate_id' => $validated['shift_b_associate_id'] ?? null,
                'part_time_associate_id' => $validated['part_time_associate_id'] ?? null,
            ]
        );

        return redirect()
            ->route('dashboard', $this->dashboardContext($request))
            ->with('success', 'Schedule updated.');
    }

    public function updatePools(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sun_wed_ids' => ['nullable', 'array'],
            'sun_wed_ids.*' => ['integer', Rule::exists('associates', 'id')],
            'wed_sat_ids' => ['nullable', 'array'],
            'wed_sat_ids.*' => ['integer', Rule::exists('associates', 'id')],
            'part_time_ids' => ['nullable', 'array'],
            'part_time_ids.*' => ['integer', Rule::exists('associates', 'id')],
            'unavailable_ids' => ['nullable', 'array'],
            'unavailable_ids.*' => ['integer', Rule::exists('associates', 'id')],
        ]);

        $poolConfig = [
            'sun_wed' => $this->normalizeIds($validated['sun_wed_ids'] ?? []),
            'wed_sat' => $this->normalizeIds($validated['wed_sat_ids'] ?? []),
            'part_time' => $this->normalizeIds($validated['part_time_ids'] ?? []),
            'unavailable' => $this->normalizeIds($validated['unavailable_ids'] ?? []),
        ];

        AppSetting::query()->updateOrCreate(
            ['key' => self::RAFFLE_POOL_SETTING_KEY],
            ['value' => json_encode($poolConfig)]
        );

        return redirect()
            ->route('dashboard', $this->dashboardContext($request))
            ->with('success', 'Raffle pools saved.');
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

    private function loadPoolConfig(Collection $associates): array
    {
        $activeIds = array_map('intval', $associates->pluck('id')->all());
        $activeLookup = array_fill_keys($activeIds, true);

        $raw = AppSetting::query()
            ->where('key', self::RAFFLE_POOL_SETTING_KEY)
            ->value('value');

        $decoded = is_string($raw) ? json_decode($raw, true) : [];
        if (! is_array($decoded)) {
            $decoded = [];
        }

        return [
            'sun_wed' => $this->filterActiveIds($decoded['sun_wed'] ?? [], $activeLookup),
            'wed_sat' => $this->filterActiveIds($decoded['wed_sat'] ?? [], $activeLookup),
            'part_time' => $this->filterActiveIds($decoded['part_time'] ?? [], $activeLookup),
            'unavailable' => $this->filterActiveIds($decoded['unavailable'] ?? [], $activeLookup),
        ];
    }

    private function filterActiveIds(mixed $ids, array $activeLookup): array
    {
        if (! is_array($ids)) {
            return [];
        }

        $normalized = [];

        foreach ($ids as $id) {
            $intId = (int) $id;

            if (isset($activeLookup[$intId])) {
                $normalized[$intId] = true;
            }
        }

        return array_map('intval', array_keys($normalized));
    }

    private function normalizeIds(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            $intId = (int) $id;
            if ($intId > 0) {
                $normalized[$intId] = true;
            }
        }

        return array_map('intval', array_keys($normalized));
    }

    private function resolveActivePool(
        Collection $associates,
        Collection $availableAssociates,
        array $configuredIds,
        bool $fallbackToAvailable
    ): Collection {
        $availableLookup = array_fill_keys($availableAssociates->pluck('id')->all(), true);
        $eligibleIds = array_values(array_filter(
            $configuredIds,
            static fn (int $id): bool => isset($availableLookup[$id])
        ));

        $pool = $this->buildPoolFromIds($associates, $eligibleIds);

        if ($pool->isEmpty() && $fallbackToAvailable) {
            return $availableAssociates->values();
        }

        return $pool;
    }

    private function buildPoolFromIds(Collection $associates, array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $lookup = array_fill_keys(array_map('intval', $ids), true);

        return $associates
            ->filter(static fn (Associate $associate): bool => isset($lookup[$associate->id]))
            ->values();
    }

    private function buildQueue(Collection $pool): array
    {
        return $pool->pluck('id')->shuffle()->values()->all();
    }

    private function drawFromQueue(array &$queue, Collection $pool): ?int
    {
        if ($pool->isEmpty()) {
            return null;
        }

        if (empty($queue)) {
            $queue = $this->buildQueue($pool);
        }

        $picked = array_shift($queue);

        return is_numeric($picked) ? (int) $picked : null;
    }
}
