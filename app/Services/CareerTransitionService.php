<?php

namespace App\Services;

use App\Models\CareerTransition;
use App\Models\Profession;
use Illuminate\Support\Collection;

class CareerTransitionService
{
    public function findOrBuild(?int $fromProfessionId, int $toProfessionId): array
    {
        $to = Profession::with(['careerPathSteps', 'category'])->findOrFail($toProfessionId);

        if (! $fromProfessionId) {
            return $this->buildFromScratch($to);
        }

        $from = Profession::with('category')->findOrFail($fromProfessionId);

        $transition = CareerTransition::with('steps')
            ->where('from_profession_id', $from->id)
            ->where('to_profession_id', $to->id)
            ->first();

        if ($transition) {
            return [
                'from' => $from,
                'to' => $to,
                'duration_months' => $transition->duration_months,
                'difficulty' => $transition->difficulty,
                'description' => $transition->description,
                'steps' => $transition->steps,
                'shared_skills' => $this->sharedSkills($from, $to),
                'missing_skills' => $this->missingSkills($from, $to),
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
            'duration_months' => $this->estimateDuration($from, $to),
            'difficulty' => $from->category_id === $to->category_id ? 'medium' : 'hard',
            'description' => "Переход из «{$from->name}» в «{$to->name}» потребует целенаправленного обучения и практики.",
            'steps' => $this->generateSteps($from, $to),
            'shared_skills' => $this->sharedSkills($from, $to),
            'missing_skills' => $this->missingSkills($from, $to),
        ];
    }

    private function buildFromScratch(Profession $to): array
    {
        return [
            'from' => null,
            'to' => $to,
            'duration_months' => $to->careerPathSteps->sum('duration_months') ?: 24,
            'difficulty' => 'easy',
            'description' => "Вы начинаете путь к профессии «{$to->name}» с нуля.",
            'steps' => $to->careerPathSteps,
            'shared_skills' => [],
            'missing_skills' => $to->skills ?? [],
        ];
    }

    private function sharedSkills(Profession $from, Profession $to): array
    {
        return array_values(array_intersect($from->skills ?? [], $to->skills ?? []));
    }

    private function missingSkills(Profession $from, Profession $to): array
    {
        return array_values(array_diff($to->skills ?? [], $from->skills ?? []));
    }

    private function estimateDuration(Profession $from, Profession $to): int
    {
        $missing = count($this->missingSkills($from, $to));

        return max(6, min(36, $missing * 3 + ($from->category_id === $to->category_id ? 6 : 12)));
    }

    private function generateSteps(Profession $from, Profession $to): Collection
    {
        $steps = collect([
            (object) [
                'title' => 'Оцените текущий опыт',
                'description' => "Проанализируйте навыки из профессии «{$from->name}», которые пригодятся в новой сфере.",
                'duration_months' => 1,
                'sort_order' => 1,
            ],
        ]);

        foreach ($this->missingSkills($from, $to) as $index => $skill) {
            $steps->push((object) [
                'title' => "Освойте: {$skill}",
                'description' => 'Пройдите курсы, прочитайте материалы и закрепите навык на практике.',
                'duration_months' => 2,
                'sort_order' => $index + 2,
            ]);
        }

        $steps->push((object) [
            'title' => "Практика и трудоустройство как {$to->name}",
            'description' => 'Соберите портфолио, пройдите стажировку и выходите на рынок труда.',
            'duration_months' => 3,
            'sort_order' => $steps->count() + 1,
        ]);

        return $steps;
    }
}
