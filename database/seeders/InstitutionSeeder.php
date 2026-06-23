<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            'volgograd' => [
                ['Волгоградский государственный университет', 'university', 'https://vlsu.ru'],
                ['Волгоградский государственный технический университет', 'university', 'https://vstu.ru'],
                ['Волгоградский государственный медицинский университет', 'university', 'https://volgmed.ru'],
                ['Волгоградский колледж технологий и управления', 'college', null],
                ['Академия профессиональных технологий', 'courses', null],
            ],
            'astrakhan' => [
                ['Астраханский государственный технический университет', 'university', 'https://astu.org'],
                ['Астраханский государственный медицинский университет', 'university', 'https://astgmu.ru'],
                ['Астраханский колледж экономики и права', 'college', null],
                ['Каспийский институт морского и речного транспорта', 'college', null],
                ['Центр дополнительного образования', 'courses', null],
            ],
        ];

        foreach ($institutions as $citySlug => $list) {
            $city = City::where('slug', $citySlug)->first();

            if (! $city) {
                continue;
            }

            foreach ($list as [$name, $type, $website]) {
                Institution::updateOrCreate(
                    ['city_id' => $city->id, 'name' => $name],
                    ['type' => $type, 'website' => $website, 'address' => $city->name]
                );
            }
        }
    }
}
