<?php

namespace App\Providers;

use App\Services\CityService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->configureRailwayDatabase();
    }

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

    private function configureRailwayDatabase(): void
    {
        $mysqlUrl = env('DATABASE_URL')
            ?: env('MYSQL_URL')
            ?: env('MYSQL_PRIVATE_URL');

        if ($mysqlUrl) {
            config(['database.connections.mysql.url' => $mysqlUrl]);
        }

        $host = env('MYSQLHOST') ?: env('DB_HOST');

        if ($host && $host !== '127.0.0.1') {
            config([
                'database.connections.mysql.host' => $host,
                'database.connections.mysql.port' => env('MYSQLPORT', env('DB_PORT', '3306')),
                'database.connections.mysql.database' => env('MYSQLDATABASE') ?: env('MYSQL_DATABASE') ?: env('DB_DATABASE'),
                'database.connections.mysql.username' => env('MYSQLUSER') ?: env('MYSQL_USER') ?: env('DB_USERNAME'),
                'database.connections.mysql.password' => env('MYSQLPASSWORD') ?: env('MYSQL_PASSWORD') ?: env('DB_PASSWORD'),
            ]);
        }
    }
}
