<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\JobVacancy;
use App\Models\Profession;
use Illuminate\Database\Seeder;

class JobVacancySampleSeeder extends Seeder
{
    public function run(): void
    {
        $slugs = ['programmist', 'vrach', 'uchitel', 'dizayner', 'buhgalter', 'marketolog'];
        $cities = City::all();

        foreach (Profession::whereIn('slug', $slugs)->get() as $profession) {
            foreach ($cities as $city) {
                $query = urlencode($profession->name . ' ' . $city->name);

                JobVacancy::updateOrCreate(
                    [
                        'profession_id' => $profession->id,
                        'city_id' => $city->id,
                        'title' => $profession->name,
                        'company' => 'Пример работодателя',
                    ],
                    [
                        'salary_text' => 'от 45 000 ₽',
                        'description' => 'Пример вакансии — нажми «Смотреть», откроется поиск на hh.ru.',
                        'external_url' => 'https://hh.ru/search/vacancy?text=' . $query,
                        'source' => 'hh.ru (пример)',
                        'experience_level' => 'junior',
                        'sort_order' => 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
