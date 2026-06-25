@extends('layouts.public')

@section('title', 'Твой результат')

@section('content')
    @php
        $professionNotes = collect($aiInsights['profession_notes'] ?? [])->keyBy('profession_name');
        $isAiSource = ($aiInsights['source'] ?? '') === 'ai';
        $archetypeService = app(\App\Services\PersonalityArchetypeService::class);
        $archetypeSlug = $archetype['slug'] ?? 'trade';
        $archetypeGradient = $archetypeService->gradientClass($archetypeSlug);
        $archetypeGradientStyle = $archetypeService->gradientStyle($archetypeSlug);
    @endphp

    <div class="mesh-bg min-h-[calc(100vh-4rem)]">
        <div class="page-container py-10 sm:py-14">
            <div class="max-w-2xl mx-auto">

                @if ($greeting)
                    <p class="text-center text-lg font-semibold text-slate-700 mb-6 animate-fade-in">{{ $greeting }}</p>
                @endif

                @if ($archetype)
                    <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 mb-8 text-white shadow-xl animate-slide-up {{ $archetypeGradient }}"
                         style="{{ $archetypeGradientStyle }}">
                        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white/10"></div>
                        <div class="absolute inset-0 bg-black/5 pointer-events-none"></div>
                        <div class="relative z-10">
                            <span class="text-5xl drop-shadow-sm">{{ $archetype['emoji'] ?? '✨' }}</span>
                            <p class="text-sm font-bold text-white/90 mt-4 uppercase tracking-wider">Твой тип личности</p>
                            <h1 class="text-2xl sm:text-3xl font-extrabold mt-1 text-white drop-shadow-sm">{{ $archetype['title'] ?? 'Искатель' }}</h1>
                            <p class="text-white/95 font-semibold mt-2 text-lg">{{ $archetype['tagline'] ?? '' }}</p>
                            <p class="text-white/90 text-sm sm:text-base mt-3 leading-relaxed">{{ $archetype['description'] ?? '' }}</p>
                        </div>
                    </div>
                @endif

                @if (! empty($interestProfile))
                    <div class="youth-card p-6 mb-8 animate-slide-up">
                        <h2 class="text-lg font-extrabold text-slate-900 mb-1">Твой интерес-профиль</h2>
                        <p class="text-sm text-slate-500 mb-4">Сферы, которые тебе ближе всего</p>
                        <div class="space-y-3">
                            @foreach ($interestProfile as $interest)
                                <div>
                                    <div class="flex items-center justify-between text-sm mb-1">
                                        <span class="font-semibold text-slate-700">{{ $interest['emoji'] }} {{ $interest['label'] }}</span>
                                        <span class="text-brand-600 font-bold">{{ $interest['percent'] }}%</span>
                                    </div>
                                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-brand-500 to-fuchsia-500 rounded-full transition-all"
                                             style="width: {{ $interest['percent'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (! empty($aiInsights['summary']))
                    <div class="relative overflow-hidden rounded-2xl p-5 sm:p-6 mb-8 text-white shadow-lg bg-gradient-to-br from-violet-600 via-fuchsia-600 to-cyan-500 animate-slide-up">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xl">✨</span>
                            <span class="text-xs font-bold uppercase tracking-wider opacity-90">
                                {{ $isAiSource ? 'ИИ-разбор' : 'Персональный разбор' }}
                            </span>
                        </div>
                        <p class="text-sm sm:text-base leading-relaxed">{{ $aiInsights['summary'] }}</p>
                        @if (! empty($aiInsights['motivation']))
                            <p class="mt-3 text-sm font-bold">{{ $aiInsights['motivation'] }}</p>
                        @endif
                        <a href="#detailed-analysis" class="inline-block mt-4 text-xs font-bold underline opacity-90 hover:opacity-100">
                            Читать полный разбор ↓
                        </a>
                    </div>
                @endif

                <h2 class="text-xl font-extrabold text-slate-900 mb-2 text-center">Твои топ-профессии</h2>
                <p class="text-sm text-slate-500 text-center mb-6">Подобрано по ответам, уточнениям и приоритетам</p>

                <div class="space-y-4">
                    @foreach ($recommendations as $index => $item)
                        @php
                            $professionName = $item['profession_name'] ?? $item['name'] ?? 'Профессия';
                            $note = $professionNotes->get($professionName);
                        @endphp
                        <article class="youth-card p-5 sm:p-6 animate-slide-up group">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 text-center">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-100 to-fuchsia-100 flex items-center justify-center text-2xl">
                                        {{ $item['category_icon'] ?? '💼' }}
                                    </div>
                                    @if ($note && ! empty($note['fit_score']))
                                        <p class="text-xs font-extrabold text-brand-600 mt-1.5">{{ $note['fit_score'] }}%</p>
                                    @elseif (isset($item['match_percent']))
                                        <p class="text-xs font-extrabold text-brand-600 mt-1.5">{{ $item['match_percent'] }}%</p>
                                    @endif
                                </div>
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-bold text-fuchsia-600 bg-fuchsia-50 px-2 py-0.5 rounded-full">#{{ $index + 1 }}</span>
                                        @if (! empty($item['category_name']))
                                            <span class="text-xs text-slate-400">{{ $item['category_name'] }}</span>
                                        @endif
                                    </div>
                                    <h3 class="text-lg font-extrabold text-slate-900 group-hover:text-brand-700 transition">
                                        {{ $professionName }}
                                    </h3>

                                    @php $matchPercent = $note['fit_score'] ?? $item['match_percent'] ?? null; @endphp
                                    @if ($matchPercent)
                                        <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-brand-500 to-fuchsia-500 rounded-full"
                                                 style="width: {{ $matchPercent }}%"></div>
                                        </div>
                                    @endif

                                    <p class="text-sm text-slate-600 mt-3 leading-relaxed">
                                        {{ $note['note'] ?? $item['reason'] ?? '' }}
                                    </p>

                                    @if (! empty($note['pros']))
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            @foreach ($note['pros'] as $pro)
                                                <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full">+ {{ $pro }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (! empty($note['cons']))
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($note['cons'] as $con)
                                                <span class="text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full">− {{ $con }}</span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <a href="{{ route('professions.show', $item['profession_slug'] ?? $item['slug']) }}#path"
                                           class="inline-flex items-center gap-1 text-sm font-bold text-brand-600 hover:text-brand-800">
                                            Мой путь →
                                        </a>
                                        <a href="{{ route('professions.show', $item['profession_slug'] ?? $item['slug']) }}#education"
                                           class="inline-flex items-center gap-1 text-sm font-bold text-fuchsia-600 hover:text-fuchsia-800">
                                            Где учиться →
                                        </a>
                                        <a href="{{ route('professions.show', $item['profession_slug'] ?? $item['slug']) }}#vacancies"
                                           class="inline-flex items-center gap-1 text-sm font-bold text-emerald-600 hover:text-emerald-800">
                                            Вакансии →
                                        </a>
                                    </div>

                                    @php
                                        $profId = $item['profession_id'] ?? null;
                                        $profEducation = $profId ? ($educationByProfession[$profId] ?? null) : null;
                                    @endphp
                                    @if (! empty($profEducation['items']))
                                        <div class="mt-4 pt-4 border-t border-slate-100">
                                            <p class="text-xs font-bold text-fuchsia-600 uppercase tracking-wider mb-2">ИИ: где учиться</p>
                                            <ul class="space-y-2">
                                                @foreach (array_slice($profEducation['items'], 0, 2) as $school)
                                                    <li class="text-sm text-slate-600">
                                                        <span class="font-semibold text-slate-800">{{ $school->name }}</span>
                                                        @if (! empty($school->why_fit))
                                                            <span class="block text-xs text-slate-500 mt-0.5">{{ Str::limit($school->why_fit, 120) }}</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if (! empty($aiInsights))
                    <div id="detailed-analysis" class="mt-10 mb-8 animate-slide-up space-y-4 scroll-mt-8">
                        <div class="text-center mb-2">
                            <h2 class="text-xl font-extrabold text-slate-900">Подробный разбор</h2>
                            <p class="text-sm text-slate-500">На основе теста и уточняющих вопросов</p>
                        </div>

                        @if (! empty($aiInsights['personality_traits']))
                            <div class="youth-card p-5">
                                <h3 class="font-extrabold text-slate-900 mb-3">🧠 Черты характера</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($aiInsights['personality_traits'] as $trait)
                                        <span class="text-xs font-semibold bg-violet-50 text-violet-700 px-3 py-1.5 rounded-full">{{ $trait }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($aiInsights['work_style']))
                            <div class="youth-card p-5">
                                <h3 class="font-extrabold text-slate-900 mb-2">💼 Стиль работы</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ $aiInsights['work_style'] }}</p>
                            </div>
                        @endif

                        <div class="grid sm:grid-cols-2 gap-4">
                            @if (! empty($aiInsights['strengths']))
                                <div class="youth-card p-5">
                                    <h3 class="font-extrabold text-slate-900 mb-3">💪 Сильные стороны</h3>
                                    <ul class="space-y-2">
                                        @foreach ($aiInsights['strengths'] as $strength)
                                            <li class="text-sm text-slate-600 flex gap-2"><span class="text-emerald-500">✓</span>{{ $strength }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if (! empty($aiInsights['growth_areas']))
                                <div class="youth-card p-5">
                                    <h3 class="font-extrabold text-slate-900 mb-3">📈 Зоны роста</h3>
                                    <ul class="space-y-2">
                                        @foreach ($aiInsights['growth_areas'] as $area)
                                            <li class="text-sm text-slate-600 flex gap-2"><span class="text-amber-500">→</span>{{ $area }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        @if (! empty($aiInsights['skills_to_learn']))
                            <div class="youth-card p-5">
                                <h3 class="font-extrabold text-slate-900 mb-3">🛠 Навыки для прокачки</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($aiInsights['skills_to_learn'] as $skill)
                                        <span class="text-xs font-semibold bg-brand-50 text-brand-700 px-3 py-1.5 rounded-full">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($aiInsights['education_path']))
                            <div class="youth-card p-5">
                                <h3 class="font-extrabold text-slate-900 mb-2">🎓 Путь обучения</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ $aiInsights['education_path'] }}</p>
                            </div>
                        @endif

                        @if (! empty($educationByProfession))
                            <div class="youth-card p-5">
                                <h3 class="font-extrabold text-slate-900 mb-1">🎓 Где учиться — подбор ИИ</h3>
                                <p class="text-sm text-slate-500 mb-4">Вузы и колледжи для топ-профессий в твоём городе</p>
                                <div class="space-y-6">
                                    @foreach (collect($recommendations)->take(3) as $item)
                                        @php
                                            $profId = $item['profession_id'] ?? null;
                                            $block = $profId ? ($educationByProfession[$profId] ?? null) : null;
                                        @endphp
                                        @if ($block && count($block['items'] ?? []))
                                            <div>
                                                <h4 class="font-bold text-slate-800 mb-2">{{ $item['profession_name'] ?? 'Профессия' }}</h4>
                                                <x-education-institutions
                                                    :items="$block['items']"
                                                    :summary="$block['summary'] ?? null"
                                                    :admission-tips="$block['admission_tips'] ?? []"
                                                    :source="$block['source'] ?? 'db'"
                                                />
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($aiInsights['first_steps']))
                            <div class="youth-card p-5">
                                <h3 class="font-extrabold text-slate-900 mb-3">🚀 Первые шаги на этой неделе</h3>
                                <ol class="space-y-2">
                                    @foreach ($aiInsights['first_steps'] as $i => $step)
                                        <li class="flex items-start gap-2 text-sm text-slate-600">
                                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                                            {{ $step }}
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif

                        @if (! empty($aiInsights['vacancy_examples']))
                            <div class="youth-card p-5">
                                <h3 class="font-extrabold text-slate-900 mb-1">💼 Актуальные вакансии для тебя</h3>
                                <p class="text-sm text-slate-500 mb-4">Реальные примеры из базы — можно откликнуться или ориентироваться на требования</p>
                                <div class="space-y-3">
                                    @foreach ($aiInsights['vacancy_examples'] as $vacancy)
                                        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-bold text-brand-600 uppercase tracking-wide">{{ $vacancy['profession_name'] ?? '' }}</p>
                                                    <h4 class="font-extrabold text-slate-900 mt-1">{{ $vacancy['title'] ?? '' }}</h4>
                                                    <p class="text-sm text-slate-600 mt-1">{{ $vacancy['company'] ?? '' }}</p>
                                                </div>
                                                @if (! empty($vacancy['salary_text']))
                                                    <span class="text-sm font-bold text-emerald-700 whitespace-nowrap">{{ $vacancy['salary_text'] }}</span>
                                                @endif
                                            </div>
                                            @if (! empty($vacancy['why_relevant']))
                                                <p class="text-sm text-slate-600 mt-3 leading-relaxed">{{ $vacancy['why_relevant'] }}</p>
                                            @endif
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @if (! empty($vacancy['profession_slug']))
                                                    <a href="{{ route('professions.show', $vacancy['profession_slug']) }}#vacancies"
                                                       class="text-xs font-bold text-brand-600 hover:text-brand-800">Все вакансии →</a>
                                                @endif
                                                @if (! empty($vacancy['external_url']))
                                                    <a href="{{ $vacancy['external_url'] }}" target="_blank" rel="noopener"
                                                       class="text-xs font-bold text-fuchsia-600 hover:text-fuchsia-800">Открыть вакансию ↗</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($aiInsights['less_suitable']))
                            <div class="youth-card p-5 bg-slate-50">
                                <h3 class="font-extrabold text-slate-700 mb-3">⚠️ Пока менее подходит</h3>
                                @foreach ($aiInsights['less_suitable'] as $item)
                                    <p class="text-sm text-slate-600"><strong>{{ $item['area'] ?? '' }}</strong> — {{ $item['reason'] ?? '' }}</p>
                                @endforeach
                            </div>
                        @endif

                        @if (! empty($aiInsights['personal_advice']))
                            <div class="youth-card p-5 border-l-4 border-violet-500">
                                <h3 class="font-extrabold text-slate-900 mb-2">💬 Персональный совет</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ $aiInsights['personal_advice'] }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($statusAdvice)
                    <div class="youth-card p-6 mt-8 animate-slide-up">
                        <h3 class="font-extrabold text-slate-900 mb-4">📋 {{ $statusAdvice['title'] }}</h3>
                        <ol class="space-y-2">
                            @foreach ($statusAdvice['steps'] as $step)
                                <li class="flex items-start gap-2 text-sm text-slate-600">
                                    <span class="text-brand-500 font-bold">→</span>
                                    {{ $step }}
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                <div class="mt-10 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('quiz.show') }}" class="btn-secondary">Пройти заново</a>
                    <a href="{{ route('professions.index') }}" class="btn-glow">Все профессии</a>
                </div>
            </div>
        </div>
    </div>
@endsection
