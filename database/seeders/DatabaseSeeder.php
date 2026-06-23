<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
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
            JobVacancySeeder::class,
            QuizSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
