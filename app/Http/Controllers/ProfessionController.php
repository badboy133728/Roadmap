<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\JobPlatform;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use App\Services\CityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfessionController extends Controller
{
    public function index(Request $request): View
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
            'categories' => ProfessionCategory::orderBy('name')->get(),
            'search' => $search,
            'category' => $categorySlug,
        ]);
    }

    public function show(Profession $profession, CityService $cityService): View
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

        $institutionCount = Institution::where('city_id', $city->id)->count();

        $vacancies = $profession->jobVacancies()
            ->where('is_active', true)
            ->where(function ($query) use ($city) {
                $query->where('city_id', $city->id)->orWhereNull('city_id');
            })
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $jobPlatforms = JobPlatform::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $searchQuery = $profession->name . ' ' . $city->name;

        return view('professions.show', [
            'profession' => $profession,
            'city' => $city,
            'pathSteps' => $profession->careerPathSteps,
            'steps' => $profession->careerPathSteps,
            'salaries' => $salaries,
            'institutions' => $institutions,
            'institutionCount' => $institutionCount,
            'vacancies' => $vacancies,
            'jobPlatforms' => $jobPlatforms,
            'searchQuery' => $searchQuery,
        ]);
    }
}
