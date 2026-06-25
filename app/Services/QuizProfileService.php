<?php

namespace App\Services;

use App\Models\User;

class QuizProfileService
{
    public function fromQuizInput(array $profile): array
    {
        if (! auth()->check()) {
            return $profile;
        }

        return $this->mergeUser(
            $profile,
            auth()->user()->loadMissing(['city', 'currentProfession', 'favoriteProfessions']),
        );
    }

    public function mergeUser(array $profile, User $user): array
    {
        $city = $user->city ?? app(CityService::class)->current();

        if (empty($profile['name']) && filled($user->name)) {
            $profile['name'] = $user->name;
        }

        $profile['account'] = [
            'is_registered' => true,
            'email' => $user->email,
            'city_id' => $city->id,
            'city_name' => $city->name,
            'city_region' => $city->region,
            'current_profession' => $user->currentProfession?->name,
            'current_profession_slug' => $user->currentProfession?->slug,
            'favorite_professions' => $user->favoriteProfessions
                ?->pluck('name')
                ->take(5)
                ->values()
                ->all() ?? [],
        ];

        return $profile;
    }

    public function authPrefill(): ?array
    {
        if (! auth()->check()) {
            return null;
        }

        $user = auth()->user()->loadMissing(['city', 'currentProfession']);

        return [
            'name' => $user->name,
            'city_name' => $user->city?->name,
            'current_profession' => $user->currentProfession?->name,
            'is_registered' => true,
        ];
    }
}
