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
        $hourSlots = collect(range(7, 16))
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
        $rotation = 0;

        if ($associateCount > 0) {
            for ($day = 1; $day <= $month->daysInMonth; $day++) {
                $date = $month->copy()->day($day)->toDateString();
                $shiftA = $associates[$rotation % $associateCount];
                $shiftB = $associateCount > 1 ? $associates[($rotation + 1) % $associateCount] : null;

                ScheduleDay::query()->updateOrCreate(
                    ['schedule_date' => $date],
                    [
                        'shift_a_associate_id' => $shiftA->id,
                        'shift_b_associate_id' => $shiftB?->id,
                    ]
                );

                $rotation++;
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
            ]);
        }

        AppSetting::query()->updateOrCreate([
            'key' => 'schedule_rotation_offset',
        ], [
            'value' => (string) ($month->daysInMonth + 1),
        ]);
    }
}
