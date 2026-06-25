<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Profession;
use App\Services\CareerPathService;
use App\Services\CatalogService;
use App\Services\CityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionController extends Controller
{
    public function index(Request $request, CatalogService $catalog): View
    {
        $search = $request->string('search')->trim()->toString();
        $categorySlug = $request->string('category')->trim()->toString();

        $professions = Profession::query()
            ->with('category')
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($categorySlug !== '', function ($query) use ($categorySlug) {
                $query->whereHas('category', fn ($query) => $query->where('slug', $categorySlug));
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('professions.index', [
            'professions' => $professions,
            'categories' => $catalog->categories(),
            'search' => $search,
            'category' => $categorySlug,
        ]);
    }

    public function show(Profession $profession, CityService $cityService, CatalogService $catalog, CareerPathService $careerPathService): View
    {
        abort_unless($profession->is_active, 404);

        $city = $cityService->current();

        $profession->load(['category', 'careerPathSteps']);

        $salaries = $profession->salaries()
            ->where('city_id', $city->id)
            ->get()
            ->keyBy('level');

        $institutions = Institution::query()
            ->where('city_id', $city->id)
            ->whereHas('educationPrograms', fn ($query) => $query->where('profession_id', $profession->id))
            ->with(['educationPrograms' => fn ($query) => $query->where('profession_id', $profession->id)])
            ->orderBy('name')
            ->get();

        $institutionCount = $institutions->isEmpty() && $city->id > 0
            ? Institution::where('city_id', $city->id)->count()
            : 0;

        $vacancies = $profession->jobVacancies()
            ->where('is_active', true)
            ->where(function ($query) use ($city) {
                $query->where('city_id', $city->id)->orWhereNull('city_id');
            })
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $pathData = $careerPathService->forProfession(
            $profession,
            auth()->user(),
            $city,
            $vacancies,
            $institutions,
        );

        return view('professions.show', [
            'profession' => $profession,
            'city' => $city,
            'pathSteps' => $profession->careerPathSteps,
            'steps' => $pathData['steps'],
            'pathSummary' => $pathData['summary'] ?? null,
            'pathPersonalized' => $pathData['personalized'] ?? false,
            'pathSource' => $pathData['source'] ?? 'static',
            'pathHint' => $pathData['hint'] ?? null,
            'pathTransition' => $pathData['transition'] ?? null,
            'pathTotalLabel' => $pathData['total_years_label'] ?? null,
            'salaries' => $salaries,
            'institutions' => $institutions,
            'institutionCount' => $institutionCount,
            'vacancies' => $vacancies,
            'jobPlatforms' => $catalog->jobPlatforms(),
            'searchQuery' => $profession->name . ' ' . $city->name,
        ]);
    }
}
