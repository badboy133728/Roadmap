<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class CityService
{
    public const COOKIE_NAME = 'selected_city';

    private ?City $currentCity = null;

    private ?Collection $allCities = null;

    public function current(): City
    {
        if ($this->currentCity) {
            return $this->currentCity;
        }

        try {
            $cities = $this->indexedCities();
            $slug = request()->cookie(self::COOKIE_NAME)
                ?? auth()->user()?->city?->slug
                ?? $cities->firstWhere('is_default', true)?->slug
                ?? 'volgograd';

            $this->currentCity = $cities->get($slug)
                ?? $cities->firstWhere('is_default', true)
                ?? $cities->first()
                ?? $this->fallbackCity();

            return $this->currentCity;
        } catch (\Throwable $e) {
            Log::warning('CityService::current failed', ['error' => $e->getMessage()]);

            return $this->fallbackCity();
        }
    }

    public function set(string $slug): City
    {
        $city = $this->indexedCities()->get($slug)
            ?? City::where('slug', $slug)->firstOrFail();

        $this->currentCity = $city;

        Cookie::queue(self::COOKIE_NAME, $city->slug, 60 * 24 * 365);

        if (auth()->check() && auth()->user()->city_id !== $city->id) {
            auth()->user()->update(['city_id' => $city->id]);
        }

        return $city;
    }

    public function all(): Collection
    {
        if ($this->allCities) {
            return $this->allCities;
        }

        try {
            $this->allCities = City::query()
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get();

            return $this->allCities;
        } catch (\Throwable $e) {
            Log::warning('CityService::all failed', ['error' => $e->getMessage()]);

            return collect([$this->fallbackCity()]);
        }
    }

    private function indexedCities(): Collection
    {
        return $this->all()->keyBy('slug');
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
