<?php

namespace App\Services;

use App\Models\Profession;
use App\Models\Quiz;
use App\Models\QuizOption;

class QuizScoringService
{
    private array $priorityInterestBoost = [
        'money' => ['it' => 2, 'law' => 1.5, 'trade' => 1.5, 'engineering' => 1],
        'creativity' => ['creative' => 3, 'beauty' => 2, 'it' => 1],
        'people' => ['medicine' => 3, 'education' => 2.5, 'trade' => 1],
        'stability' => ['law' => 2, 'security' => 2, 'production' => 1.5, 'medicine' => 1],
        'growth' => ['it' => 2, 'science' => 2, 'engineering' => 1.5, 'creative' => 1],
    ];

    private array $statusInterestBoost = [
        'school_9' => ['trade' => 1, 'beauty' => 1, 'transport' => 1, 'production' => 0.5],
        'school_11' => ['it' => 0.5, 'medicine' => 0.5, 'engineering' => 0.5, 'law' => 0.5],
        'student' => ['it' => 0.5, 'creative' => 0.5, 'trade' => 0.5],
        'working' => ['it' => 1, 'trade' => 1, 'creative' => 0.5],
    ];

    public function __construct(
        private PersonalityArchetypeService $archetypeService
    ) {}

    public function score(Quiz $quiz, array $answers, array $profile = []): array
    {
        $interestScores = [];
        $professionScores = [];

        foreach ($answers as $questionId => $optionId) {
            $option = QuizOption::where('quiz_question_id', $questionId)
                ->where('id', $optionId)
                ->first();

            if (! $option) {
                continue;
            }

            foreach ($option->interest_scores ?? [] as $slug => $points) {
                $interestScores[$slug] = ($interestScores[$slug] ?? 0) + $points;
            }

            foreach ($option->profession_scores ?? [] as $slug => $points) {
                $professionScores[$slug] = ($professionScores[$slug] ?? 0) + $points;
            }
        }

        $interestScores = $this->applyPersonalizationBoosts($interestScores, $profile);

        $professions = Profession::with('category')->where('is_active', true)->get();

        $ranked = $professions->map(function (Profession $profession) use ($professionScores, $interestScores, $profile) {
            $directScore = $professionScores[$profession->slug] ?? 0;
            $categoryScore = $interestScores[$profession->category?->slug ?? ''] ?? 0;
            $score = $directScore + ($categoryScore * 0.5);

            return [
                'profession' => $profession,
                'score' => $score,
                'reason' => $this->buildReason($profession, $directScore, $categoryScore, $profile),
            ];
        })
            ->sortByDesc('score')
            ->take(8)
            ->values();

        if ($ranked->first()['score'] <= 0) {
            $ranked = $professions->shuffle()->take(5)->map(fn ($profession) => [
                'profession' => $profession,
                'score' => 1,
                'reason' => 'Эта профессия популярна среди ребят твоего возраста — загляни подробнее.',
            ]);
        } else {
            $ranked = $ranked->filter(fn ($item) => $item['score'] > 0)->take(5)->values();
        }

        $maxScore = $ranked->max('score') ?: 1;
        $archetype = $this->archetypeService->resolve($interestScores);
        $status = $profile['status'] ?? 'exploring';

        return [
            'profile' => $profile,
            'interest_scores' => $interestScores,
            'interest_profile' => $this->archetypeService->interestProfile($interestScores),
            'archetype' => $archetype,
            'greeting' => $this->archetypeService->personalizedGreeting(
                $profile['name'] ?? null,
                $status
            ),
            'status_advice' => $this->archetypeService->statusAdvice(
                $status,
                $profile['name'] ?? null
            ),
            'recommendations' => $ranked->map(fn ($item) => [
                'profession_id' => $item['profession']->id,
                'profession_slug' => $item['profession']->slug,
                'profession_name' => $item['profession']->name,
                'category_name' => $item['profession']->category?->name,
                'category_icon' => $item['profession']->category?->icon,
                'score' => round($item['score'], 1),
                'match_percent' => $this->archetypeService->matchPercent($item['score'], $maxScore),
                'reason' => $item['reason'],
            ])->all(),
        ];
    }

    private function applyPersonalizationBoosts(array $interestScores, array $profile): array
    {
        foreach ($profile['priorities'] ?? [] as $priority) {
            foreach ($this->priorityInterestBoost[$priority] ?? [] as $slug => $boost) {
                $interestScores[$slug] = ($interestScores[$slug] ?? 0) + $boost;
            }
        }

        $status = $profile['status'] ?? null;
        foreach ($this->statusInterestBoost[$status] ?? [] as $slug => $boost) {
            $interestScores[$slug] = ($interestScores[$slug] ?? 0) + $boost;
        }

        return $interestScores;
    }

    private function buildReason(Profession $profession, float $direct, float $category, array $profile): string
    {
        $name = $profile['name'] ?? null;
        $you = $name ? "{$name}, т" : 'Т';
        $priorities = $profile['priorities'] ?? [];

        if ($direct >= 3) {
            return "{$you}ебе откликается именно «{$profession->name}» — твои ответы прямо на это указывают.";
        }

        if ($category >= 3) {
            $priorityHint = $this->priorityHint($priorities, $profession->category?->slug ?? '');

            return "{$you}ебе близка сфера «{$profession->category?->name}». «{$profession->name}» — сильный вариант{$priorityHint}.";
        }

        if (! empty($priorities)) {
            return "С учётом твоих приоритетов «{$profession->name}» выглядит перспективно — проверь путь и вакансии.";
        }

        return "По твоим ответам «{$profession->name}» выглядит перспективным направлением — стоит изучить подробнее.";
    }

    private function priorityHint(array $priorities, string $categorySlug): string
    {
        $map = [
            'money' => ['it', 'law', 'trade'],
            'creativity' => ['creative', 'beauty'],
            'people' => ['medicine', 'education'],
            'stability' => ['law', 'security', 'production'],
            'growth' => ['it', 'science', 'engineering'],
        ];

        foreach ($priorities as $priority) {
            if (in_array($categorySlug, $map[$priority] ?? [], true)) {
                return match ($priority) {
                    'money' => ' — совпадает с твоим запросом на доход',
                    'creativity' => ' — это про творчество, которое ты выбрал(а)',
                    'people' => ' — здесь ты сможешь помогать людям',
                    'stability' => ' — подходит под запрос на стабильность',
                    'growth' => ' — есть куда расти и развиваться',
                    default => '',
                };
            }
        }

        return '';
    }
}
