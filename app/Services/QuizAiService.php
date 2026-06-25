<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuizAiService
{
    public function __construct(
        private CatalogService $catalog,
        private CityService $cities,
        private QuizProfileService $profiles,
    ) {}

    private array $statusLabels = [
        'school_9' => 'учится в 9 классе',
        'school_11' => 'учится в 10–11 классе',
        'student' => 'студент или учится в колледже',
        'working' => 'уже работает',
        'exploring' => 'ищет себя и варианты будущего',
    ];

    private array $priorityLabels = [
        'money' => 'высокий доход',
        'creativity' => 'творчество и самовыражение',
        'people' => 'помощь людям',
        'stability' => 'стабильность',
        'growth' => 'развитие и рост',
    ];

    public function isAvailable(): bool
    {
        if (! config('ai.enabled')) {
            return false;
        }

        return filled(config('ai.openai.api_key'));
    }

    public function isCompleteInsights(?array $insights, ?array $profile = null): bool
    {
        if (! $insights) {
            return false;
        }

        if (empty($insights['summary'])
            || ! isset($insights['personality_traits'])
            || ! isset($insights['first_steps'])) {
            return false;
        }

        if (! empty($profile['account']['is_registered']) && ! array_key_exists('vacancy_examples', $insights)) {
            return false;
        }

        return true;
    }

    public function buildQuizContext(Quiz $quiz, array $answers, array $profile, array $preview, array $aiAnswers = [], array $aiQuestions = []): array
    {
        $optionIds = array_map('intval', array_values($answers));

        $options = $optionIds
            ? QuizOption::query()->with('question:id,question')->whereIn('id', $optionIds)->get()
            : collect();

        $answerHighlights = $options->map(function (QuizOption $option) {
            return [
                'question' => $option->question?->question,
                'answer' => $option->text,
            ];
        })->filter(fn ($item) => $item['question'] && $item['answer'])->values()->all();

        $aiHighlights = $this->buildAiAnswerHighlights($aiQuestions, $aiAnswers);

        return [
            'name' => $profile['name'] ?? null,
            'status' => $profile['status'] ?? 'exploring',
            'status_label' => $this->statusLabels[$profile['status'] ?? 'exploring'] ?? 'ищет направление',
            'priorities' => $profile['priorities'] ?? [],
            'priority_labels' => collect($profile['priorities'] ?? [])
                ->map(fn ($p) => $this->priorityLabels[$p] ?? $p)
                ->values()
                ->all(),
            'about' => $profile['about'] ?? null,
            'archetype' => $preview['archetype'] ?? null,
            'interest_profile' => $preview['interest_profile'] ?? [],
            'interest_scores' => $preview['interest_scores'] ?? [],
            'recommendations' => collect($preview['recommendations'] ?? [])->take(5)->values()->all(),
            'answer_highlights' => $answerHighlights,
            'ai_answer_highlights' => $aiHighlights,
            'ai_questions' => $aiQuestions,
        ];
    }

    public function buildAdaptiveContext(
        array $profile,
        array $staticAnswers,
        array $aiQuestions,
        array $aiAnswers,
        array $preview,
        int $round,
    ): array {
        $staticHighlights = $this->buildStaticAnswerHighlights($staticAnswers);
        $aiHighlights = $this->buildAiAnswerHighlights($aiQuestions, $aiAnswers);
        $clarity = $preview['clarity'] ?? 50;

        return $this->enrichContextWithPersonalization([
            'name' => $profile['name'] ?? null,
            'status' => $profile['status'] ?? 'exploring',
            'status_label' => $this->statusLabels[$profile['status'] ?? 'exploring'] ?? 'ищет направление',
            'priorities' => $profile['priorities'] ?? [],
            'priority_labels' => collect($profile['priorities'] ?? [])
                ->map(fn ($p) => $this->priorityLabels[$p] ?? $p)
                ->values()
                ->all(),
            'about' => $profile['about'] ?? null,
            'archetype' => $preview['archetype'] ?? null,
            'interest_profile' => $preview['interest_profile'] ?? [],
            'interest_scores' => $preview['interest_scores'] ?? [],
            'recommendations' => collect($preview['recommendations'] ?? [])->take(5)->values()->all(),
            'clarity' => $clarity,
            'round' => $round,
            'asked_count' => count($aiQuestions),
            'answer_highlights' => $staticHighlights,
            'ai_answer_highlights' => $aiHighlights,
            'ai_questions' => $aiQuestions,
            'already_asked' => collect($aiQuestions)->pluck('question')->filter()->values()->all(),
        ], $profile);
    }

    private function enrichContextWithPersonalization(array $context, array $profile): array
    {
        $city = $this->cities->current();

        if (! empty($profile['account']['city_id'])) {
            $city = (object) [
                'id' => $profile['account']['city_id'],
                'name' => $profile['account']['city_name'] ?? $city->name,
                'region' => $profile['account']['city_region'] ?? $city->region,
            ];
        }

        $context['city'] = [
            'id' => $city->id,
            'name' => $city->name,
            'region' => $city->region,
        ];
        $context['account'] = $profile['account'] ?? null;
        $context['vacancy_samples'] = $this->catalog->vacanciesForRecommendations(
            $context['recommendations'] ?? [],
            (int) $city->id,
        );

        return $context;
    }

    private function formatAccountBlock(array $context): string
    {
        $account = $context['account'] ?? null;

        if (! is_array($account) || empty($account['is_registered'])) {
            return '';
        }

        $lines = ['Зарегистрированный пользователь (данные из профиля):'];

        if (! empty($account['city_name'])) {
            $lines[] = 'Город: ' . $account['city_name']
                . (! empty($account['city_region']) ? ' (' . $account['city_region'] . ')' : '');
        }

        if (! empty($account['current_profession'])) {
            $lines[] = 'Текущая/указанная профессия в профиле: ' . $account['current_profession'];
        }

        if (! empty($account['favorite_professions'])) {
            $lines[] = 'Избранные профессии: ' . implode(', ', $account['favorite_professions']);
        }

        if (! empty($account['email'])) {
            $lines[] = 'Email: ' . $account['email'];
        }

        return implode("\n", $lines);
    }

    private function formatVacanciesBlock(array $context): string
    {
        $samples = $context['vacancy_samples'] ?? [];

        if ($samples === []) {
            return 'Актуальные вакансии в базе: пока нет примеров для этого города.';
        }

        return "Актуальные вакансии в базе (используй в примерах и first_steps, не выдумывай другие):\n"
            . collect($samples)
                ->map(function ($item) {
                    $salary = $item['salary_text'] ? ', ' . $item['salary_text'] : '';

                    return '- [' . ($item['profession_name'] ?? 'Профессия') . '] '
                        . ($item['title'] ?? '') . ' — ' . ($item['company'] ?? 'компания не указана')
                        . $salary;
                })
                ->implode("\n");
    }

    private function normalizeVacancyExamples(array $fromAi, array $context): array
    {
        $samples = collect($context['vacancy_samples'] ?? []);

        if ($samples->isEmpty()) {
            return [];
        }

        $aiByTitle = collect($fromAi)
            ->filter(fn ($item) => is_array($item) && ! empty($item['title']))
            ->keyBy(fn ($item) => mb_strtolower(trim((string) $item['title'])));

        return $samples->map(function ($sample) use ($aiByTitle) {
            $key = mb_strtolower(trim((string) ($sample['title'] ?? '')));
            $aiNote = $aiByTitle->get($key);

            return [
                'profession_name' => $sample['profession_name'] ?? '',
                'profession_slug' => $sample['profession_slug'] ?? '',
                'title' => $sample['title'] ?? '',
                'company' => $sample['company'] ?? '',
                'salary_text' => $sample['salary_text'] ?? '',
                'experience_level' => $sample['experience_level'] ?? '',
                'external_url' => $sample['external_url'] ?? '',
                'why_relevant' => trim((string) ($aiNote['why_relevant'] ?? $aiNote['note'] ?? ''))
                    ?: 'Актуальная вакансия в твоём городе по направлению «' . ($sample['profession_name'] ?? '') . '».',
            ];
        })->values()->all();
    }

    public function generateAdaptiveQuestions(array $context): array
    {
        $clarity = (int) ($context['clarity'] ?? 50);
        $askedCount = (int) ($context['asked_count'] ?? 0);
        $round = (int) ($context['round'] ?? 0);
        $threshold = (int) config('ai.clarity_threshold', 72);
        $maxRounds = (int) config('ai.max_ai_rounds', 5);
        $maxTotal = (int) config('ai.max_ai_questions_total', 12);

        if ($clarity >= $threshold || $askedCount >= $maxTotal || $round >= $maxRounds) {
            return [
                'complete' => true,
                'questions' => [],
                'clarity' => $clarity,
                'message' => 'Профиль достаточно ясен — можно показать результат.',
            ];
        }

        $remaining = max(0, $maxTotal - $askedCount);
        $batchSize = match (true) {
            $clarity < 40 => 3,
            $clarity < 60 => 2,
            default => 1,
        };
        $batchSize = min($batchSize, $remaining);

        if ($batchSize === 0) {
            return [
                'complete' => true,
                'questions' => [],
                'clarity' => $clarity,
                'message' => 'Достигнут лимит вопросов.',
            ];
        }

        if ($this->isAvailable()) {
            try {
                $result = $this->generateAdaptiveWithApi($context, $batchSize);

                if ($result) {
                    return $result;
                }
            } catch (\Throwable $e) {
                Log::warning('QuizAiService adaptive failed', ['error' => $e->getMessage()]);
            }
        }

        return $this->generateAdaptiveLocal($context, $batchSize);
    }

    public function generateFollowUpQuestions(array $context): array
    {
        $count = (int) config('ai.follow_up_questions', 4);

        if ($this->isAvailable()) {
            try {
                $questions = $this->generateFollowUpWithApi($context, $count);

                if ($questions) {
                    return $questions;
                }
            } catch (\Throwable $e) {
                Log::warning('QuizAiService follow-up failed', ['error' => $e->getMessage()]);
            }
        }

        return $this->generateFollowUpLocal($context, $count);
    }

    public function generate(QuizResult $result, array $payload): array
    {
        $context = $this->buildContextFromResult($result, $payload);

        if ($this->isAvailable()) {
            try {
                $insights = $this->generateWithApi($context);

                if ($insights) {
                    return array_merge($insights, ['source' => 'ai']);
                }
            } catch (\Throwable $e) {
                Log::warning('QuizAiService API failed', ['error' => $e->getMessage()]);
            }
        }

        return array_merge($this->generateLocal($context), ['source' => 'local']);
    }

    private function buildContextFromResult(QuizResult $result, array $payload): array
    {
        $profile = $payload['profile'] ?? [];
        $staticAnswers = $result->answers ?? [];
        $preview = [
            'archetype' => $payload['archetype'] ?? null,
            'interest_profile' => $payload['interest_profile'] ?? [],
            'interest_scores' => $payload['interest_scores'] ?? [],
            'recommendations' => $payload['list'] ?? [],
        ];

        if ($result->user) {
            $profile = $this->profiles->mergeUser(
                $profile,
                $result->user->loadMissing(['city', 'currentProfession', 'favoriteProfessions']),
            );
        }

        return $this->buildAdaptiveContext(
            $profile,
            $staticAnswers,
            $payload['ai_questions'] ?? [],
            $payload['ai_answers'] ?? [],
            $preview,
            0,
        );
    }

    private function buildStaticAnswerHighlights(array $staticAnswers): array
    {
        $highlights = [];

        foreach (QuizStaticQuestions::all() as $question) {
            $questionId = $question['id'] ?? null;
            $selectedId = $staticAnswers[$questionId] ?? null;

            if (! $questionId || ! $selectedId) {
                continue;
            }

            $option = collect($question['options'] ?? [])
                ->first(fn ($item) => ($item['id'] ?? null) === $selectedId);

            if ($option) {
                $highlights[] = [
                    'question' => $question['question'] ?? '',
                    'answer' => $option['text'] ?? '',
                ];
            }
        }

        return $highlights;
    }

    private function generateAdaptiveWithApi(array $context, int $batchSize): ?array
    {
        $decoded = $this->callJsonApi(
            'Ты профориентолог для молодёжи 14–25 лет в России. Адаптивно задаёшь уточняющие вопросы: если интересы уже ясны — заверши опрос, если нет — задай 1–3 вопроса. Пиши на «ты», по-русски. Отвечай строго JSON.',
            $this->buildAdaptivePrompt($context, $batchSize),
        );

        if (! is_array($decoded)) {
            return null;
        }

        $clarity = (int) ($decoded['clarity'] ?? $context['clarity'] ?? 50);
        $complete = (bool) ($decoded['complete'] ?? false);

        if ($complete || empty($decoded['questions'])) {
            return [
                'complete' => true,
                'questions' => [],
                'clarity' => $clarity,
                'message' => $decoded['message'] ?? 'Достаточно данных для подбора.',
            ];
        }

        $questions = $this->normalizeAdaptiveQuestions(
            $decoded['questions'] ?? [],
            $batchSize,
            (int) ($context['round'] ?? 0),
            $context['already_asked'] ?? [],
        );

        if ($questions === []) {
            return [
                'complete' => true,
                'questions' => [],
                'clarity' => $clarity,
                'message' => 'Достаточно данных для подбора.',
            ];
        }

        return [
            'complete' => false,
            'questions' => $questions,
            'clarity' => $clarity,
            'message' => $decoded['message'] ?? null,
        ];
    }

    private function buildAdaptivePrompt(array $context, int $batchSize): string
    {
        $name = $context['name'] ? "Имя: {$context['name']}" : 'Имя не указано';
        $about = $context['about'] ? "О себе: {$context['about']}" : '';
        $priorities = $context['priority_labels']
            ? 'Приоритеты: ' . implode(', ', $context['priority_labels'])
            : '';

        $interests = collect($context['interest_profile'])
            ->map(fn ($item) => ($item['label'] ?? '') . ' (' . ($item['percent'] ?? 0) . '%)')
            ->implode(', ');

        $professions = collect($context['recommendations'])
            ->map(fn ($item, $i) => ($i + 1) . '. ' . ($item['profession_name'] ?? ''))
            ->implode("\n");

        $staticAnswers = collect($context['answer_highlights'])
            ->map(fn ($item) => "- {$item['question']} → {$item['answer']}")
            ->implode("\n");

        $aiAnswers = collect($context['ai_answer_highlights'] ?? [])
            ->map(fn ($item) => "- {$item['question']} → {$item['answer']}")
            ->implode("\n");

        $alreadyAsked = collect($context['already_asked'] ?? [])
            ->map(fn ($q) => "- {$q}")
            ->implode("\n") ?: 'пока нет';

        $clarity = $context['clarity'] ?? 50;
        $round = ($context['round'] ?? 0) + 1;
        $accountBlock = $this->formatAccountBlock($context);
        $cityLine = ! empty($context['city']['name'])
            ? 'Город для подбора: ' . $context['city']['name']
            : '';
        $accountSection = $accountBlock !== '' ? "\n{$accountBlock}\n" : '';

        return <<<PROMPT
Оцени, насколько ясны интересы человека (clarity 0–100). Сейчас clarity ≈ {$clarity}.
Раунд уточнения: {$round}. Можно задать до {$batchSize} вопросов в этом раунде.

{$name}
Статус: {$context['status_label']}
{$cityLine}
{$priorities}
{$about}
{$accountSection}
Интересы: {$interests}

Предварительный топ профессий:
{$professions}

Ответы на 2 базовых вопроса:
{$staticAnswers}

Уже заданные уточняющие вопросы и ответы:
{$aiAnswers}

Тексты уже заданных вопросов (не повторять):
{$alreadyAsked}

Если интересы уже достаточно ясны (противоречий мало, топ-профессии отличаются) — верни complete: true и questions: [].
Если нужно уточнить — верни complete: false и 1–{$batchSize} новых вопросов, которые различают топ-профессии.

JSON:
{
  "complete": false,
  "clarity": 65,
  "message": "короткое пояснение для пользователя",
  "questions": [
    {
      "id": "ai_r{$round}_q1",
      "question": "текст",
      "emoji": "💡",
      "hint": "зачем вопрос",
      "options": [
        {
          "id": "ai_r{$round}_q1_a",
          "text": "вариант",
          "interest_scores": {"it": 2},
          "profession_scores": {"programmist": 1}
        }
      ]
    }
  ]
}

Правила:
- У каждого вопроса 3–4 варианта ответа
- interest_scores: it, medicine, engineering, education, trade, law, creative, production, transport, security, beauty, science
- profession_scores по желанию
- Вопросы персональные, не дублируй уже заданные
- Если есть данные профиля — учитывай город и текущую профессию при уточнении
PROMPT;
    }

    private function normalizeAdaptiveQuestions(array $questions, int $batchSize, int $round, array $alreadyAsked): array
    {
        $asked = collect($alreadyAsked)->map(fn ($q) => mb_strtolower(trim($q)))->all();

        return collect($questions)
            ->filter(fn ($q) => is_array($q) && ! empty($q['question']) && ! empty($q['options']))
            ->reject(fn ($q) => in_array(mb_strtolower(trim((string) $q['question'])), $asked, true))
            ->take($batchSize)
            ->values()
            ->map(function ($question, $index) use ($round) {
                $id = $question['id'] ?? 'ai_r' . ($round + 1) . '_q' . ($index + 1);

                return [
                    'id' => $id,
                    'question' => trim((string) $question['question']),
                    'emoji' => $question['emoji'] ?? '💡',
                    'hint' => $question['hint'] ?? 'Это поможет точнее подобрать профессию',
                    'options' => collect($question['options'] ?? [])
                        ->filter(fn ($o) => is_array($o) && ! empty($o['text']))
                        ->take(4)
                        ->values()
                        ->map(function ($option, $optIndex) use ($id) {
                            return [
                                'id' => $option['id'] ?? "{$id}_" . chr(97 + $optIndex),
                                'text' => trim((string) $option['text']),
                                'interest_scores' => (array) ($option['interest_scores'] ?? []),
                                'profession_scores' => (array) ($option['profession_scores'] ?? []),
                            ];
                        })
                        ->all(),
                ];
            })
            ->filter(fn ($q) => count($q['options']) >= 2)
            ->values()
            ->all();
    }

    private function generateAdaptiveLocal(array $context, int $batchSize): array
    {
        $clarity = (int) ($context['clarity'] ?? 50);
        $threshold = (int) config('ai.clarity_threshold', 72);
        $askedIds = collect($context['ai_questions'] ?? [])->pluck('id')->all();
        $askedTexts = collect($context['already_asked'] ?? [])->map(fn ($q) => mb_strtolower(trim($q)))->all();

        if ($clarity >= $threshold) {
            return [
                'complete' => true,
                'questions' => [],
                'clarity' => $clarity,
                'message' => 'Профиль достаточно ясен.',
            ];
        }

        $pool = collect($this->localQuestionPool($context))
            ->reject(fn ($item) => in_array($item['id'], $askedIds, true))
            ->reject(fn ($item) => in_array(mb_strtolower(trim($item['question'] ?? '')), $askedTexts, true));

        $topInterests = collect($context['interest_profile'])->take(3)->pluck('slug')->all();
        $priorities = $context['priorities'] ?? [];
        $status = $context['status'] ?? 'exploring';

        $selected = $pool
            ->filter(function ($item) use ($topInterests, $priorities, $status) {
                $tags = $item['tags'] ?? [];

                return empty($tags)
                    || ! empty(array_intersect($tags, $topInterests))
                    || ! empty(array_intersect($tags, $priorities))
                    || in_array($status, $tags, true);
            })
            ->shuffle()
            ->take($batchSize)
            ->values();

        if ($selected->count() < $batchSize) {
            $selected = $selected->merge(
                $pool->shuffle()->take($batchSize - $selected->count())
            );
        }

        $round = (int) ($context['round'] ?? 0);
        $questions = $selected->take($batchSize)->values()->map(function ($question, $index) use ($round) {
            $id = 'ai_r' . ($round + 1) . '_q' . ($index + 1);

            return [
                'id' => $id,
                'question' => $question['question'],
                'emoji' => $question['emoji'] ?? '💡',
                'hint' => $question['hint'] ?? 'Это поможет точнее подобрать профессию',
                'options' => collect($question['options'] ?? [])
                    ->map(function ($option, $optIndex) use ($id) {
                        return [
                            'id' => $option['id'] ?? "{$id}_" . chr(97 + $optIndex),
                            'text' => $option['text'],
                            'interest_scores' => (array) ($option['interest_scores'] ?? []),
                            'profession_scores' => (array) ($option['profession_scores'] ?? []),
                        ];
                    })
                    ->all(),
            ];
        })->all();

        if ($questions === []) {
            return [
                'complete' => true,
                'questions' => [],
                'clarity' => $clarity,
                'message' => 'Достаточно данных для подбора.',
            ];
        }

        return [
            'complete' => false,
            'questions' => $questions,
            'clarity' => $clarity,
            'message' => 'Ещё пара вопросов — так подбор будет точнее.',
        ];
    }

    private function buildAiAnswerHighlights(array $aiQuestions, array $aiAnswers): array
    {
        $highlights = [];

        foreach ($aiQuestions as $question) {
            $questionId = $question['id'] ?? null;
            $selectedId = $aiAnswers[$questionId] ?? null;

            if (! $questionId || ! $selectedId) {
                continue;
            }

            $option = collect($question['options'] ?? [])
                ->first(fn ($item) => ($item['id'] ?? null) === $selectedId);

            if ($option) {
                $highlights[] = [
                    'question' => $question['question'] ?? '',
                    'answer' => $option['text'] ?? '',
                ];
            }
        }

        return $highlights;
    }

    private function generateFollowUpWithApi(array $context, int $count): ?array
    {
        $prompt = $this->buildFollowUpPrompt($context, $count);
        $decoded = $this->callJsonApi(
            'Ты профориентолог для молодёжи 14–25 лет в России. Генерируешь уточняющие вопросы, чтобы точнее подобрать профессию. Пиши на «ты», по-русски, без канцелярита. Отвечай строго JSON.',
            $prompt,
        );

        if (! is_array($decoded)) {
            return null;
        }

        return $this->normalizeFollowUpQuestions($decoded['questions'] ?? [], $count);
    }

    private function buildFollowUpPrompt(array $context, int $count): string
    {
        $name = $context['name'] ? "Имя: {$context['name']}" : 'Имя не указано';
        $about = $context['about'] ? "О себе: {$context['about']}" : '';
        $priorities = $context['priority_labels']
            ? 'Приоритеты: ' . implode(', ', $context['priority_labels'])
            : '';

        $interests = collect($context['interest_profile'])
            ->map(fn ($item) => ($item['label'] ?? '') . ' (' . ($item['percent'] ?? 0) . '%)')
            ->implode(', ');

        $professions = collect($context['recommendations'])
            ->map(fn ($item, $i) => ($i + 1) . '. ' . ($item['profession_name'] ?? ''))
            ->implode("\n");

        $answers = collect($context['answer_highlights'])
            ->map(fn ($item) => "- {$item['question']} → {$item['answer']}")
            ->implode("\n");

        return <<<PROMPT
На основе ответов сгенерируй {$count} УТОЧНЯЮЩИХ вопроса, чтобы точнее выбрать профессию.
Вопросы должны различать между топ-профессиями и учитывать противоречия в ответах.

{$name}
Статус: {$context['status_label']}
{$priorities}
{$about}
Интересы: {$interests}

Предварительный топ профессий:
{$professions}

Ответы на основной тест:
{$answers}

Верни JSON:
{
  "questions": [
    {
      "id": "ai_q1",
      "question": "текст вопроса",
      "emoji": "💡",
      "hint": "зачем этот вопрос",
      "options": [
        {
          "id": "ai_q1_a",
          "text": "вариант ответа",
          "interest_scores": {"it": 2, "creative": 0},
          "profession_scores": {"programmist": 2}
        }
      ]
    }
  ]
}

Правила:
- Ровно {$count} вопроса, у каждого 3–4 варианта ответа
- id вопросов: ai_q1, ai_q2...
- id вариантов: ai_q1_a, ai_q1_b...
- interest_scores: slug сферы → баллы (it, medicine, engineering, education, trade, law, creative, production, transport, security, beauty, science)
- profession_scores: slug профессии на латинице/транслите (programmist, vrach, dizayner...) — необязательно, но желательно
- Вопросы персональные, не повторяй дословно основной тест
PROMPT;
    }

    private function normalizeFollowUpQuestions(array $questions, int $count): array
    {
        return collect($questions)
            ->filter(fn ($q) => is_array($q) && ! empty($q['question']) && ! empty($q['options']))
            ->take($count)
            ->values()
            ->map(function ($question, $index) {
                $id = $question['id'] ?? 'ai_q' . ($index + 1);

                return [
                    'id' => $id,
                    'question' => trim((string) $question['question']),
                    'emoji' => $question['emoji'] ?? '💡',
                    'hint' => $question['hint'] ?? 'Это поможет точнее подобрать профессию',
                    'options' => collect($question['options'] ?? [])
                        ->filter(fn ($o) => is_array($o) && ! empty($o['text']))
                        ->take(4)
                        ->values()
                        ->map(function ($option, $optIndex) use ($id) {
                            return [
                                'id' => $option['id'] ?? "{$id}_" . chr(97 + $optIndex),
                                'text' => trim((string) $option['text']),
                                'interest_scores' => (array) ($option['interest_scores'] ?? []),
                                'profession_scores' => (array) ($option['profession_scores'] ?? []),
                            ];
                        })
                        ->all(),
                ];
            })
            ->filter(fn ($q) => count($q['options']) >= 2)
            ->values()
            ->all();
    }

    private function generateFollowUpLocal(array $context, int $count): array
    {
        $pool = $this->localQuestionPool($context);
        $topInterests = collect($context['interest_profile'])->take(3)->pluck('slug')->all();
        $priorities = $context['priorities'] ?? [];
        $status = $context['status'] ?? 'exploring';

        $selected = collect($pool)
            ->filter(function ($item) use ($topInterests, $priorities, $status) {
                $tags = $item['tags'] ?? [];

                return empty($tags)
                    || ! empty(array_intersect($tags, $topInterests))
                    || ! empty(array_intersect($tags, $priorities))
                    || in_array($status, $tags, true);
            })
            ->shuffle()
            ->take($count)
            ->values();

        if ($selected->count() < $count) {
            $selected = $selected->merge(
                collect($pool)->shuffle()->take($count - $selected->count())
            );
        }

        return $selected->take($count)->values()->all();
    }

    private function localQuestionPool(array $context): array
    {
        $name = $context['name'] ?? 'ты';

        return [
            [
                'id' => 'ai_q1',
                'question' => "{$name}, что тебе ближе в работе?",
                'emoji' => '⚡',
                'hint' => 'Поможет понять формат деятельности',
                'tags' => ['it', 'engineering', 'creative'],
                'options' => [
                    ['id' => 'ai_q1_a', 'text' => 'Решать логические задачи и разбираться в системах', 'interest_scores' => ['it' => 3, 'science' => 1]],
                    ['id' => 'ai_q1_b', 'text' => 'Создавать что-то руками или на практике', 'interest_scores' => ['engineering' => 3, 'production' => 2]],
                    ['id' => 'ai_q1_c', 'text' => 'Придумывать идеи, истории, визуал', 'interest_scores' => ['creative' => 3, 'beauty' => 1]],
                    ['id' => 'ai_q1_d', 'text' => 'Общаться и помогать людям напрямую', 'interest_scores' => ['medicine' => 2, 'education' => 2, 'trade' => 1]],
                ],
            ],
            [
                'id' => 'ai_q2',
                'question' => 'Какой график и ритм тебе комфортнее?',
                'emoji' => '🕐',
                'hint' => 'У разных профессий разный режим',
                'tags' => ['stability', 'money', 'growth'],
                'options' => [
                    ['id' => 'ai_q2_a', 'text' => 'Чёткий график 5/2, всё предсказуемо', 'interest_scores' => ['law' => 2, 'medicine' => 1, 'security' => 2]],
                    ['id' => 'ai_q2_b', 'text' => 'Гибкий график, можно работать удалённо', 'interest_scores' => ['it' => 3, 'creative' => 1]],
                    ['id' => 'ai_q2_c', 'text' => 'Сменный или проектный — главное интересно', 'interest_scores' => ['production' => 2, 'transport' => 2, 'engineering' => 1]],
                    ['id' => 'ai_q2_d', 'text' => 'Готов(а) много учиться ради роста', 'interest_scores' => ['science' => 2, 'it' => 2, 'medicine' => 1]],
                ],
            ],
            [
                'id' => 'ai_q3',
                'question' => 'Что для тебя важнее в будущей работе?',
                'emoji' => '🎯',
                'hint' => 'Уточняем твои приоритеты',
                'tags' => ['money', 'creativity', 'people'],
                'options' => [
                    ['id' => 'ai_q3_a', 'text' => 'Высокая зарплата и карьерный рост', 'interest_scores' => ['it' => 2, 'law' => 2, 'trade' => 2]],
                    ['id' => 'ai_q3_b', 'text' => 'Творческая свобода и самовыражение', 'interest_scores' => ['creative' => 3, 'beauty' => 1]],
                    ['id' => 'ai_q3_c', 'text' => 'Видеть, как моя работа помогает людям', 'interest_scores' => ['medicine' => 3, 'education' => 2]],
                    ['id' => 'ai_q3_d', 'text' => 'Стабильность и уверенность в завтрашнем дне', 'interest_scores' => ['law' => 2, 'security' => 2, 'production' => 1]],
                ],
            ],
            [
                'id' => 'ai_q4',
                'question' => 'Как ты предпочитаешь учиться новому?',
                'emoji' => '📚',
                'hint' => 'Подберём реалистичный путь',
                'tags' => ['school_9', 'school_11', 'student'],
                'options' => [
                    ['id' => 'ai_q4_a', 'text' => 'Сам(а) — видео, курсы, практика', 'interest_scores' => ['it' => 2, 'creative' => 1]],
                    ['id' => 'ai_q4_b', 'text' => 'В колледже — быстрее выйти на работу', 'interest_scores' => ['production' => 2, 'beauty' => 2, 'trade' => 1]],
                    ['id' => 'ai_q4_c', 'text' => 'В вузе — глубокие знания и диплом', 'interest_scores' => ['medicine' => 2, 'engineering' => 2, 'science' => 2]],
                    ['id' => 'ai_q4_d', 'text' => 'Через наставника или стажировку', 'interest_scores' => ['trade' => 2, 'education' => 1]],
                ],
            ],
            [
                'id' => 'ai_q5',
                'question' => 'Какая задача звучит интереснее?',
                'emoji' => '🧩',
                'hint' => 'Проверяем, что реально зажигает',
                'tags' => ['it', 'creative', 'science'],
                'options' => [
                    ['id' => 'ai_q5_a', 'text' => 'Написать код или настроить приложение', 'interest_scores' => ['it' => 3], 'profession_scores' => ['programmist' => 2, 'frontend-razrabotchik' => 2]],
                    ['id' => 'ai_q5_b', 'text' => 'Провести эксперимент или исследование', 'interest_scores' => ['science' => 3], 'profession_scores' => ['uchenyy' => 2]],
                    ['id' => 'ai_q5_c', 'text' => 'Оформить бренд, сайт или контент', 'interest_scores' => ['creative' => 3], 'profession_scores' => ['dizayner' => 2, 'smm-menedzher' => 1]],
                    ['id' => 'ai_q5_d', 'text' => 'Организовать команду или проект', 'interest_scores' => ['trade' => 2, 'law' => 1], 'profession_scores' => ['menedzher' => 2]],
                ],
            ],
            [
                'id' => 'ai_q6',
                'question' => 'Где ты видишь себя через 5 лет?',
                'emoji' => '🔮',
                'hint' => 'Долгосрочное видение помогает с выбором',
                'tags' => ['working', 'exploring', 'growth'],
                'options' => [
                    ['id' => 'ai_q6_a', 'text' => 'Эксперт в технической сфере', 'interest_scores' => ['it' => 2, 'engineering' => 2]],
                    ['id' => 'ai_q6_b', 'text' => 'Руковожу людьми или своим делом', 'interest_scores' => ['trade' => 3, 'law' => 1]],
                    ['id' => 'ai_q6_c', 'text' => 'Помогаю людям как специалист', 'interest_scores' => ['medicine' => 2, 'education' => 2]],
                    ['id' => 'ai_q6_d', 'text' => 'Создаю что-то своё — продукт, арт, медиа', 'interest_scores' => ['creative' => 3, 'it' => 1]],
                ],
            ],
        ];
    }

    private function generateWithApi(array $context): ?array
    {
        $decoded = $this->callJsonApi(
            'Ты дружелюбный профориентолог для подростков и молодёжи в России (14–25 лет). Пиши на русском, обращайся на «ты», без канцелярита. Дай подробный, глубокий разбор. Отвечай строго JSON без markdown.',
            $this->buildInsightsPrompt($context),
        );

        if (! is_array($decoded)) {
            return null;
        }

        return $this->normalizeInsights($decoded, $context);
    }

    private function callJsonApi(string $system, string $userPrompt): ?array
    {
        $response = Http::withToken(config('ai.openai.api_key'))
            ->timeout(config('ai.openai.timeout'))
            ->post(rtrim(config('ai.openai.base_url'), '/') . '/chat/completions', [
                'model' => config('ai.openai.model'),
                'temperature' => 0.85,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI HTTP error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function buildInsightsPrompt(array $context): string
    {
        $name = $context['name'] ? "Имя: {$context['name']}" : 'Имя не указано';
        $about = $context['about'] ? "О себе: {$context['about']}" : '';
        $priorities = $context['priority_labels']
            ? 'Приоритеты: ' . implode(', ', $context['priority_labels'])
            : '';

        $archetype = $context['archetype'];
        $archetypeLine = $archetype
            ? "Тип: {$archetype['title']} — {$archetype['tagline']}. {$archetype['description']}"
            : '';

        $interests = collect($context['interest_profile'])
            ->map(fn ($item) => ($item['label'] ?? '') . ' (' . ($item['percent'] ?? 0) . '%)')
            ->implode(', ');

        $professions = collect($context['recommendations'])
            ->map(fn ($item, $i) => ($i + 1) . '. ' . ($item['profession_name'] ?? '') . ' — ' . ($item['reason'] ?? ''))
            ->implode("\n");

        $answers = collect($context['answer_highlights'])
            ->map(fn ($item) => "- {$item['question']} → {$item['answer']}")
            ->implode("\n");

        $aiAnswers = collect($context['ai_answer_highlights'] ?? [])
            ->map(fn ($item) => "- [уточнение] {$item['question']} → {$item['answer']}")
            ->implode("\n");

        $accountBlock = $this->formatAccountBlock($context);
        $vacanciesBlock = $this->formatVacanciesBlock($context);
        $cityLine = ! empty($context['city']['name'])
            ? 'Город: ' . $context['city']['name'] . (! empty($context['city']['region']) ? ' (' . $context['city']['region'] . ')' : '')
            : '';
        $accountSection = $accountBlock !== '' ? "\n{$accountBlock}\n" : '';
        $registeredHint = ! empty($context['account']['is_registered'])
            ? "\nЭто зарегистрированный пользователь — путь к профессии должен быть конкретнее: учитывай город, текущую профессию из профиля и реальные вакансии ниже.\n"
            : '';

        return <<<PROMPT
Сделай ПОДРОБНЫЙ персональный профориентационный разбор.

{$name}
Статус: {$context['status_label']}
{$cityLine}
{$priorities}
{$about}
{$accountSection}{$registeredHint}
{$archetypeLine}
Интерес-профиль: {$interests}

Топ-профессии:
{$professions}

{$vacanciesBlock}

Ответы на 2 базовых вопроса:
{$answers}

Ответы на уточняющие ИИ-вопросы:
{$aiAnswers}

Верни JSON:
{
  "summary": "4-6 предложений — глубокий портрет личности, стиль мышления, мотивация",
  "personality_traits": ["черта 1", "черта 2", "черта 3", "черта 4"],
  "work_style": "2-3 предложения — как тебе комфортнее работать",
  "strengths": ["сильная сторона 1", "...", "..."],
  "growth_areas": ["над чем стоит поработать 1", "..."],
  "skills_to_learn": ["навык 1", "навык 2", "навык 3"],
  "profession_notes": [
    {
      "profession_name": "из списка",
      "note": "3-4 предложения — почему подходит именно этому человеку",
      "pros": ["плюс 1", "плюс 2"],
      "cons": ["минус/риск 1"],
      "fit_score": 85
    }
  ],
  "vacancy_examples": [
    {
      "profession_name": "из списка вакансий",
      "title": "точное название вакансии из списка",
      "why_relevant": "1-2 предложения — почему эта вакансия подходит этому человеку"
    }
  ],
  "less_suitable": [
    {"area": "сфера или тип работы", "reason": "почему менее подходит"}
  ],
  "education_path": "2-3 предложения — колледж/вуз/курсы с учётом статуса и города",
  "first_steps": ["конкретный шаг 1", "шаг 2", "шаг 3", "шаг 4", "шаг 5"],
  "personal_advice": "развёрнутый совет на 3-4 предложения",
  "motivation": "мотивирующая фраза"
}

profession_notes — для каждой профессии из топ-списка. fit_score 55-99.
vacancy_examples — 2-4 вакансии ТОЛЬКО из списка «Актуальные вакансии» выше; не придумывай компании и зарплаты.
В first_steps включи хотя бы один шаг с реальной вакансией из списка (название + компания).
PROMPT;
    }

    private function normalizeInsights(array $decoded, array $context): array
    {
        $recommendations = collect($context['recommendations']);

        $notes = collect($decoded['profession_notes'] ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['profession_name']))
            ->take(5)
            ->map(fn ($item) => [
                'profession_name' => $item['profession_name'],
                'note' => trim((string) ($item['note'] ?? '')),
                'pros' => array_values(array_filter(array_map('strval', $item['pros'] ?? []))),
                'cons' => array_values(array_filter(array_map('strval', $item['cons'] ?? []))),
                'fit_score' => (int) ($item['fit_score'] ?? 0),
            ])
            ->values()
            ->all();

        if (empty($notes)) {
            $notes = $recommendations->map(fn ($item) => [
                'profession_name' => $item['profession_name'] ?? '',
                'note' => $item['reason'] ?? '',
                'pros' => [],
                'cons' => [],
                'fit_score' => $item['match_percent'] ?? 70,
            ])->all();
        }

        return [
            'summary' => trim((string) ($decoded['summary'] ?? '')),
            'personality_traits' => $this->stringList($decoded['personality_traits'] ?? []),
            'work_style' => trim((string) ($decoded['work_style'] ?? '')),
            'strengths' => $this->stringList($decoded['strengths'] ?? []),
            'growth_areas' => $this->stringList($decoded['growth_areas'] ?? []),
            'skills_to_learn' => $this->stringList($decoded['skills_to_learn'] ?? []),
            'profession_notes' => $notes,
            'less_suitable' => collect($decoded['less_suitable'] ?? [])
                ->filter(fn ($item) => is_array($item) && ! empty($item['area']))
                ->take(3)
                ->values()
                ->all(),
            'education_path' => trim((string) ($decoded['education_path'] ?? '')),
            'first_steps' => $this->stringList($decoded['first_steps'] ?? []),
            'personal_advice' => trim((string) ($decoded['personal_advice'] ?? '')),
            'motivation' => trim((string) ($decoded['motivation'] ?? '')),
            'vacancy_examples' => $this->normalizeVacancyExamples($decoded['vacancy_examples'] ?? [], $context),
        ];
    }

    private function stringList(array $items): array
    {
        return array_values(array_filter(
            array_map('strval', $items),
            fn ($s) => $s !== '',
        ));
    }

    private function generateLocal(array $context): array
    {
        $name = $context['name'];
        $greeting = $name ? "{$name}, " : '';
        $archetype = $context['archetype'];
        $topInterest = $context['interest_profile'][0] ?? null;
        $secondInterest = $context['interest_profile'][1] ?? null;
        $priorities = $context['priority_labels'];

        $summaryParts = [];

        if ($archetype) {
            $summaryParts[] = "{$greeting}ты явно тянешься к типу «{$archetype['title']}». {$archetype['description']}";
        }

        if ($topInterest) {
            $summaryParts[] = "Сильнейший интерес — «{$topInterest['label']}» ({$topInterest['percent']}%).";
            if ($secondInterest && $secondInterest['percent'] >= 30) {
                $summaryParts[] = "На втором месте «{$secondInterest['label']}» ({$secondInterest['percent']}%) — можно искать профессии на стыке.";
            }
        }

        if ($context['about']) {
            $summaryParts[] = "Ты написал(а) о себе: «{$context['about']}» — это важная подсказка.";
        }

        if (! empty($context['ai_answer_highlights'])) {
            $aiSample = $context['ai_answer_highlights'][0]['answer'] ?? '';
            $summaryParts[] = "На уточняющие вопросы ты ответил(а), в том числе: «{$aiSample}» — это уточнило подбор.";
        }

        if (! empty($context['account']['is_registered'])) {
            $cityName = $context['city']['name'] ?? $context['account']['city_name'] ?? null;
            if ($cityName) {
                $summaryParts[] = "Учитываем твой профиль и город {$cityName} — рекомендации привязаны к региону.";
            }
            if (! empty($context['account']['current_profession'])) {
                $summaryParts[] = 'Сейчас в профиле указана профессия «'
                    . $context['account']['current_profession']
                    . '» — разбор учитывает возможный переход или развитие.';
            }
        }

        $workStyle = match (true) {
            in_array('creativity', $context['priorities'] ?? [], true) => 'Тебе важна свобода и возможность проявлять себя — ищи проекты, где есть пространство для идей, а не только регламент.',
            in_array('stability', $context['priorities'] ?? [], true) => 'Тебе комфортнее, когда правила ясны и есть понятный карьерный трек — обрати внимание на профессии с прозрачной квалификацией.',
            in_array('people', $context['priorities'] ?? [], true) => 'Ты заряжаешься от общения и пользы для других — выбирай роли с живым контактом, а не только «за компьютером».',
            default => 'Тебе подойдёт баланс: понятные задачи + возможность учиться новому без жёсткого застоя.',
        };

        $professionNotes = collect($context['recommendations'])->map(function ($item, $index) use ($name, $priorities) {
            $profession = $item['profession_name'] ?? 'Профессия';
            $base = $item['reason'] ?? "«{$profession}» хорошо ложится на твой профиль.";
            $fit = max(60, ($item['match_percent'] ?? 75) - ($index * 3));

            return [
                'profession_name' => $profession,
                'note' => ($name ? "{$name}, " : '') . $base . ($priorities ? ' Учитывая твои приоритеты (' . implode(', ', $priorities) . '), это сильный кандидат.' : ''),
                'pros' => ['Совпадает с твоим интерес-профилем', 'Есть понятный путь обучения в регионе'],
                'cons' => $index > 2 ? ['Потребуется время на вход в профессию'] : [],
                'fit_score' => $fit,
            ];
        })->all();

        return [
            'summary' => implode(' ', $summaryParts) ?: 'Твои ответы показывают широкий спектр интересов.',
            'personality_traits' => $this->localTraits($context),
            'work_style' => $workStyle,
            'strengths' => $this->localStrengths($context),
            'growth_areas' => ['Прокачать самоорганизацию', 'Попробовать мини-проект в топ-сфере'],
            'skills_to_learn' => $this->localSkills($context),
            'profession_notes' => $professionNotes,
            'less_suitable' => $this->localLessSuitable($context),
            'education_path' => $this->localEducationPath($context),
            'first_steps' => $this->localFirstSteps($context),
            'personal_advice' => $this->localPersonalAdvice($context),
            'motivation' => $name
                ? "{$name}, у тебя уже есть зацепки — исследуй топ-3 профессии на этой неделе!"
                : 'У тебя уже есть зацепки — исследуй топ-3 профессии на этой неделе!',
            'vacancy_examples' => $this->normalizeVacancyExamples([], $context),
        ];
    }

    private function localTraits(array $context): array
    {
        $traits = [];

        if (! empty($context['archetype']['title'])) {
            $traits[] = $context['archetype']['title'];
        }

        foreach (array_slice($context['priority_labels'] ?? [], 0, 2) as $label) {
            $traits[] = 'Ценит: ' . $label;
        }

        if (! empty($context['ai_answer_highlights'])) {
            $traits[] = 'Вдумчиво отвечает на уточняющие вопросы';
        }

        return array_slice($traits, 0, 4) ?: ['Любознательный', 'Открыт новому'];
    }

    private function localSkills(array $context): array
    {
        $top = $context['interest_profile'][0]['slug'] ?? 'it';

        return match ($top) {
            'it' => ['Базовый Python или веб', 'Английский для IT', 'Git и командная работа'],
            'medicine' => ['Биология и химия', 'Эмпатия и коммуникация', 'Стрессоустойчивость'],
            'creative' => ['Figma или графика', 'Портфолио', 'Насмотренность и референсы'],
            'trade' => ['Переговоры', 'Презентации', 'Аналитика рынка'],
            default => ['Целеполагание', 'Самопрезентация', 'Профиль на hh.ru'],
        };
    }

    private function localLessSuitable(array $context): array
    {
        $bottom = collect($context['interest_profile'])->sortBy('percent')->first();

        if (! $bottom || ($bottom['percent'] ?? 0) > 40) {
            return [];
        }

        return [[
            'area' => $bottom['label'] ?? 'некоторые сферы',
            'reason' => 'По ответам этот интерес выражен слабо (' . ($bottom['percent'] ?? 0) . '%) — пока не приоритет.',
        ]];
    }

    private function localEducationPath(array $context): string
    {
        $city = $context['city']['name'] ?? null;
        $citySuffix = $city ? " в {$city}" : ' в своём городе';

        return match ($context['status']) {
            'school_9' => 'Рассмотри колледж по топ-профессии — это быстрый старт. Параллельно сравни 10 класс, если планируешь вуз.',
            'school_11' => 'Определи предметы ЕГЭ под 1–2 профессии из списка и подбери вузы/колледжи' . $citySuffix . '.',
            'student' => 'Если специальность не зашла — смотри смежные курсы и стажировки' . $citySuffix . ', не обязательно начинать с нуля.',
            'working' => 'Короткие курсы + портфолио/пет-проект часто эффективнее второго высшего.',
            default => 'Начни с бесплатных курсов и дня открытых дверей' . $citySuffix . ' — так поймёшь, заходит ли сфера.',
        };
    }

    private function localFirstSteps(array $context): array
    {
        $top = $context['recommendations'][0]['profession_name'] ?? 'топ-профессию';
        $city = $context['city']['name'] ?? 'своём городе';
        $steps = [
            "Открой карточку «{$top}» и изучи дорожную карту",
        ];

        $vacancy = ($context['vacancy_samples'] ?? [])[0] ?? null;
        if ($vacancy) {
            $salary = $vacancy['salary_text'] ? ' — ' . $vacancy['salary_text'] : '';
            $steps[] = 'Изучи вакансию «' . $vacancy['title'] . '» в ' . $vacancy['company'] . $salary;
        } else {
            $steps[] = "Посмотри зарплаты в {$city}";
        }

        $steps[] = 'Загляни в раздел «Где учиться»';
        $steps[] = 'Найди 2–3 вакансии для понимания требований';
        $steps[] = 'Сохрани этот результат и вернись через неделю';

        return $steps;
    }

    private function localPersonalAdvice(array $context): string
    {
        $city = $context['city']['name'] ?? null;
        $current = $context['account']['current_profession'] ?? null;

        if ($current && ! empty($context['account']['is_registered'])) {
            return "Ты уже указал(а) в профиле «{$current}»"
                . ($city ? " и живёшь в {$city}" : '')
                . ' — сравни топ-профессии из теста со своей текущей траекторией: возможно, стоит развиваться в смежном направлении, а не начинать с нуля.';
        }

        return match ($context['status']) {
            'school_9' => 'Не торопись «навсегда» выбрать профессию — сейчас задача сузить круг до 2–3 направлений и попробовать их на практике (кружки, профориентация, визит в колледж).',
            'school_11' => 'Свяжи выбор профессии с предметами ЕГЭ уже сейчас — это сэкономит годы. Топ-3 из результата сравни по пути, деньгам и учёбе.',
            'student' => 'Если сомневаешься в специальности — это нормально. Пройди стажировку в одной из рекомендованных сфер: за месяц станет яснее, чем за год теории.',
            'working' => 'Смена сферы — марафон, не спринт. Используй переносимые навыки и начни с смежной роли, чтобы не терять доход.',
            default => 'Выбери одну профессию из топа и потрать на неё 2–3 часа: путь, учёба, вакансии. Ощущения после этого важнее любого теста.',
        };
    }

    private function localStrengths(array $context): array
    {
        $strengths = [];

        if (! empty($context['interest_profile'])) {
            foreach (collect($context['interest_profile'])->take(2) as $interest) {
                $strengths[] = 'Сильный интерес к «' . ($interest['label'] ?? '') . '» (' . ($interest['percent'] ?? 0) . '%)';
            }
        }

        if (! empty($context['priority_labels'])) {
            $strengths[] = 'Понимаешь свои приоритеты: ' . implode(', ', $context['priority_labels']);
        }

        if (! empty($context['answer_highlights'])) {
            $strengths[] = 'Осознанно выбираешь в тесте — видно по ответам';
        }

        return array_slice(array_values(array_unique($strengths)), 0, 5);
    }
}
