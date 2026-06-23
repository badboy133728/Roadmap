<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Быстрый набор данных для деплоя (Railway).
 * Без JobVacancySeeder — он тяжёлый; вакансии можно досеять позже.
 */
class DeploySeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CitySeeder::class,
            ProfessionSeeder::class,
            CareerPathStepSeeder::class,
            InstitutionSeeder::class,
            EducationProgramSeeder::class,
            SalarySeeder::class,
            JobPlatformSeeder::class,
            QuizSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Лёгкие примеры вакансий только для популярных профессий
        $this->call(JobVacancySampleSeeder::class);
    }
}
