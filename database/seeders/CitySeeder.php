<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        City::upsert([
            ['name' => 'Волгоград', 'slug' => 'volgograd', 'region' => 'Волгоградская область', 'is_default' => true],
            ['name' => 'Астрахань', 'slug' => 'astrakhan', 'region' => 'Астраханская область', 'is_default' => false],
        ], ['slug'], ['name', 'region', 'is_default']);
    }
}
