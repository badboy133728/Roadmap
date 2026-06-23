<?php

namespace Database\Seeders;

use App\Models\JobPlatform;
use Illuminate\Database\Seeder;

class JobPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'HeadHunter',
                'slug' => 'hh',
                'url' => 'https://hh.ru',
                'search_url_template' => 'https://hh.ru/search/vacancy?text={query}',
                'icon' => '🔴',
                'description' => 'Крупнейший сайт вакансий в России — удобные фильтры и отклики',
                'sort_order' => 1,
            ],
            [
                'name' => 'SuperJob',
                'slug' => 'superjob',
                'url' => 'https://www.superjob.ru',
                'search_url_template' => 'https://www.superjob.ru/vacancy/search/?keywords={query}',
                'icon' => '🟡',
                'description' => 'Много вакансий в регионах, в том числе в Волгограде и Астрахани',
                'sort_order' => 2,
            ],
            [
                'name' => 'Авито Работа',
                'slug' => 'avito',
                'url' => 'https://www.avito.ru/volgograd/vakansii',
                'search_url_template' => 'https://www.avito.ru/rossiya/vakansii?q={query}',
                'icon' => '🟢',
                'description' => 'Подработка, стажировки и вакансии без опыта — особенно для молодых',
                'sort_order' => 3,
            ],
            [
                'name' => 'Работа России',
                'slug' => 'trudvsem',
                'url' => 'https://trudvsem.ru',
                'search_url_template' => 'https://trudvsem.ru/vacancy/search?_title={query}',
                'icon' => '🇷🇺',
                'description' => 'Государственный портал — официальные вакансии и программы для молодёжи',
                'sort_order' => 4,
            ],
            [
                'name' => 'Habr Career',
                'slug' => 'habr-career',
                'url' => 'https://career.habr.com',
                'search_url_template' => 'https://career.habr.com/vacancies?q={query}',
                'icon' => '💻',
                'description' => 'IT-вакансии: разработка, дизайн, аналитика, тестирование',
                'sort_order' => 5,
            ],
        ];

        foreach ($platforms as $platform) {
            JobPlatform::updateOrCreate(
                ['slug' => $platform['slug']],
                array_merge($platform, ['is_active' => true])
            );
        }
    }
}
