<?php

namespace App\Providers;

use App\Services\CityService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer([
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
