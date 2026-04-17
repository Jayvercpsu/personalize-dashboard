<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Associate;
use App\Models\ChatMessage;
use App\Models\HourlyNote;
use App\Models\ProcessPathAssignment;
use App\Models\ScheduleDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $context = $this->dashboardContext($request);

        $selectedDate = Carbon::parse($context['date'])->toDateString();
        $selectedMonth = Carbon::createFromFormat('Y-m', $context['month'])->startOfMonth();
        $selectedYear = $context['year'];
        $selectedQuarter = $context['quarter'];

        $hourSlots = collect(range(7, 16))
            ->map(fn (int $hour): string => sprintf('%02d:00', $hour));

        $existingNotes = HourlyNote::whereDate('note_date', $selectedDate)
            ->get()
            ->keyBy('hour_slot');

        $hourlyNotes = $hourSlots->map(function (string $slot) use ($existingNotes, $selectedDate): HourlyNote {
            return $existingNotes->get($slot) ?? new HourlyNote([
                'note_date' => $selectedDate,
                'hour_slot' => $slot,
                'status' => 'pending',
            ]);
        });

        $statusOverview = [
            'resolved' => HourlyNote::whereDate('note_date', $selectedDate)->where('status', 'resolved')->count(),
            'pending' => HourlyNote::whereDate('note_date', $selectedDate)->where('status', 'pending')->count(),
            'needs_manager_attention' => HourlyNote::whereDate('note_date', $selectedDate)->where('status', 'needs_manager_attention')->count(),
        ];
        $statusOverview['total'] = array_sum($statusOverview);

        $chatMessages = ChatMessage::query()
            ->orderBy('created_at')
            ->take(150)
            ->get();

        $associates = Associate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $scheduleTheme = AppSetting::query()
            ->where('key', 'schedule_theme')
            ->value('value') ?? 'blue';

        if (! in_array($scheduleTheme, ['blue', 'green', 'gray'], true)) {
            $scheduleTheme = 'blue';
        }

        $monthStart = $selectedMonth->copy();
        $monthEnd = $selectedMonth->copy()->endOfMonth();
        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        $scheduleDays = ScheduleDay::query()
            ->with(['shiftA', 'shiftB'])
            ->whereBetween('schedule_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->keyBy(fn (ScheduleDay $day) => $day->schedule_date->toDateString());

        $calendarWeeks = [];
        $cursor = $calendarStart->copy();

        while ($cursor->lte($calendarEnd)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $cursor->copy()->addDays($i);
                $isoDate = $date->toDateString();

                $week[] = [
                    'iso_date' => $isoDate,
                    'day_number' => $date->day,
                    'is_current_month' => $date->month === $monthStart->month,
                    'schedule' => $scheduleDays->get($isoDate),
                ];
            }

            $calendarWeeks[] = $week;
            $cursor->addWeek();
        }

        $processAssignments = ProcessPathAssignment::query()
            ->where('year', $selectedYear)
            ->where('quarter', $selectedQuarter)
            ->get()
            ->keyBy('associate_id');

        return view('dashboard.index', [
            'selectedDate' => $selectedDate,
            'selectedMonth' => $monthStart,
            'selectedYear' => $selectedYear,
            'selectedQuarter' => $selectedQuarter,
            'hourlyNotes' => $hourlyNotes,
            'statusOverview' => $statusOverview,
            'chatMessages' => $chatMessages,
            'associates' => $associates,
            'scheduleTheme' => $scheduleTheme,
            'calendarWeeks' => $calendarWeeks,
            'processAssignments' => $processAssignments,
            'context' => $context,
        ]);
    }
}
