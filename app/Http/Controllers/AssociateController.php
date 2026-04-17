<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssociateController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $name = trim($validated['name']);
        $existing = Associate::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existing) {
            $existing->update(['is_active' => true]);
        } else {
            $request->validate([
                'name' => ['required', 'string', 'max:80', Rule::unique('associates', 'name')],
            ]);

            Associate::query()->create([
                'name' => $name,
                'is_active' => true,
            ]);
        }

        return redirect()
            ->route('dashboard', $this->dashboardContext($request))
            ->with('success', 'Associate added.');
    }

    public function destroy(Request $request, Associate $associate): RedirectResponse
    {
        $associate->update(['is_active' => false]);

        return redirect()
            ->route('dashboard', $this->dashboardContext($request))
            ->with('success', 'Associate hidden from schedule list.');
    }
}
