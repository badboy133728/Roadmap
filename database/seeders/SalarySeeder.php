<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Institution;
use App\Models\Profession;
use App\Models\Salary;
use Illuminate\Database\Seeder;

class SalarySeeder extends Seeder
{
    private array $baseSalaries = [
        'it' => ['junior' => [45000, 65000, 85000], 'middle' => [90000, 120000, 150000], 'senior' => [150000, 200000, 280000]],
        'medicine' => ['junior' => [35000, 50000, 65000], 'middle' => [60000, 85000, 110000], 'senior' => [100000, 140000, 180000]],
        'engineering' => ['junior' => [40000, 55000, 70000], 'middle' => [70000, 95000, 120000], 'senior' => [110000, 150000, 190000]],
        'education' => ['junior' => [28000, 38000, 48000], 'middle' => [45000, 60000, 75000], 'senior' => [65000, 85000, 105000]],
        'trade' => ['junior' => [30000, 42000, 55000], 'middle' => [50000, 70000, 90000], 'senior' => [80000, 110000, 140000]],
        'law' => ['junior' => [35000, 50000, 65000], 'middle' => [65000, 90000, 115000], 'senior' => [110000, 160000, 220000]],
        'creative' => ['junior' => [30000, 45000, 60000], 'middle' => [55000, 80000, 105000], 'senior' => [90000, 130000, 170000]],
        'production' => ['junior' => [35000, 48000, 60000], 'middle' => [55000, 75000, 95000], 'senior' => [85000, 110000, 140000]],
        'transport' => ['junior' => [40000, 55000, 70000], 'middle' => [60000, 80000, 100000], 'senior' => [90000, 120000, 150000]],
        'security' => ['junior' => [35000, 48000, 60000], 'middle' => [55000, 75000, 95000], 'senior' => [80000, 105000, 130000]],
        'beauty' => ['junior' => [25000, 38000, 50000], 'middle' => [45000, 65000, 85000], 'senior' => [70000, 100000, 130000]],
        'science' => ['junior' => [35000, 50000, 65000], 'middle' => [60000, 85000, 110000], 'senior' => [95000, 130000, 170000]],
    ];

    public function run(): void
    {
        $cities = City::all();
        $cityMultipliers = ['volgograd' => 1.0, 'astrakhan' => 0.95];

        Profession::with('category')->chunk(50, function ($professions) use ($cities, $cityMultipliers) {
            foreach ($professions as $profession) {
                $categorySlug = $profession->category?->slug ?? 'trade';
                $ranges = $this->baseSalaries[$categorySlug] ?? $this->baseSalaries['trade'];

                foreach ($cities as $city) {
                    $multiplier = $cityMultipliers[$city->slug] ?? 1.0;

                    foreach (['junior', 'middle', 'senior'] as $level) {
                        [$min, $median, $max] = $ranges[$level];
                        $variance = random_int(-3, 3) * 1000;

                        Salary::updateOrCreate(
                            [
                                'profession_id' => $profession->id,
                                'city_id' => $city->id,
                                'level' => $level,
                            ],
                            [
                                'salary_min' => (int) round(($min + $variance) * $multiplier),
                                'salary_median' => (int) round($median * $multiplier),
                                'salary_max' => (int) round(($max + $variance) * $multiplier),
                                'source' => 'hh.ru (ориентировочно)',
                                'updated_at_source' => now()->toDateString(),
                            ]
                        );
                    }
                }
            }
        });
    }
}
