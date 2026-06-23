<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Profession;
use App\Services\CityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(): View
    {
        $user = auth()->user()->load([
            'favoriteProfessions.category',
            'quizResults' => fn ($query) => $query->latest()->limit(10),
            'savedPaths.profession',
            'savedPaths.city',
            'city',
            'currentProfession',
        ]);

        return view('dashboard.career', [
            'user' => $user,
            'favorites' => $user->favoriteProfessions,
            'quizHistory' => $user->quizResults,
            'savedPaths' => $user->savedPaths,
            'cities' => City::query()->orderByDesc('is_default')->orderBy('name')->get(),
            'professions' => Profession::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function addFavorite(Profession $profession): RedirectResponse
    {
        auth()->user()->favoriteProfessions()->syncWithoutDetaching([$profession->id]);

        return back()->with('status', 'favorite-added');
    }

    public function removeFavorite(Profession $profession): RedirectResponse
    {
        auth()->user()->favoriteProfessions()->detach($profession->id);

        return back()->with('status', 'favorite-removed');
    }

    public function updateProfile(Request $request, CityService $cityService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'current_profession_id' => ['nullable', 'integer', 'exists:professions,id'],
        ]);

        $user = $request->user();
        $user->update($validated);

        $city = City::find($validated['city_id']);

        if ($city) {
            $cityService->set($city->slug);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'profile-updated');
    }
}
