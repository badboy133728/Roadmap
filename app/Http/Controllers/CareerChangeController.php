<?php

namespace App\Http\Controllers;

use App\Services\CareerTransitionService;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerChangeController extends Controller
{
    public function show(CatalogService $catalog): View
    {
        return view('career-change.show', [
            'professions' => $catalog->professionOptions(),
        ]);
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
