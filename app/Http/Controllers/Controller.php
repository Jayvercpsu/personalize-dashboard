<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function dashboardContext(Request $request, array $overrides = []): array
    {
        $date = (string) $request->input('date', now()->toDateString());
        $month = (string) $request->input('month', now()->format('Y-m'));
        $year = (int) $request->input('year', now()->year);
        $quarter = (int) $request->input('quarter', (int) ceil(now()->month / 3));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = now()->toDateString();
        }

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $year = min(2100, max(2000, $year));

        if ($quarter < 1 || $quarter > 4) {
            $quarter = (int) ceil(now()->month / 3);
        }

        return array_merge([
            'date' => $date,
            'month' => $month,
            'year' => $year,
            'quarter' => $quarter,
        ], $overrides);
    }
}
