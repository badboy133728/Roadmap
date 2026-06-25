<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Profession;
use App\Models\QuizResult;
use App\Models\User;
use App\Services\Concerns\CallsOpenAiJson;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class InstitutionAiService
{
    use CallsOpenAiJson;

    private const CACHE_VERSION = 'v1';

    private array $typeLabels = [
        'university' => 'Вуз',
        'college' => 'Колледж',
        'courses' => 'Курсы',
    ];

    public function __construct(
        private QuizProfileService $profiles,
    ) {}

    public function recommend(
        Profession $profession,
        object $city,
        ?User $user = null,
        ?QuizResult $quizResult = null,
    ): array {
        $profession->loadMissing('category');
        $user?->loadMissing(['currentProfession', 'city']);

        $dbInstitutions = Institution::query()
            ->where('city_id', $city->id)
            ->with(['educationPrograms' => fn ($q) => $q->where('profession_id', $profession->id)])
            ->orderBy('name')
            ->get();

        $matched = $dbInstitutions->filter(fn ($i) => $i->educationPrograms->isNotEmpty())->values();
        $referencePool = $matched->isNotEmpty() ? $matched : $dbInstitutions;

        $quizPayload = $quizResult?->recommendations ?? [];
        $profile = $user
            ? $this->profiles->mergeUser($quizPayload['profile'] ?? [], $user)
            : ($quizPayload['profile'] ?? []);

        $cacheKey = sprintf(
            '%s:inst_ai:%d:%d:%s:%s',
            self::CACHE_VERSION,
            $city->id,
            $profession->id,
            $user?->id ?? 'guest',
            $quizResult?->session_id ?? 'no_quiz',
        );

        return Cache::remember($cacheKey, 3600, function () use (
            $profession,
            $city,
            $profile,
            $quizPayload,
            $referencePool,
            $matched,
            $user,
        ) {
            $context = $this->buildContext($profession, $city, $profile, $quizPayload, $referencePool, $user);

            $aiResult = $this->generateWithApi($context);

            if ($aiResult) {
                $items = $this->mergeWithDatabase($aiResult['institutions'] ?? [], $referencePool);

                return [
                    'items' => $items,
                    'summary' => trim((string) ($aiResult['summary'] ?? '')),
                    'admission_tips' => $this->stringList($aiResult['admission_tips'] ?? []),
                    'source' => 'ai',
                    'personalized' => (bool) $user,
                ];
            }

            return $this->fromDatabase($matched, $referencePool, $profession, $city);
        });
    }

    private function buildContext(
        Profession $profession,
        object $city,
        array $profile,
        array $quizPayload,
        Collection $referencePool,
        ?User $user,
    ): array {
        $recommendations = collect($quizPayload['list'] ?? []);
        $match = $recommendations->firstWhere('profession_id', $profession->id);

        $reference = $referencePool->map(fn ($i) => [
            'name' => $i->name,
            'type' => $i->type,
            'address' => $i->address,
            'website' => $i->website,
            'programs' => $i->educationPrograms->map(fn ($p) => [
                'name' => $p->name,
                'duration_years' => $p->duration_years,
                'study_form' => $p->study_form,
            ])->values()->all(),
        ])->values()->all();

        return [
            'profession' => [
                'name' => $profession->name,
                'description' => $profession->description,
                'skills' => $profession->skills ?? [],
                'category' => $profession->category?->name,
            ],
            'city' => ['name' => $city->name, 'region' => $city->region ?? ''],
            'profile' => $profile,
            'account' => $profile['account'] ?? null,
            'current_profession' => $user?->currentProfession?->name,
            'quiz_status' => $profile['status'] ?? ($quizPayload['profile']['status'] ?? 'exploring'),
            'archetype' => $quizPayload['archetype']['title'] ?? null,
            'match_percent' => $match['match_percent'] ?? null,
            'reference_institutions' => $reference,
        ];
    }

    private function generateWithApi(array $context): ?array
    {
        $decoded = $this->callOpenAiJson(
            'Ты эксперт по образованию и профориентации в России. Подбираешь учебные заведения под конкретного человека, '
            . 'город и профессию. Называй реальные вузы/колледжи региона, если знаешь; если в справочнике есть заведения — '
            . 'используй их названия. Пиши на «ты», по-русски. Отвечай строго JSON.',
            $this->buildPrompt($context),
            0.75,
            3500,
        );

        if (! is_array($decoded) || empty($decoded['institutions'])) {
            return null;
        }

        $institutions = collect($decoded['institutions'])
            ->filter(fn ($i) => is_array($i) && ! empty($i['name']))
            ->take(8)
            ->values()
            ->all();

        if ($institutions === []) {
            return null;
        }

        return [
            'summary' => $decoded['summary'] ?? '',
            'admission_tips' => $decoded['admission_tips'] ?? [],
            'institutions' => $institutions,
        ];
    }

    private function buildPrompt(array $context): string
    {
        $prof = $context['profession'];
        $name = $context['profile']['name'] ?? 'Пользователь';
        $account = $context['account'] ?? [];

        $reference = collect($context['reference_institutions'])
            ->map(fn ($i) => '- ' . $i['name'] . ' (' . ($i['type'] ?? 'unknown') . ')'
                . ($i['programs'] ? ': ' . collect($i['programs'])->pluck('name')->implode(', ') : ''))
            ->implode("\n") ?: 'справочник пуст — предложи типичные для города варианты';

        $accountLine = collect([
            $account['city_name'] ?? null ? 'Город профиля: ' . $account['city_name'] : null,
            $context['current_profession'] ? 'Текущая профессия: ' . $context['current_profession'] : null,
            $context['archetype'] ? 'Архетип: ' . $context['archetype'] : null,
            $context['match_percent'] ? 'Совпадение с профессией: ' . $context['match_percent'] . '%' : null,
        ])->filter()->implode("\n");

        $skills = implode(', ', $prof['skills'] ?? []) ?: 'не указаны';

        return <<<PROMPT
Подбери персонально, ГДЕ УЧИТЬСЯ на профессию «{$prof['name']}» в городе {$context['city']['name']}.

Человек: {$name}
Статус: {$context['quiz_status']}
{$accountLine}

Профессия: {$prof['name']} ({$prof['category']})
Описание: {$prof['description']}
Навыки: {$skills}

Заведения из нашей базы (приоритет — использовать эти названия, дополни программами и советами):
{$reference}

Правила:
- 3–6 заведений: вузы, колледжи, курсы — что реально подходит под статус человека
- Для школьника — колледж/вуз; для работающего — переподготовка, заочка, курсы
- У каждого заведения: конкретные программы, почему именно этому человеку, совет по поступлению
- Не копируй шаблон «рассмотри колледж» — пиши с именем, городом и профессией
- Если база пуста — предложи реальные типичные для {$context['city']['name']} варианты (медвуз, политех, колледж и т.д.)

JSON:
{
  "summary": "2-4 предложения — персональный обзор обучения для этого человека",
  "admission_tips": ["совет 1", "совет 2", "совет 3"],
  "institutions": [
    {
      "name": "полное название",
      "type": "university|college|courses",
      "address_hint": "район или адрес если известен",
      "website_hint": "домен или null",
      "why_fit": "2-3 предложения — почему именно {$name} и эта профессия",
      "programs": [
        {
          "name": "название программы",
          "duration_years": 4,
          "study_form": "очная|заочная|очно-заочная",
          "entrance_notes": "ЕГЭ/экзамены/портфолио"
        }
      ]
    }
  ]
}
PROMPT;
    }

    private function mergeWithDatabase(array $aiItems, Collection $referencePool): array
    {
        $byName = $referencePool->keyBy(fn ($i) => mb_strtolower(trim($i->name)));

        return collect($aiItems)->map(function ($item) use ($byName) {
            $key = mb_strtolower(trim((string) ($item['name'] ?? '')));
            $db = $byName->get($key);

            if (! $db) {
                foreach ($byName as $dbInst) {
                    if (str_contains($key, mb_strtolower($dbInst->name))
                        || str_contains(mb_strtolower($dbInst->name), $key)) {
                        $db = $dbInst;
                        break;
                    }
                }
            }

            $programs = collect($item['programs'] ?? [])->map(fn ($p) => (object) [
                'name' => $p['name'] ?? '',
                'duration_years' => $p['duration_years'] ?? null,
                'study_form' => $p['study_form'] ?? null,
                'entrance_notes' => $p['entrance_notes'] ?? null,
            ]);

            if ($db && $db->educationPrograms->isNotEmpty() && $programs->isEmpty()) {
                $programs = $db->educationPrograms->map(fn ($p) => (object) [
                    'name' => $p->name,
                    'duration_years' => $p->duration_years,
                    'study_form' => $p->study_form,
                    'entrance_notes' => null,
                ]);
            }

            return (object) [
                'name' => $db?->name ?? ($item['name'] ?? ''),
                'type' => $item['type'] ?? $db?->type ?? 'university',
                'type_label' => $this->typeLabels[$item['type'] ?? $db?->type ?? 'university'] ?? 'Учебное заведение',
                'address' => $db?->address ?? ($item['address_hint'] ?? null),
                'website' => $db?->website ?? ($item['website_hint'] ?? null),
                'why_fit' => trim((string) ($item['why_fit'] ?? '')),
                'programs' => $programs->values()->all(),
                'from_ai' => true,
            ];
        })->values()->all();
    }

    private function fromDatabase(Collection $matched, Collection $pool, Profession $profession, object $city): array
    {
        $source = $matched->isNotEmpty() ? $matched : $pool->take(4);

        $items = $source->map(function ($institution) use ($profession) {
            $programs = $institution->educationPrograms;

            if ($programs->isEmpty()) {
                $programs = collect([(object) [
                    'name' => $profession->name,
                    'duration_years' => null,
                    'study_form' => null,
                    'entrance_notes' => null,
                ]]);
            } else {
                $programs = $programs->map(fn ($p) => (object) [
                    'name' => $p->name,
                    'duration_years' => $p->duration_years,
                    'study_form' => $p->study_form,
                    'entrance_notes' => null,
                ]);
            }

            return (object) [
                'name' => $institution->name,
                'type' => $institution->type,
                'type_label' => $this->typeLabels[$institution->type] ?? $institution->type,
                'address' => $institution->address,
                'website' => $institution->website,
                'why_fit' => '',
                'programs' => $programs->values()->all(),
                'from_ai' => false,
            ];
        })->values()->all();

        return [
            'items' => $items,
            'summary' => $items === []
                ? "В {$city->name} мы дополняем базу учебных заведений для «{$profession->name}»."
                : null,
            'admission_tips' => [],
            'source' => 'db',
            'personalized' => false,
        ];
    }

    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $value,
        )));
    }
}
