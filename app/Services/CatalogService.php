<?php

namespace App\Services;

use App\Models\JobPlatform;
use App\Models\JobVacancy;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CatalogService
{
    private const TTL = 3600;

    public function categories(): Collection
    {
        return Cache::remember('catalog:categories', self::TTL, function () {
            return ProfessionCategory::query()->orderBy('name')->get();
        });
    }

    public function jobPlatforms(): Collection
    {
        return Cache::remember('catalog:job_platforms', self::TTL, function () {
            return JobPlatform::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }

    public function activeProfessions(): Collection
    {
        return Cache::remember('catalog:active_professions', self::TTL, function () {
            return Profession::query()
                ->with('category')
                ->where('is_active', true)
                ->get();
        });
    }

    public function professionOptions(): Collection
    {
        return Cache::remember('catalog:profession_options', self::TTL, function () {
            return Profession::query()
                ->with('category')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });
    }

    public function vacanciesForRecommendations(array $recommendations, int $cityId, int $perProfession = 2): array
    {
        $professionIds = collect($recommendations)
            ->pluck('profession_id')
            ->filter()
            ->unique()
            ->values();

        if ($professionIds->isEmpty()) {
            return [];
        }

        $vacancies = JobVacancy::query()
            ->where('is_active', true)
            ->whereIn('profession_id', $professionIds)
            ->where(function ($query) use ($cityId) {
                $query->where('city_id', $cityId)->orWhereNull('city_id');
            })
            ->orderBy('sort_order')
            ->get()
            ->groupBy('profession_id');

        $nameById = collect($recommendations)
            ->filter(fn ($item) => ! empty($item['profession_id']))
            ->keyBy('profession_id')
            ->map(fn ($item) => $item['profession_name'] ?? '');

        $slugById = collect($recommendations)
            ->filter(fn ($item) => ! empty($item['profession_id']))
            ->keyBy('profession_id')
            ->map(fn ($item) => $item['profession_slug'] ?? '');

        $samples = [];

        foreach ($professionIds as $professionId) {
            foreach (($vacancies->get($professionId) ?? collect())->take($perProfession) as $vacancy) {
                $samples[] = [
                    'profession_id' => $professionId,
                    'profession_name' => $nameById->get($professionId, $vacancy->profession?->name ?? ''),
                    'profession_slug' => $slugById->get($professionId, $vacancy->profession?->slug ?? ''),
                    'title' => $vacancy->title,
                    'company' => $vacancy->company,
                    'salary_text' => $vacancy->salary_text,
                    'experience_level' => $vacancy->experience_level,
                    'external_url' => $vacancy->external_url,
                    'source' => $vacancy->source,
                ];
            }
        }

        return $samples;
    }
}
