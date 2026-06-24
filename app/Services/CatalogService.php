<?php

namespace App\Services;

use App\Models\JobPlatform;
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
                ->with('category:id,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'category_id']);
        });
    }
}
