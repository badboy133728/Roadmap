<?php

namespace App\Providers;

use App\Services\CatalogService;
use App\Services\CityService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CityService::class);
        $this->app->singleton(CatalogService::class);
    }

    public function boot(): void
    {
        View::composer([
            'layouts.public',
            'home',
            'professions.*',
            'quiz.*',
            'career-change.*',
        ], function ($view) {
            $data = $view->getData();

            if (! array_key_exists('currentCity', $data)) {
                $view->with('currentCity', $data['city'] ?? app(CityService::class)->current());
            }
        });
    }
}
