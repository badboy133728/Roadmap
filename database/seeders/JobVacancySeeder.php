<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\JobVacancy;
use App\Models\Profession;
use Illuminate\Database\Seeder;

class JobVacancySeeder extends Seeder
{
    public function run(): void
    {
        $cities = City::all()->keyBy('slug');
        $templates = [
            'programmist' => [
                ['title' => 'Junior PHP-разработчик', 'company' => 'IT-компания «ВолгаСофт»', 'salary' => 'от 55 000 ₽', 'level' => 'junior'],
                ['title' => 'Стажёр-программист', 'company' => 'Цифровая лаборатория', 'salary' => 'от 35 000 ₽', 'level' => 'intern'],
                ['title' => 'Fullstack-разработчик', 'company' => 'Стартап «Курс»', 'salary' => 'от 90 000 ₽', 'level' => 'middle'],
            ],
            'vrach' => [
                ['title' => 'Врач-терапевт', 'company' => 'Городская поликлиника №1', 'salary' => 'от 65 000 ₽', 'level' => 'middle'],
                ['title' => 'Медсестра', 'company' => 'Частная клиника «Здоровье»', 'salary' => 'от 45 000 ₽', 'level' => 'junior'],
            ],
            'uchitel' => [
                ['title' => 'Учитель информатики', 'company' => 'Средняя школа №42', 'salary' => 'от 50 000 ₽', 'level' => 'middle'],
                ['title' => 'Воспитатель', 'company' => 'Детский сад «Радуга»', 'salary' => 'от 38 000 ₽', 'level' => 'junior'],
            ],
            'dizayner' => [
                ['title' => 'Графический дизайнер', 'company' => 'Студия «Пиксель»', 'salary' => 'от 50 000 ₽', 'level' => 'junior'],
                ['title' => 'UI/UX дизайнер', 'company' => 'Digital-агентство', 'salary' => 'от 70 000 ₽', 'level' => 'middle'],
            ],
            'buhgalter' => [
                ['title' => 'Бухгалтер', 'company' => 'ООО «РегионТорг»', 'salary' => 'от 55 000 ₽', 'level' => 'middle'],
                ['title' => 'Помощник бухгалтера', 'company' => 'Аутсорсинговая компания', 'salary' => 'от 40 000 ₽', 'level' => 'junior'],
            ],
            'logist' => [
                ['title' => 'Логист', 'company' => 'Транспортная компания', 'salary' => 'от 48 000 ₽', 'level' => 'junior'],
                ['title' => 'Специалист по ВЭД', 'company' => 'Импортёр «ВолгаТрейд»', 'salary' => 'от 65 000 ₽', 'level' => 'middle'],
            ],
            'marketolog' => [
                ['title' => 'SMM-менеджер', 'company' => 'Маркетинговое агентство', 'salary' => 'от 45 000 ₽', 'level' => 'junior'],
                ['title' => 'Интернет-маркетолог', 'company' => 'E-commerce проект', 'salary' => 'от 60 000 ₽', 'level' => 'middle'],
            ],
            'povar' => [
                ['title' => 'Повар', 'company' => 'Ресторан «Волга»', 'salary' => 'от 50 000 ₽', 'level' => 'middle'],
                ['title' => 'Помощник повара', 'company' => 'Кафе «Уют»', 'salary' => 'от 35 000 ₽', 'level' => 'junior'],
            ],
        ];

        $defaultTemplates = [
            ['title' => null, 'company' => 'Региональная компания', 'salary' => 'по договорённости', 'level' => 'junior'],
            ['title' => null, 'company' => 'Местный бизнес', 'salary' => 'от 30 000 ₽', 'level' => 'intern'],
        ];

        $professions = Profession::where('is_active', true)->get();

        foreach ($professions as $profession) {
            $items = $templates[$profession->slug] ?? $defaultTemplates;

            foreach ($cities as $city) {
                foreach ($items as $index => $item) {
                    $title = $item['title'] ?? $profession->name;
                    $query = urlencode($title . ' ' . $city->name);
                    $externalUrl = 'https://hh.ru/search/vacancy?text=' . $query;

                    JobVacancy::updateOrCreate(
                        [
                            'profession_id' => $profession->id,
                            'city_id' => $city->id,
                            'title' => $title,
                            'company' => $item['company'],
                        ],
                        [
                            'salary_text' => $item['salary'],
                            'description' => 'Пример вакансии для ориентира. Нажми «Смотреть» — откроется поиск на hh.ru в твоём городе.',
                            'external_url' => $externalUrl,
                            'source' => 'hh.ru (пример)',
                            'experience_level' => $item['level'],
                            'sort_order' => $index + 1,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
