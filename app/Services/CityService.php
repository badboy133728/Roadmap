<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class CityService
{
    public const COOKIE_NAME = 'selected_city';

    public function current(): City
    {
        try {
            $slug = request()->cookie(self::COOKIE_NAME)
                ?? auth()->user()?->city?->slug
                ?? City::where('is_default', true)->value('slug')
                ?? 'volgograd';

            return City::where('slug', $slug)->first()
                ?? City::where('is_default', true)->first()
                ?? City::first()
                ?? $this->fallbackCity();
        } catch (\Throwable $e) {
            Log::warning('CityService::current failed', ['error' => $e->getMessage()]);

            return $this->fallbackCity();
        }
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
        try {
            return City::orderByDesc('is_default')->orderBy('name')->get();
        } catch (\Throwable $e) {
            Log::warning('CityService::all failed', ['error' => $e->getMessage()]);

            return collect([$this->fallbackCity()]);
        }
    }

    private function fallbackCity(): City
    {
        $city = new City;
        $city->id = 0;
        $city->name = 'Волгоград';
        $city->slug = 'volgograd';
        $city->region = 'Волгоградская область';
        $city->is_default = true;

        return $city;
    }
}
