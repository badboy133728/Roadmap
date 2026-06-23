<?php

namespace Database\Seeders;

use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProfessionSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'it' => ['name' => 'IT и digital', 'icon' => '💻'],
            'medicine' => ['name' => 'Медицина', 'icon' => '🏥'],
            'engineering' => ['name' => 'Инженерия', 'icon' => '⚙️'],
            'education' => ['name' => 'Образование', 'icon' => '📚'],
            'trade' => ['name' => 'Торговля и услуги', 'icon' => '🛒'],
            'law' => ['name' => 'Госсектор и право', 'icon' => '⚖️'],
            'creative' => ['name' => 'Творчество и медиа', 'icon' => '🎨'],
            'production' => ['name' => 'Производство и АПК', 'icon' => '🏭'],
            'transport' => ['name' => 'Транспорт', 'icon' => '🚗'],
            'security' => ['name' => 'Безопасность', 'icon' => '🛡️'],
            'beauty' => ['name' => 'Красота и спорт', 'icon' => '💪'],
            'science' => ['name' => 'Наука', 'icon' => '🔬'],
        ];

        foreach ($categories as $slug => $meta) {
            ProfessionCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $meta['name'],
                    'icon' => $meta['icon'],
                    'description' => "Профессии в сфере «{$meta['name']}».",
                ]
            );
        }

        $data = require database_path('data/professions.php');

        foreach ($data as $categorySlug => $professions) {
            $category = ProfessionCategory::where('slug', $categorySlug)->first();

            if (! $category) {
                continue;
            }

            foreach ($professions as [$name, $description, $skills]) {
                $slug = Str::slug($name, '-', 'ru') ?: Str::slug(Str::ascii($name));

                Profession::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'description' => $description,
                        'skills' => $skills,
                        'outlook' => 'Спрос на специалистов сохраняется; перспективы зависят от региона и опыта.',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
