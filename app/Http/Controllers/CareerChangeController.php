<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Profession;
use App\Models\QuizResult;
use App\Services\CareerPathService;
use App\Services\CareerTransitionService;
use App\Services\CatalogService;
use App\Services\CityService;
use App\Services\InstitutionAiService;
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

    public function result(
        Request $request,
        CareerTransitionService $transitionService,
        CareerPathService $careerPathService,
        InstitutionAiService $institutionAi,
        CityService $cityService,
    ): View {
        $validated = $request->validate([
            'from_profession_id' => ['nullable', 'integer', 'exists:professions,id'],
            'to_profession_id' => ['required', 'integer', 'exists:professions,id'],
        ]);

        $transition = $transitionService->findOrBuild(
            $validated['from_profession_id'] ?? null,
            $validated['to_profession_id'],
        );

        $to = $transition['to'];
        $from = $transition['from'];
        $city = $cityService->current();
        $user = auth()->user();

        $quizResult = $user
            ? QuizResult::query()->where('user_id', $user->id)->latest()->first()
            : null;

        $institutions = Institution::query()
            ->where('city_id', $city->id)
            ->whereHas('educationPrograms', fn ($q) => $q->where('profession_id', $to->id))
            ->with(['educationPrograms' => fn ($q) => $q->where('profession_id', $to->id)])
            ->orderBy('name')
            ->get();

        $vacancies = $to->jobVacancies()
            ->where('is_active', true)
            ->where(function ($query) use ($city) {
                $query->where('city_id', $city->id)->orWhereNull('city_id');
            })
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $educationData = $institutionAi->recommend($to, $city, $user, $quizResult);

        if ($user) {
            $pathData = $careerPathService->forProfession(
                $to,
                $user,
                $city,
                $vacancies,
                $institutions,
                $from,
                $educationData,
            );
            $transition['steps'] = $pathData['steps'];
            $transition['path_summary'] = $pathData['summary'] ?? null;
            $transition['path_source'] = $pathData['source'] ?? 'local';
            $transition['path_total_label'] = $pathData['total_years_label'] ?? null;
        }

        $transition['education'] = $educationData;
        $transition['city'] = $city;

        return view('career-change.result', ['plan' => $transition]);
    }
}
