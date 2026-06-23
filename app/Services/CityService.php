<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Cookie;

class CityService
{
    public const COOKIE_NAME = 'selected_city';

    public function current(): City
    {
        $slug = request()->cookie(self::COOKIE_NAME)
            ?? auth()->user()?->city?->slug
            ?? City::where('is_default', true)->value('slug')
            ?? 'volgograd';

        return City::where('slug', $slug)->first()
            ?? City::where('is_default', true)->first()
            ?? City::firstOrFail();
    }

    public function set(string $slug): City
    {
        $city = City::where('slug', $slug)->firstOrFail();

        Cookie::queue(self::COOKIE_NAME, $city->slug, 60 * 24 * 365);

        if (auth()->check() && auth()->user()->city_id !== $city->id) {
            auth()->user()->update(['city_id' => $city->id]);
        }

        return $city;
    }

    public function all()
    {
        return City::orderByDesc('is_default')->orderBy('name')->get();
    }
}
