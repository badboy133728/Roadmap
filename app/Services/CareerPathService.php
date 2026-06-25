<?php

namespace App\Services;

use App\Models\Profession;
use App\Models\QuizResult;
use App\Models\User;
use App\Services\Concerns\CallsOpenAiJson;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CareerPathService
{
  use CallsOpenAiJson;

  private const CACHE_VERSION = 'v4';

  private array $statusLabels = [
    'school_9' => 'учится в 9 классе',
    'school_11' => 'учится в 10–11 классе',
    'student' => 'студент или учится в колледже',
    'working' => 'уже работает',
    'exploring' => 'ищет себя и варианты будущего',
  ];

  /** Категории с обязательным лицензированием / длительным обучением */
  private array $regulatedCategories = [
    'medicine' => [
      'label' => 'медицина',
      'requirements' => [
        'медицинское образование (специалитет или ординатура)',
        'клиническая практика и интернатура',
        'ординатура по профилю (для врачей-специалистов)',
        'первичная и периодическая аккредитация специалиста',
        'сертификат / свидетельство об аккредитации',
      ],
    ],
    'law' => [
      'label' => 'право',
      'requirements' => [
        'юридическое высшее образование',
        'стажировка в юридической организации',
        'сдача квалификационного экзамена (для адвоката — экзамен в адвокатской палате)',
        'получение статуса адвоката / нотариуса при необходимости',
      ],
    ],
    'education' => [
      'label' => 'педагогика',
      'requirements' => [
        'педагогическое образование',
        'педагогическая практика',
        'аттестация педагогического работника',
      ],
    ],
    'transport' => [
      'label' => 'транспорт',
      'requirements' => [
        'профессиональное обучение / права нужной категории',
        'медкомиссия',
        'сертификаты и допуски по виду транспорта',
      ],
    ],
    'security' => [
      'label' => 'силовые структуры',
      'requirements' => [
        'отбор и обучение в ведомстве',
        'медкомиссия и нормативы физподготовки',
        'специальная подготовка и стажировка',
      ],
    ],
  ];

  public function __construct(
    private QuizProfileService $profiles,
    private CatalogService $catalog,
    private InstitutionAiService $institutionAi,
  ) {}

  public function forProfession(
    Profession $profession,
    ?User $user,
    object $city,
    Collection $vacancies,
    Collection $institutions,
    ?Profession $fromOverride = null,
    ?array $educationAi = null,
  ): array {
    $staticSteps = $profession->careerPathSteps;

    if (! $user) {
      return [
        'steps' => $staticSteps,
        'summary' => null,
        'personalized' => false,
        'source' => 'static',
        'hint' => 'Войди в аккаунт и пройди тест — ИИ построит путь под тебя.',
        'transition' => null,
        'total_months' => $staticSteps->sum('duration_months'),
      ];
    }

    $user->loadMissing(['city', 'currentProfession.category', 'favoriteProfessions']);
    $quizResult = QuizResult::query()
      ->where('user_id', $user->id)
      ->latest()
      ->first();

    $fromProfession = $fromOverride ?? $user->currentProfession;
    if ($fromProfession && ! $fromProfession->relationLoaded('category')) {
      $fromProfession->load('category');
    }
    $fromKey = $fromProfession?->id ?? 'none';

    $educationAi ??= $this->institutionAi->recommend($profession, $city, $user, $quizResult);

    $cacheKey = sprintf(
      '%s:career_path:%d:%d:%s:%s:%d',
      self::CACHE_VERSION,
      $user->id,
      $profession->id,
      $fromKey,
      $quizResult?->session_id ?? 'no_quiz',
      $city->id ?? 0,
    );

    return Cache::remember($cacheKey, 3600, function () use (
      $profession,
      $user,
      $city,
      $vacancies,
      $institutions,
      $quizResult,
      $staticSteps,
      $fromProfession,
      $educationAi,
    ) {
      $context = $this->buildContext(
        $profession,
        $user,
        $city,
        $vacancies,
        $institutions,
        $quizResult,
        $fromProfession,
        $educationAi,
      );

      if ($this->isAiAvailable()) {
        try {
          $generated = $this->generateWithApi($context);

          if ($generated) {
            return array_merge($generated, [
              'personalized' => true,
              'source' => 'ai',
              'hint' => null,
            ]);
          }
        } catch (\Throwable $e) {
          Log::warning('CareerPathService AI failed', ['error' => $e->getMessage()]);
        }
      }

      return array_merge($this->generateLocal($context, $staticSteps), [
        'personalized' => true,
        'source' => 'local',
        'hint' => $quizResult ? null : 'Пройди тест — путь станет ещё точнее.',
      ]);
    });
  }

  private function buildContext(
    Profession $profession,
    User $user,
    object $city,
    Collection $vacancies,
    Collection $institutions,
    ?QuizResult $quizResult,
    ?Profession $fromProfession,
    array $educationAi = [],
  ): array {
    $quizPayload = $quizResult?->recommendations ?? [];
    $quizProfile = $quizPayload['profile'] ?? [];
    $profile = $this->profiles->mergeUser($quizProfile, $user);

    $recommendations = collect($quizPayload['list'] ?? []);
    $match = $recommendations->firstWhere('profession_id', $profession->id)
      ?? $recommendations->firstWhere('profession_slug', $profession->slug);

    $transition = $this->analyzeTransition($fromProfession, $profession);

    return [
      'profession' => $this->professionSnapshot($profession),
      'from_profession' => $fromProfession ? $this->professionSnapshot($fromProfession) : null,
      'transition' => $transition,
      'city' => [
        'id' => $city->id,
        'name' => $city->name,
        'region' => $city->region ?? '',
      ],
      'profile' => $profile,
      'account' => $profile['account'] ?? null,
      'quiz' => [
        'has_result' => (bool) $quizResult,
        'session_id' => $quizResult?->session_id,
        'status' => $quizProfile['status'] ?? 'exploring',
        'status_label' => $this->statusLabels[$quizProfile['status'] ?? 'exploring'] ?? 'ищет направление',
        'about' => $quizProfile['about'] ?? null,
        'priorities' => $quizProfile['priorities'] ?? [],
        'archetype' => $quizPayload['archetype'] ?? null,
        'interest_profile' => $quizPayload['interest_profile'] ?? [],
        'match_percent' => $match['match_percent'] ?? null,
        'match_reason' => $match['reason'] ?? null,
        'in_top' => (bool) $match,
        'top_professions' => $recommendations->take(5)->pluck('profession_name')->all(),
      ],
      'vacancies' => $vacancies->map(fn ($v) => [
        'title' => $v->title,
        'company' => $v->company,
        'salary_text' => $v->salary_text,
        'experience_level' => $v->experience_level,
      ])->values()->all(),
      'institutions' => $institutions->map(fn ($i) => [
        'name' => $i->name,
        'type' => $i->type ?? null,
        'programs' => $i->educationPrograms?->pluck('name')->take(3)->all() ?? [],
      ])->values()->all(),
      'education_ai' => [
        'summary' => $educationAi['summary'] ?? null,
        'admission_tips' => $educationAi['admission_tips'] ?? [],
        'institutions' => collect($educationAi['items'] ?? [])->map(fn ($i) => [
          'name' => $i->name ?? '',
          'type' => $i->type ?? null,
          'why_fit' => $i->why_fit ?? '',
          'programs' => collect($i->programs ?? [])->pluck('name')->take(3)->all(),
        ])->values()->all(),
      ],
      'quiz_answers' => $this->formatQuizAnswers($quizResult),
      'reference_steps' => $profession->careerPathSteps->map(fn ($s) => [
        'step_type' => $s->step_type,
        'title' => $s->title,
        'description' => $s->description,
        'duration_months' => $s->duration_months,
      ])->values()->all(),
      'regulated_requirements' => $this->regulatedRequirements($profession),
      'min_steps' => $this->minimumStepCount($transition, $quizProfile['status'] ?? 'exploring'),
    ];
  }

  private function professionSnapshot(Profession $profession): array
  {
    $categorySlug = $profession->category?->slug;

    return [
      'id' => $profession->id,
      'name' => $profession->name,
      'slug' => $profession->slug,
      'description' => $profession->description,
      'skills' => $profession->skills ?? [],
      'category' => $profession->category?->name,
      'category_slug' => $categorySlug,
    ];
  }

  private function analyzeTransition(?Profession $from, Profession $to): array
  {
    if (! $from) {
      return [
        'type' => 'from_scratch',
        'label' => 'путь с нуля (текущая профессия в профиле не указана)',
        'difficulty' => 'medium',
        'same_category' => false,
        'shared_skills' => [],
        'missing_skills' => $to->skills ?? [],
        'is_major_change' => false,
      ];
    }

    if ($from->id === $to->id) {
      return [
        'type' => 'same_profession',
        'label' => "развитие в текущей профессии «{$from->name}»",
        'difficulty' => 'easy',
        'same_category' => true,
        'shared_skills' => $from->skills ?? [],
        'missing_skills' => [],
        'is_major_change' => false,
      ];
    }

    $shared = array_values(array_intersect($from->skills ?? [], $to->skills ?? []));
    $missing = array_values(array_diff($to->skills ?? [], $from->skills ?? []));
    $sameCategory = $from->category_id === $to->category_id;
    $fromSlug = $from->category?->slug;
    $toSlug = $to->category?->slug;
    $isMajor = ! $sameCategory || count($missing) >= 3;

    $difficulty = 'medium';
    if (! $sameCategory) {
      $difficulty = isset($this->regulatedCategories[$toSlug]) ? 'extreme' : 'hard';
    } elseif (count($missing) >= 4) {
      $difficulty = 'hard';
    }

    return [
      'type' => 'career_change',
      'label' => "переход из «{$from->name}» в «{$to->name}»",
      'difficulty' => $difficulty,
      'same_category' => $sameCategory,
      'from_category' => $from->category?->name,
      'to_category' => $to->category?->name,
      'from_category_slug' => $fromSlug,
      'to_category_slug' => $toSlug,
      'shared_skills' => $shared,
      'missing_skills' => $missing,
      'is_major_change' => $isMajor,
    ];
  }

  private function regulatedRequirements(Profession $profession): ?array
  {
    $slug = $profession->category?->slug;

    return $this->regulatedCategories[$slug] ?? null;
  }

  private function minimumStepCount(array $transition, string $status): int
  {
    if ($transition['type'] === 'career_change' && ($transition['is_major_change'] ?? false)) {
      return in_array($transition['difficulty'] ?? '', ['extreme', 'hard'], true) ? 12 : 10;
    }

    if ($transition['type'] === 'from_scratch') {
      return in_array($status, ['school_9', 'school_11'], true) ? 8 : 9;
    }

    return 7;
  }

  private function isAiAvailable(): bool
  {
    return config('ai.enabled') && filled(config('ai.openai.api_key'));
  }

  private function generateWithApi(array $context): ?array
  {
    $system = 'Ты опытный профориентолог в России. Строишь УНИКАЛЬНЫЙ персональный маршрут — не шаблон «школа-колледж-работа». '
      . 'Каждый шаг привязан к имени человека, его текущей профессии и ответам теста. '
      . 'Для смены сферы включай всё формальное обучение, экзамены, аккредитацию. Отвечай строго JSON.';

    $decoded = $this->callOpenAiJson($system, $this->buildPrompt($context), 0.8, 6000);

    if (! is_array($decoded) || empty($decoded['steps'])) {
      return null;
    }

    $minSteps = $context['min_steps'] ?? 8;
    $steps = $this->normalizeSteps($decoded['steps'], $minSteps);

    if (count($steps) < max(5, (int) floor($minSteps * 0.6))) {
      $expand = $this->callOpenAiJson(
        $system,
        $this->buildExpandPrompt($context, $steps, $minSteps),
        0.75,
        6000,
      );

      if (is_array($expand) && ! empty($expand['steps'])) {
        $expanded = $this->normalizeSteps($expand['steps'], $minSteps);
        if (count($expanded) > count($steps)) {
          $steps = $expanded;
          $decoded['summary'] = $expand['summary'] ?? $decoded['summary'];
        }
      }
    }

    if ($steps === []) {
      return null;
    }

    $totalMonths = collect($steps)->sum(fn ($s) => $s->duration_months);

    return [
      'steps' => $steps,
      'summary' => trim((string) ($decoded['summary'] ?? '')),
      'transition' => $context['transition'],
      'total_months' => $totalMonths,
      'total_years_label' => $this->formatDuration($totalMonths),
    ];
  }

  private function buildPrompt(array $context): string
  {
    $prof = $context['profession'];
    $from = $context['from_profession'];
    $transition = $context['transition'];
    $quiz = $context['quiz'];
    $account = $context['account'] ?? [];
    $name = $context['profile']['name'] ?? 'Пользователь';
    $minSteps = $context['min_steps'];

    $interests = collect($quiz['interest_profile'] ?? [])
      ->map(fn ($i) => ($i['label'] ?? '') . ' (' . ($i['percent'] ?? 0) . '%)')
      ->implode(', ');

    $archetype = $quiz['archetype'];
    $archetypeLine = $archetype
      ? "Тип личности из теста: {$archetype['title']} — {$archetype['tagline']}"
      : 'Тест не пройден или архетип не определён';

    $matchLine = $quiz['in_top']
      ? "Профессия «{$prof['name']}» в топе рекомендаций теста ({$quiz['match_percent']}%): {$quiz['match_reason']}"
      : "Профессия «{$prof['name']}» не в топе теста. Топ: " . implode(', ', $quiz['top_professions'] ?: ['не указан']);

    $vacancies = collect($context['vacancies'])
      ->map(fn ($v) => "- {$v['title']} ({$v['company']})" . ($v['salary_text'] ? ", {$v['salary_text']}" : ''))
      ->implode("\n") ?: 'нет в базе';

    $schools = collect($context['education_ai']['institutions'] ?? [])
      ->map(fn ($i) => '- ' . $i['name'] . ($i['why_fit'] ? ' — ' . $i['why_fit'] : '')
        . ($i['programs'] ? ' [' . implode(', ', $i['programs']) . ']' : ''))
      ->implode("\n")
      ?: collect($context['institutions'])
        ->map(fn ($i) => '- ' . $i['name'] . ($i['programs'] ? ': ' . implode(', ', $i['programs']) : ''))
        ->implode("\n")
      ?: 'нет в базе для этого города';

    $educationSummary = $context['education_ai']['summary'] ?? '';
    $quizAnswers = $context['quiz_answers'] ?? 'ответы не указаны';

    $fromBlock = $from
      ? <<<FROM
ТОЧКА СТАРТА (текущая профессия в профиле):
«{$from['name']}» ({$from['category']})
Навыки сейчас: {$this->joinList($from['skills'])}
Описание: {$from['description']}
FROM
      : 'ТОЧКА СТАРТА: текущая профессия в профиле не указана — считай, что человек только выбирает направление.';

    $transitionBlock = <<<TRANS
Тип маршрута: {$transition['label']}
Сложность перехода: {$transition['difficulty']}
Общие навыки: {$this->joinList($transition['shared_skills'] ?? [])}
Не хватает навыков: {$this->joinList($transition['missing_skills'] ?? [])}
TRANS;

    $regulatedBlock = '';
    if ($regulated = $context['regulated_requirements']) {
      $reqs = implode("\n- ", $regulated['requirements']);
      $regulatedBlock = <<<REG

ЦЕЛЕВАЯ СФЕРА РЕГУЛИРУЕМАЯ ({$regulated['label']}). ОБЯЗАТЕЛЬНО включи отдельными шагами:
- {$reqs}
Не пропускай эти этапы даже при смене профессии из другой сферы.
REG;
    }

    $about = $quiz['about'] ? "О себе из теста: {$quiz['about']}" : '';

    $majorChangeWarning = ($transition['is_major_change'] ?? false)
      ? "\n⚠️ Это КРУПНАЯ смена сферы. Нельзя сводить путь к 3–5 шагам. Нужен полный маршрут с обучением с нуля в новой отрасли."
      : '';

    return <<<PROMPT
Построй ПОЛНЫЙ персональный маршрут ОТ текущей точки ДО профессии «{$prof['name']}».

{$fromBlock}

ТОЧКА ФИНИША:
«{$prof['name']}» ({$prof['category']})
Навыки цели: {$this->joinList($prof['skills'])}
Описание: {$prof['description']}

{$transitionBlock}
{$majorChangeWarning}
{$regulatedBlock}

Человек:
Имя: {$name}
Город: {$context['city']['name']}
Статус: {$quiz['status_label']}
{$about}
{$archetypeLine}
Интересы: {$interests}
{$matchLine}

Ответы из теста (учитывай в шагах):
{$quizAnswers}

ИИ-подбор учебных заведений:
{$educationSummary}
{$schools}

Вакансии (только в финальных шагах):
{$vacancies}

КРИТИЧЕСКИ ВАЖНО — НЕ ШАБЛОН:
1. Минимум {$minSteps} шагов. Для смены сферы — 12–16 шагов.
2. Каждый title уникален. В description ОБЯЗАТЕЛЬНО имя «{$name}» и отсылка к текущей профессии/статусу.
3. Шаги про обучение — конкретные вузы из списка выше с программами.
4. ЗАПРЕЩЕНО: «Закрепи базу», «Прокачай навыки» без контекста; пропуск медобразования при переходе в медицину.
5. duration_months реалистичны: вуз 48–72, ординатура 24–60.
6. step_type: start, assessment, school, exam, college, university, course, practice, internship, residency, accreditation, license, work, transition

JSON:
{
  "summary": "3–5 предложений: откуда стартуем, куда идём, общий срок, главные риски и что учесть",
  "total_months_estimate": 120,
  "steps": [
    {
      "step_type": "start",
      "title": "заголовок",
      "description": "подробное описание",
      "duration_months": 6
    }
  ]
}
PROMPT;
  }

  private function joinList(array $items): string
  {
    return $items === [] ? 'не указаны' : implode(', ', $items);
  }

  private function normalizeSteps(array $steps, int $minSteps = 8): array
  {
    $allowedTypes = [
      'start', 'assessment', 'school', 'exam', 'college', 'university',
      'course', 'practice', 'internship', 'residency', 'accreditation',
      'license', 'work', 'transition',
    ];

    $normalized = collect($steps)
      ->filter(fn ($s) => is_array($s) && ! empty($s['title']))
      ->take(18)
      ->values()
      ->map(function ($step) use ($allowedTypes) {
        $type = $step['step_type'] ?? 'course';

        if (! in_array($type, $allowedTypes, true)) {
          $type = 'course';
        }

        return (object) [
          'step_type' => $type,
          'title' => trim((string) $step['title']),
          'description' => trim((string) ($step['description'] ?? '')),
          'duration_months' => max(1, (int) ($step['duration_months'] ?? 3)),
        ];
      })
      ->all();

    if (count($normalized) < 5) {
      return [];
    }

    return $normalized;
  }

  private function buildExpandPrompt(array $context, array $existingSteps, int $minSteps): string
  {
    $existing = collect($existingSteps)
      ->map(fn ($s, $i) => ($i + 1) . '. ' . $s->title)
      ->implode("\n");

    $name = $context['profile']['name'] ?? 'Пользователь';

    return <<<PROMPT
Маршрут для «{$name}» слишком короткий ({$minSteps} шагов минимум). Расширь его.

Уже есть:
{$existing}

Добавь недостающие этапы: экзамены, конкретные вузы, практику, аккредитацию, стажировку.
Каждый шаг — уникальный, с именем «{$name}» в description.
Верни ПОЛНЫЙ список steps (не только новые) в том же JSON-формате с summary.
PROMPT;
  }

  private function formatQuizAnswers(?QuizResult $quizResult): string
  {
    if (! $quizResult) {
      return 'тест не пройден';
    }

    $answers = $quizResult->answers ?? [];
    $lines = [];

    foreach (array_slice($answers, 0, 12) as $answer) {
      if (! is_array($answer)) {
        continue;
      }

      $question = $answer['question'] ?? $answer['question_text'] ?? null;
      $text = $answer['answer_text'] ?? $answer['text'] ?? $answer['value'] ?? null;

      if ($question && $text) {
        $lines[] = "- {$question}: {$text}";
      }
    }

    $payload = $quizResult->recommendations ?? [];
    if ($about = $payload['profile']['about'] ?? null) {
      $lines[] = "- О себе: {$about}";
    }

    return $lines ? implode("\n", $lines) : 'детали ответов не сохранены';
  }

  private function generateLocal(array $context, Collection $staticSteps): array
  {
    $transition = $context['transition'];
    $quiz = $context['quiz'];
    $status = $quiz['status'] ?? 'exploring';
    $to = $context['profession'];
    $from = $context['from_profession'];
    $cityName = $context['city']['name'];
    $name = $context['profile']['name'] ?? null;
    $greeting = $name ? "{$name}, " : '';

    $steps = collect();

    if ($from) {
      $steps->push((object) [
        'step_type' => 'start',
        'title' => "Старт: «{$from['name']}»",
        'description' => $greeting . "сейчас ты в сфере «{$from['category']}». "
          . "Перед переходом в «{$to['name']}» зафиксируй сильные стороны: "
          . $this->joinList($from['skills'] ?? [])
          . '. Оцени, готов ли ты к длительному обучению и смене режима работы.',
        'duration_months' => 1,
      ]);

      $steps->push((object) [
        'step_type' => 'assessment',
        'title' => 'Анализ разрыва навыков',
        'description' => 'Сравни текущий опыт с требованиями цели. '
          . 'Уже есть: ' . $this->joinList($transition['shared_skills'] ?? []) . '. '
          . 'Нужно освоить: ' . $this->joinList($transition['missing_skills'] ?? []) . '.',
        'duration_months' => 1,
      ]);
    }

    $toSlug = $to['category_slug'] ?? null;

    if ($toSlug === 'medicine') {
      $steps = $steps->merge($this->medicinePathSteps($context, $status));
    } elseif ($transition['type'] === 'career_change' && ($transition['is_major_change'] ?? false)) {
      $steps = $steps->merge($this->majorChangePathSteps($context, $status));
    } elseif (in_array($status, ['school_9', 'school_11'], true)) {
      $steps = $steps->merge($this->schoolStudentPathSteps($context, $status));
    } elseif ($status === 'student') {
      $steps = $steps->merge($this->studentPathSteps($context));
    } elseif ($status === 'working') {
      $steps = $steps->merge($this->workingPathSteps($context));
    } else {
      $steps = $steps->merge($this->genericPathSteps($context));
    }

    $vacancy = ($context['vacancies'] ?? [])[0] ?? null;
    if (! $steps->contains(fn ($s) => in_array($s->step_type, ['work', 'internship'], true))) {
      $steps->push((object) [
        'step_type' => $vacancy ? 'internship' : 'work',
        'title' => 'Выход на работу по специальности',
        'description' => $vacancy
          ? 'Ориентир по рынку: «' . $vacancy['title'] . '» — ' . $vacancy['company'] . '.'
          : 'Ищи junior- или стажёрские позиции по «' . $to['name'] . '» в ' . $cityName . '.',
        'duration_months' => 6,
      ]);
    }

    if ($steps->count() < 6 && $staticSteps->isNotEmpty()) {
      foreach ($staticSteps as $s) {
        $steps->push((object) [
          'step_type' => $s->step_type,
          'title' => $s->title,
          'description' => $s->description,
          'duration_months' => $s->duration_months,
        ]);
      }
    }

    $summary = $this->buildLocalSummary($context, $greeting, $steps);

    $totalMonths = $steps->sum(fn ($s) => $s->duration_months);

    return [
      'steps' => $steps->values()->all(),
      'summary' => $summary,
      'transition' => $transition,
      'total_months' => $totalMonths,
      'total_years_label' => $this->formatDuration($totalMonths),
    ];
  }

  private function medicinePathSteps(array $context, string $status): Collection
  {
    $to = $context['profession'];
    $cityName = $context['city']['name'];
    $from = $context['from_profession'];
    $isCareerChange = $from && ($from['category_slug'] ?? '') !== 'medicine';

    $steps = collect();

    if ($isCareerChange) {
      $steps->push((object) [
        'step_type' => 'transition',
        'title' => 'Решение о смене сферы на медицину',
        'description' => 'Переход из «' . $from['name'] . '» в «' . $to['name'] . '» — это не короткие курсы, а полное медицинское образование (обычно 6+ лет). '
          . 'Спланируй финансы, поддержку семьи и возможность учиться очно.',
        'duration_months' => 2,
      ]);
    }

    if (in_array($status, ['school_9', 'school_11', 'exploring'], true) || $isCareerChange) {
      $steps->push((object) [
        'step_type' => 'exam',
        'title' => 'Подготовка к поступлению в медвуз',
        'description' => 'Сдай ЕГЭ по биологии и химии (часто также русский и профильный предмет). '
          . 'Высокий проходной балл — обязателен. Можно подтянуть знания на подготовительных курсах.',
        'duration_months' => $status === 'school_11' ? 12 : 24,
      ]);
    }

    $steps->push((object) [
      'step_type' => 'university',
      'title' => 'Медицинский вуз (специалитет «Лечебное дело»)',
      'description' => 'Поступи в медицинский университет. Базовый цикл — около 6 лет: анатомия, физиология, '
        . 'клинические дисциплины. Выбери вуз в ' . $cityName . ' или готовься к переезду.',
      'duration_months' => 72,
    ]);

    $steps->push((object) [
      'step_type' => 'practice',
      'title' => 'Клиническая практика на кафедрах',
      'description' => 'Проходи практику в поликлиниках и стационарах с 3–4 курса. '
        . 'Наблюдай за врачами, отрабатывай базовые навыки, выбирай хирургическое направление.',
      'duration_months' => 12,
    ]);

    $steps->push((object) [
      'step_type' => 'internship',
      'title' => 'Интернатура / первичная специализация',
      'description' => 'После диплома — интернатура или ординатура (в зависимости от года выпуска и программы). '
        . 'Работа под руководством опытных врачей, первые самостоятельные решения.',
      'duration_months' => 12,
    ]);

    $steps->push((object) [
      'step_type' => 'residency',
      'title' => 'Ординатура по «' . $to['name'] . '»',
      'description' => 'Углубленное обучение хирургии: операции, протоколы, ассистирование, дежурства. '
        . 'Обычно 2–5 лет в зависимости от специальности.',
      'duration_months' => 36,
    ]);

    $steps->push((object) [
      'step_type' => 'accreditation',
      'title' => 'Первичная аккредитация специалиста',
      'description' => 'Пройди первичную аккредитацию по специальности «' . $to['name'] . '» '
        . '(тест + оценка практических навыков). Без аккредитации нельзя легально работать врачом в РФ.',
      'duration_months' => 3,
    ]);

    $steps->push((object) [
      'step_type' => 'license',
      'title' => 'Сертификат и допуск к самостоятельной работе',
      'description' => 'Получи сертификат специалиста / свидетельство об аккредитации. '
        . 'Оформи медкнижку, при необходимости — допуски в конкретной клинике.',
      'duration_months' => 2,
    ]);

    $steps->push((object) [
      'step_type' => 'work',
      'title' => 'Работа «' . $to['name'] . '»',
      'description' => 'Трудоустройство в больницу или клинику. Начни с позиции младшего хирурга / ассистента, '
        . 'накапливай операционный опыт, готовься к периодической аккредитации каждые 5 лет.',
      'duration_months' => 12,
    ]);

    return $steps;
  }

  private function majorChangePathSteps(array $context, string $status): Collection
  {
    $to = $context['profession'];
    $cityName = $context['city']['name'];

    return collect([
      (object) [
        'step_type' => 'transition',
        'title' => 'План перехода в новую сферу',
        'description' => 'Смена отрасли займёт годы, не месяцы. Составь финансовый план, '
          . 'обсуди с работодателем возможность совмещения или отпуска на обучение.',
        'duration_months' => 2,
      ],
      (object) [
        'step_type' => 'course',
        'title' => 'Базовая переподготовка',
        'description' => 'Пройди программу переквалификации или второе высшее по направлению «' . $to['name'] . '». '
          . 'Навыки к освоению: ' . $this->joinList($context['transition']['missing_skills'] ?? []) . '.',
        'duration_months' => 12,
      ],
      (object) [
        'step_type' => 'university',
        'title' => 'Профильное образование',
        'description' => 'Поступи в колледж или вуз в ' . $cityName . ' по специальности, связанной с «' . $to['name'] . '».',
        'duration_months' => $status === 'working' ? 36 : 48,
      ],
      (object) [
        'step_type' => 'practice',
        'title' => 'Практика и стажировка',
        'description' => 'Закрепи навыки на реальных задачах: практика, проекты, наставник в отрасли.',
        'duration_months' => 6,
      ],
    ]);
  }

  private function schoolStudentPathSteps(array $context, string $status): Collection
  {
    $to = $context['profession'];
    $cityName = $context['city']['name'];

    $steps = collect([
      (object) [
        'step_type' => 'school',
        'title' => 'Школьная подготовка',
        'description' => 'Подтяни предметы, нужные для «' . $to['name'] . '»: '
          . $this->joinList($to['skills'] ?? []) . '.',
        'duration_months' => $status === 'school_9' ? 12 : 24,
      ],
    ]);

    if ($status === 'school_9') {
      $steps->push((object) [
        'step_type' => 'college',
        'title' => 'Колледж по смежной специальности',
        'description' => 'После 9 класса — СПО в ' . $cityName . ', если хочешь быстрее выйти на рынок.',
        'duration_months' => 24,
      ]);
    } else {
      $steps->push((object) [
        'step_type' => 'exam',
        'title' => 'ЕГЭ и поступление',
        'description' => 'Сдай ЕГЭ по профильным предметам и подай документы в вуз или колледж.',
        'duration_months' => 12,
      ]);
      $steps->push((object) [
        'step_type' => 'university',
        'title' => 'Высшее или среднее профессиональное образование',
        'description' => 'Обучение по программе, ведущей к профессии «' . $to['name'] . '».',
        'duration_months' => 48,
      ]);
    }

    $steps->push((object) [
      'step_type' => 'course',
      'title' => 'Дополнительные курсы и сертификаты',
      'description' => 'Усиль резюме курсами по ключевым навыкам профессии.',
      'duration_months' => 6,
    ]);

    return $steps;
  }

  private function studentPathSteps(array $context): Collection
  {
    $to = $context['profession'];

    return collect([
      (object) [
        'step_type' => 'university',
        'title' => 'Заверши текущее обучение или переведись',
        'description' => 'Проверь, совпадает ли твоя специальность с «' . $to['name'] . '». При необходимости — перевод или магистратура.',
        'duration_months' => 24,
      ],
      (object) [
        'step_type' => 'practice',
        'title' => 'Практика в отрасли',
        'description' => 'Пройди производственную практику или подработку по выбранному направлению.',
        'duration_months' => 6,
      ],
      (object) [
        'step_type' => 'course',
        'title' => 'Прокачка недостающих навыков',
        'description' => 'Курсы по: ' . $this->joinList($context['transition']['missing_skills'] ?? $to['skills'] ?? []),
        'duration_months' => 6,
      ],
    ]);
  }

  private function workingPathSteps(array $context): Collection
  {
    $to = $context['profession'];
    $from = $context['from_profession'];

    return collect([
      (object) [
        'step_type' => 'course',
        'title' => 'Профессиональная переподготовка',
        'description' => $from
          ? 'Из «' . $from['name'] . '» в «' . $to['name'] . '» — нужны формальные программы обучения, не только онлайн-курсы.'
          : 'Выбери очную или заочную программу переподготовки под целевую профессию.',
        'duration_months' => 12,
      ],
      (object) [
        'step_type' => 'practice',
        'title' => 'Практика параллельно с работой',
        'description' => 'Стажировка, волонтёрство или pet-проекты в новой сфере по вечерам и выходным.',
        'duration_months' => 6,
      ],
      (object) [
        'step_type' => 'internship',
        'title' => 'Стажировка или junior-позиция',
        'description' => 'Первая работа в новой отрасли — даже с понижением дохода на старте.',
        'duration_months' => 6,
      ],
    ]);
  }

  private function genericPathSteps(array $context): Collection
  {
    $to = $context['profession'];
    $cityName = $context['city']['name'];

    return collect([
      (object) [
        'step_type' => 'college',
        'title' => 'Среднее профессиональное образование',
        'description' => 'Колледж в ' . $cityName . ' — первый шаг к «' . $to['name'] . '».',
        'duration_months' => 24,
      ],
      (object) [
        'step_type' => 'university',
        'title' => 'Высшее образование',
        'description' => 'Профильный вуз для углубления знаний и доступа к senior-позициям.',
        'duration_months' => 48,
      ],
      (object) [
        'step_type' => 'course',
        'title' => 'Специализированные курсы',
        'description' => 'Сертификаты по: ' . $this->joinList($to['skills'] ?? []),
        'duration_months' => 6,
      ],
    ]);
  }

  private function buildLocalSummary(array $context, string $greeting, Collection $steps): string
  {
    $to = $context['profession'];
    $transition = $context['transition'];
    $total = $this->formatDuration($steps->sum(fn ($s) => $s->duration_months));

    $parts = [];

    if ($transition['type'] === 'career_change' && $context['from_profession']) {
      $parts[] = $greeting . 'маршрут из «' . $context['from_profession']['name'] . '» в «' . $to['name'] . '» '
        . '(сложность: ' . $transition['difficulty'] . ').';
    } else {
      $parts[] = $greeting . 'путь к профессии «' . $to['name'] . '».';
    }

    $parts[] = 'Ориентировочный срок: ' . $total . '.';

    if (($transition['difficulty'] ?? '') === 'extreme') {
      $parts[] = 'Это длительный переход между разными сферами — закладывай годы обучения и формальную аккредитацию.';
    }

    return implode(' ', $parts);
  }

  private function formatDuration(int $months): string
  {
    if ($months < 12) {
      return $months . ' мес.';
    }

    $years = intdiv($months, 12);
    $rest = $months % 12;

    if ($rest === 0) {
      return $years . ' ' . $this->yearWord($years);
    }

    return $years . ' ' . $this->yearWord($years) . ' ' . $rest . ' мес.';
  }

  private function yearWord(int $years): string
  {
    $mod10 = $years % 10;
    $mod100 = $years % 100;

    if ($mod100 >= 11 && $mod100 <= 14) {
      return 'лет';
    }

    return match ($mod10) {
      1 => 'год',
      2, 3, 4 => 'года',
      default => 'лет',
    };
  }
}
