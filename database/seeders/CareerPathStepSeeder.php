<?php

namespace Database\Seeders;

use App\Models\CareerPathStep;
use App\Models\Profession;
use Illuminate\Database\Seeder;

class CareerPathStepSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['school', 'Среднее образование', 'Закончите школу, уделите внимание профильным предметам.', 132],
            ['college', 'Среднее профессиональное', 'Поступите в колледж или техникум по профильной специальности.', 24],
            ['university', 'Высшее образование', 'Получите диплом бакалавра или специалиста в вузе.', 48],
            ['courses', 'Дополнительные курсы', 'Пройдите курсы повышения квалификации или сертификации.', 6],
            ['practice', 'Практика и стажировка', 'Получите опыт на стажировке или в учебной практике.', 6],
            ['internship', 'Первое место работы', 'Начните карьеру на junior-позиции и набирайте опыт.', 12],
        ];

        Profession::chunk(50, function ($professions) use ($templates) {
            foreach ($professions as $profession) {
                if ($profession->careerPathSteps()->exists()) {
                    continue;
                }

                foreach ($templates as $order => [$type, $title, $description, $months]) {
                    CareerPathStep::create([
                        'profession_id' => $profession->id,
                        'sort_order' => $order + 1,
                        'step_type' => $type,
                        'title' => $title,
                        'description' => $description,
                        'duration_months' => $months,
                    ]);
                }
            }
        });
    }
}
