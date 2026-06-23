<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\EducationProgram;
use App\Models\Institution;
use App\Models\Profession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EducationProgramSeeder extends Seeder
{
  private array $categoryInstitutionTypes = [
    'it' => ['university', 'college', 'courses'],
    'medicine' => ['university'],
    'engineering' => ['university', 'college'],
    'education' => ['university', 'college'],
    'trade' => ['university', 'college', 'courses'],
    'law' => ['university', 'college'],
    'creative' => ['university', 'courses'],
    'production' => ['college', 'courses'],
    'transport' => ['college', 'courses'],
    'security' => ['college', 'courses'],
    'beauty' => ['college', 'courses'],
    'science' => ['university'],
  ];

  private array $categoryProgramMeta = [
    'it' => ['years' => 4, 'form' => 'очная', 'prefix' => 'Программная инженерия / '],
    'medicine' => ['years' => 6, 'form' => 'очная', 'prefix' => 'Медицина / '],
    'engineering' => ['years' => 4, 'form' => 'очная', 'prefix' => 'Инженерное дело / '],
    'education' => ['years' => 4, 'form' => 'очная', 'prefix' => 'Педагогика / '],
    'trade' => ['years' => 2.5, 'form' => 'очная', 'prefix' => 'Экономика и управление / '],
    'law' => ['years' => 4, 'form' => 'очная', 'prefix' => 'Юриспруденция / '],
    'creative' => ['years' => 4, 'form' => 'очная', 'prefix' => 'Медиа и дизайн / '],
    'production' => ['years' => 2, 'form' => 'очная', 'prefix' => 'Производство / '],
    'transport' => ['years' => 2, 'form' => 'очная', 'prefix' => 'Транспорт / '],
    'security' => ['years' => 2, 'form' => 'очная', 'prefix' => 'Безопасность / '],
    'beauty' => ['years' => 1, 'form' => 'очная', 'prefix' => 'Сфера услуг / '],
    'science' => ['years' => 4, 'form' => 'очная', 'prefix' => 'Естественные науки / '],
  ];

  public function run(): void
  {
    $cities = City::all();

    foreach ($cities as $city) {
      $institutions = Institution::where('city_id', $city->id)->get();

      Profession::with('category')->chunk(50, function ($professions) use ($city, $institutions) {
        foreach ($professions as $profession) {
          $categorySlug = $profession->category?->slug ?? 'trade';
          $types = $this->categoryInstitutionTypes[$categorySlug] ?? ['university', 'college'];
          $meta = $this->categoryProgramMeta[$categorySlug] ?? ['years' => 4, 'form' => 'очная', 'prefix' => ''];

          $matched = $institutions->filter(fn ($i) => in_array($i->type, $types))->take(2);

          if ($matched->isEmpty()) {
            $matched = $institutions->take(1);
          }

          foreach ($matched as $institution) {
            EducationProgram::updateOrCreate(
              [
                'institution_id' => $institution->id,
                'profession_id' => $profession->id,
              ],
              [
                'name' => $meta['prefix'] . $profession->name,
                'duration_years' => $meta['years'],
                'study_form' => $meta['form'],
              ]
            );
          }
        }
      });
    }
  }
}
