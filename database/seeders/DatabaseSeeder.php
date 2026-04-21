<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Associate;
use App\Models\ChatMessage;
use App\Models\HourlyNote;
use App\Models\ProcessPathAssignment;
use App\Models\ScheduleDay;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'test@gmail.com',
        ], [
            'name' => 'ICQA Shared Account',
            'password' => Hash::make('test123'),
        ]);

        $associateNames = [
            'Shan',
            'Carlos',
            'John',
            'Chloe',
            'Ant',
            'Mary',
            'Nang',
            'Esah',
            'Java',
            'Gonh',
        ];

        foreach ($associateNames as $name) {
            Associate::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }

        AppSetting::query()->updateOrCreate(
            ['key' => 'schedule_theme'],
            ['value' => 'blue']
        );

        $today = now()->toDateString();
        $hourSlots = collect(range(7, 18))
            ->map(fn (int $hour): string => sprintf('%02d:00', $hour));

        foreach ($hourSlots as $slot) {
            HourlyNote::query()->updateOrCreate(
                ['note_date' => $today, 'hour_slot' => $slot],
                [
                    'note' => null,
                    'status' => 'pending',
                ]
            );
        }

        ChatMessage::query()->firstOrCreate([
            'sender_role' => 'manager',
            'message' => 'Please check urgent concerns before 11:00 AM.',
        ]);

        ChatMessage::query()->firstOrCreate([
            'sender_role' => 'associate',
            'message' => 'Received. Updating notes and schedule now.',
        ]);

        $month = now()->startOfMonth();
        $associates = Associate::query()->where('is_active', true)->orderBy('name')->get()->values();
        $associateCount = $associates->count();
        $sunWedPool = $associates->slice(0, max(1, (int) ceil($associateCount / 2)))->values();
        $wedSatPool = $associates->slice((int) floor($associateCount / 3))->values();
        $partTimePool = $associates->slice(0, min(2, $associateCount))->values();

        AppSetting::query()->updateOrCreate(
            ['key' => 'schedule_raffle_pools'],
            ['value' => json_encode([
                'sun_wed' => $sunWedPool->pluck('id')->all(),
                'wed_sat' => $wedSatPool->pluck('id')->all(),
                'part_time' => $partTimePool->pluck('id')->all(),
                'unavailable' => [],
            ])]
        );

        $sunWedRotation = 0;
        $wedSatRotation = 0;
        $partTimeRotation = 0;

        if ($associateCount > 0) {
            for ($day = 1; $day <= $month->daysInMonth; $day++) {
                $dateCursor = $month->copy()->day($day);
                $date = $dateCursor->toDateString();
                $dayOfWeek = (int) $dateCursor->dayOfWeek;

                $shiftA = null;
                $shiftB = null;
                $partTime = null;

                if (in_array($dayOfWeek, [0, 1, 2, 3], true) && $sunWedPool->isNotEmpty()) {
                    $shiftA = $sunWedPool[$sunWedRotation % $sunWedPool->count()];
                    $sunWedRotation++;
                }

                if (in_array($dayOfWeek, [3, 4, 5, 6], true) && $wedSatPool->isNotEmpty()) {
                    $shiftB = $wedSatPool[$wedSatRotation % $wedSatPool->count()];
                    $wedSatRotation++;
                }

                if (in_array($dayOfWeek, [0, 6], true) && $partTimePool->isNotEmpty()) {
                    $partTime = $partTimePool[$partTimeRotation % $partTimePool->count()];
                    $partTimeRotation++;
                }

                ScheduleDay::query()->updateOrCreate(
                    ['schedule_date' => $date],
                    [
                        'shift_a_associate_id' => $shiftA?->id,
                        'shift_b_associate_id' => $shiftB?->id,
                        'part_time_associate_id' => $partTime?->id,
                    ]
                );
            }
        }

        $year = now()->year;
        $quarter = (int) ceil(now()->month / 3);
        $paths = ['SBC', 'SRC', 'CC'];

        foreach ($associates as $index => $associate) {
            ProcessPathAssignment::query()->updateOrCreate([
                'associate_id' => $associate->id,
                'year' => $year,
                'quarter' => $quarter,
            ], [
                'start_path' => $paths[$index % count($paths)],
                'end_path' => $paths[($index + 1) % count($paths)],
                'path_1' => $paths[$index % count($paths)],
                'path_2' => $paths[($index + 1) % count($paths)],
                'path_3' => $paths[($index + 2) % count($paths)],
            ]);
        }
    }
}
