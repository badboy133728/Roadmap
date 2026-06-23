<?php

namespace App\Services;

class PersonalityArchetypeService
{
    private array $archetypes = [
        'it' => [
            'title' => 'Техно-исследователь',
            'emoji' => '🚀',
            'tagline' => 'Тебе нравится разбираться, как всё устроено',
            'description' => 'Ты тянешься к технологиям, логике и инновациям. Тебе комфортно учиться новому и решать нестандартные задачи.',
            'color' => 'from-violet-500 to-indigo-600',
        ],
        'medicine' => [
            'title' => 'Помогающий герой',
            'emoji' => '💚',
            'tagline' => 'Тебе важно быть полезным людям',
            'description' => 'Ты эмпатичен и ответственен. Профессии, где можно заботиться о других и видеть результат своей работы — твоя зона.',
            'color' => 'from-emerald-500 to-teal-600',
        ],
        'engineering' => [
            'title' => 'Созидатель',
            'emoji' => '⚙️',
            'tagline' => 'Ты любишь создавать и чинить',
            'description' => 'Тебе близки практика, техника и конкретный результат. Ты предпочитаешь дело теории и видишь плоды своего труда.',
            'color' => 'from-slate-600 to-zinc-700',
        ],
        'education' => [
            'title' => 'Наставник',
            'emoji' => '📚',
            'tagline' => 'Тебе нравится делиться знаниями',
            'description' => 'Ты умеешь объяснять, вдохновлять и вести за собой. Образование и работа с людьми — твой естественный путь.',
            'color' => 'from-amber-500 to-orange-500',
        ],
        'trade' => [
            'title' => 'Коммуникатор',
            'emoji' => '🤝',
            'tagline' => 'Ты чувствуешь людей и процессы',
            'description' => 'Тебе интересны продажи, управление и сервис. Ты быстро находишь общий язык и умеешь договариваться.',
            'color' => 'from-sky-500 to-blue-600',
        ],
        'law' => [
            'title' => 'Аналитик порядка',
            'emoji' => '⚖️',
            'tagline' => 'Тебе важны правила и структура',
            'description' => 'Ты внимателен к деталям, умеешь аргументировать и ценишь стабильность. Право и финансы тебе близки.',
            'color' => 'from-indigo-600 to-blue-800',
        ],
        'creative' => [
            'title' => 'Творец',
            'emoji' => '🎨',
            'tagline' => 'Тебе нужна свобода самовыражения',
            'description' => 'Ты мыслишь образами, идеями и историями. Креативные профессии дают тебе пространство для реализации.',
            'color' => 'from-pink-500 to-rose-600',
        ],
        'production' => [
            'title' => 'Мастер дела',
            'emoji' => '🏭',
            'tagline' => 'Ты ценишь реальный результат',
            'description' => 'Тебе нравится работать руками и видеть конкретный продукт. Производство и ремесло — твоя сильная сторона.',
            'color' => 'from-orange-500 to-amber-600',
        ],
        'transport' => [
            'title' => 'Путешественник',
            'emoji' => '🛣️',
            'tagline' => 'Тебе нравится движение и дорога',
            'description' => 'Ты не сидишь на месте — тебе близки профессии, связанные с транспортом, логистикой и мобильностью.',
            'color' => 'from-cyan-500 to-blue-600',
        ],
        'security' => [
            'title' => 'Защитник',
            'emoji' => '🛡️',
            'tagline' => 'Тебе важна ответственность и порядок',
            'description' => 'Ты спокоен в стрессе, дисциплинирован и готов брать на себя ответственность за безопасность других.',
            'color' => 'from-slate-700 to-slate-900',
        ],
        'beauty' => [
            'title' => 'Эстет',
            'emoji' => '✨',
            'tagline' => 'Тебе важна красота и гармония',
            'description' => 'Ты замечаешь детали, любишь ухаживать за собой и другими. Сфера красоты и спорта тебе подходит.',
            'color' => 'from-fuchsia-500 to-purple-600',
        ],
        'science' => [
            'title' => 'Учёный',
            'emoji' => '🔬',
            'tagline' => 'Тебя тянет к познанию мира',
            'description' => 'Тебе интересны закономерности, эксперименты и глубокое понимание. Наука и исследования — твой путь.',
            'color' => 'from-teal-500 to-emerald-700',
        ],
    ];

    private array $interestLabels = [
        'it' => 'Технологии',
        'medicine' => 'Медицина',
        'engineering' => 'Инженерия',
        'education' => 'Образование',
        'trade' => 'Бизнес и продажи',
        'law' => 'Право и финансы',
        'creative' => 'Творчество',
        'production' => 'Производство',
        'transport' => 'Транспорт',
        'security' => 'Безопасность',
        'beauty' => 'Красота и спорт',
        'science' => 'Наука',
    ];

    public function resolve(array $interestScores): array
    {
        if (empty($interestScores)) {
            return $this->archetypes['trade'];
        }

        arsort($interestScores);
        $topSlug = array_key_first($interestScores);

        return array_merge(
            ['slug' => $topSlug],
            $this->archetypes[$topSlug] ?? $this->archetypes['trade']
        );
    }

    public function interestProfile(array $interestScores, int $limit = 5): array
    {
        if (empty($interestScores)) {
            return [];
        }

        arsort($interestScores);
        $max = max($interestScores) ?: 1;

        return collect($interestScores)
            ->take($limit)
            ->map(fn ($score, $slug) => [
                'slug' => $slug,
                'label' => $this->interestLabels[$slug] ?? $slug,
                'score' => $score,
                'percent' => (int) round(($score / $max) * 100),
                'emoji' => $this->archetypes[$slug]['emoji'] ?? '✨',
            ])
            ->values()
            ->all();
    }

    public function personalizedGreeting(?string $name, string $status): string
    {
        $greeting = $name ? "Привет, {$name}!" : 'Привет!';

        $statusMessages = [
            'school_9' => ' Ты на распутье после 9 класса — самое время определиться.',
            'school_11' => ' Скоро выпуск — давай найдём твою профессию до ЕГЭ.',
            'student' => ' Учишься, но сомневаешься? Это нормально — разберёмся вместе.',
            'working' => ' Хочешь сменить сферу? Мы построим твой путь.',
            'exploring' => ' Не знаешь, кем хочешь быть? Начнём с твоих интересов.',
        ];

        return $greeting . ($statusMessages[$status] ?? $statusMessages['exploring']);
    }

    public function statusAdvice(string $status, ?string $name = null): array
    {
        $you = $name ? $name : 'Ты';

        return match ($status) {
            'school_9' => [
                'title' => 'Твой следующий шаг',
                'steps' => [
                    'Посмотри колледжи в своём городе — многие профессии можно освоить за 2–3 года',
                    'Сравни топ-3 профессии из результата: путь, зарплата, где учиться',
                    'Поговори с родителями и профориентатором — покажи им этот результат',
                ],
            ],
            'school_11' => [
                'title' => 'План до выпуска',
                'steps' => [
                    'Выбери 2–3 профессии и узнай, какие предметы ЕГЭ нужны',
                    'Загляни в раздел «Где учиться» — подбери вуз или колледж',
                    'Запишись на день открытых дверей в понравившемся заведении',
                ],
            ],
            'student' => [
                'title' => 'Если сомневаешься в специальности',
                'steps' => [
                    "{$you} можешь попробовать стажировку в одной из рекомендованных сфер",
                    'Посмотри вакансии — поймёшь, что реально ищут работодатели',
                    'Изучи модуль «Смена профессии», если хочешь сменить направление',
                ],
            ],
            'working' => [
                'title' => 'Как сменить сферу',
                'steps' => [
                    'Оцени, какие навыки из текущей работы пригодятся в новой профессии',
                    'Начни с курсов или коротких программ — не обязательно сразу в вуз',
                    'Откликнись на 2–3 вакансии из раздела «Где искать работу»',
                ],
            ],
            default => [
                'title' => 'С чего начать',
                'steps' => [
                    'Открой топ-1 профессию и изучи дорожную карту',
                    'Пройди по ссылкам «Где учиться» и «Где работать»',
                    'Сохрани результат — вернись к нему через пару дней',
                ],
            ],
        };
    }

    public function matchPercent(float $score, float $maxScore): int
    {
        if ($maxScore <= 0) {
            return 70;
        }

        return (int) min(99, max(55, round(($score / $maxScore) * 100)));
    }
}
