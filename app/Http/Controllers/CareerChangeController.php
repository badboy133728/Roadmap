<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use App\Services\CareerTransitionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerChangeController extends Controller
{
    public function show(): View
    {
        $professions = Profession::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('career-change.show', compact('professions'));
    }

    public function result(Request $request, CareerTransitionService $transitionService): View
    {
        $validated = $request->validate([
            'from_profession_id' => ['nullable', 'integer', 'exists:professions,id'],
            'to_profession_id' => ['required', 'integer', 'exists:professions,id'],
        ]);

        $transition = $transitionService->findOrBuild(
            $validated['from_profession_id'] ?? null,
            $validated['to_profession_id'],
        );

        return view('career-change.result', ['plan' => $transition]);
    }
}
