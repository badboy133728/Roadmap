@extends('layouts.public')

@section('title', $profession->name)

@section('content')
    @php
        $typeLabels = ['university' => 'Вуз', 'college' => 'Колледж', 'courses' => 'Курсы'];
        $medianSalary = $salaries['middle']->salary_median ?? $salaries['junior']->salary_median ?? null;
    @endphp

    {{-- Hero --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-brand-900 to-fuchsia-900 text-white">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 right-0 w-96 h-96 bg-fuchsia-500 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-cyan-500 rounded-full blur-3xl"></div>
        </div>
        <div class="page-container relative py-10 sm:py-14">
            <nav class="text-sm text-white/60 mb-4">
                <a href="{{ route('professions.index') }}" class="hover:text-white transition">Профессии</a>
                <span class="mx-2">/</span>
                <span class="text-white">{{ $profession->name }}</span>
            </nav>

            @if ($profession->category)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-white/15 border border-white/20 mb-4">
                    {{ $profession->category->icon }} {{ $profession->category->name }}
                </span>
            @endif

            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">{{ $profession->name }}</h1>

            @if ($profession->description)
                <p class="mt-4 text-lg text-white/80 max-w-2xl leading-relaxed">{{ $profession->description }}</p>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($medianSalary)
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 border border-white/20 text-sm font-semibold">
                        💰 от {{ number_format($medianSalary, 0, ',', ' ') }} ₽ в {{ $city->name }}
                    </span>
                @endif
                @if (count($education['items'] ?? []))
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 border border-white/20 text-sm font-semibold">
                        🎓 {{ count($education['items']) }} от ИИ
                    </span>
                @endif
            </div>

            @if (! empty($profession->skills))
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ($profession->skills as $skill)
                        <span class="px-3 py-1 rounded-lg text-xs font-semibold bg-white/10 text-white/90">{{ $skill }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Sticky tabs --}}
    <div class="sticky top-16 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <div class="page-container">
            <nav class="flex gap-1 overflow-x-auto py-2 scrollbar-hide">
                <a href="#path" class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-brand-50 hover:text-brand-700 transition">🗺️ Мой путь</a>
                <a href="#salary" class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-brand-50 hover:text-brand-700 transition">💰 Зарплата</a>
                <a href="#education" class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-fuchsia-50 hover:text-fuchsia-700 transition">
                    🎓 Где учиться
                    @if (count($education['items'] ?? []))
                        <span class="ml-1 px-1.5 py-0.5 rounded-full bg-fuchsia-100 text-fuchsia-700 text-xs">{{ count($education['items']) }}</span>
                    @endif
                </a>
                <a href="#vacancies" class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 transition">
                    💼 Вакансии
                    @if ($vacancies->count())
                        <span class="ml-1 px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs">{{ $vacancies->count() }}</span>
                    @endif
                </a>
                <a href="#skills" class="shrink-0 px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-brand-50 hover:text-brand-700 transition">⚡ Навыки</a>
            </nav>
        </div>
    </div>

    <div class="page-container py-10 space-y-12">

        {{-- Путь --}}
        <section id="path" class="scroll-mt-36">
            <div class="flex items-center gap-3 mb-6">
                <span class="text-3xl">🗺️</span>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900">Твой путь к профессии</h2>
                    @if ($pathPersonalized)
                        <p class="text-sm text-slate-500">
                            @if ($pathTransition && ($pathTransition['type'] ?? '') === 'career_change')
                                Переход: {{ $pathTransition['label'] ?? 'смена профессии' }}
                            @else
                                Персональный маршрут
                            @endif
                            @if ($pathSource === 'ai')
                                <span class="text-violet-600 font-semibold">· ИИ</span>
                            @endif
                            @if ($pathTotalLabel)
                                <span class="text-slate-400">· ~{{ $pathTotalLabel }}</span>
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-slate-500">Пошаговый маршрут — от школы до работы</p>
                    @endif
                </div>
            </div>
            <div class="youth-card p-6 sm:p-8 space-y-4">
                @if ($pathPersonalized && $pathSummary)
                    <div class="rounded-xl bg-gradient-to-r from-brand-50 to-violet-50 border border-brand-100 p-4">
                        <p class="text-sm text-slate-700 leading-relaxed">{{ $pathSummary }}</p>
                    </div>
                @endif

                @if ($pathHint)
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-100 rounded-xl py-2 px-4">
                        {{ $pathHint }}
                        @guest
                            <a href="{{ route('login') }}" class="font-bold underline ml-1">Войти</a>
                            или
                            <a href="{{ route('quiz.show') }}" class="font-bold underline">пройти тест</a>.
                        @else
                            <a href="{{ route('quiz.show') }}" class="font-bold underline ml-1">Пройти тест</a>
                        @endguest
                    </p>
                @endif

                @guest
                    @if (! $pathPersonalized)
                        <p class="text-sm text-brand-700 bg-brand-50 border border-brand-100 rounded-xl py-2 px-4">
                            <a href="{{ route('login') }}" class="font-bold underline">Войди</a> и
                            <a href="{{ route('quiz.show') }}" class="font-bold underline">пройди тест</a> —
                            ИИ построит путь именно под тебя.
                        </p>
                    @endif
                @endguest

                <x-path-timeline :steps="$steps" />
            </div>
        </section>

        {{-- Зарплата --}}
        <section id="salary" class="scroll-mt-36">
            <div class="flex items-center gap-3 mb-6">
                <span class="text-3xl">💰</span>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900">Сколько зарабатывают в {{ $city->name }}</h2>
                    <p class="text-sm text-slate-500">Ориентиры по уровню опыта — данные на {{ now()->format('m.Y') }}</p>
                </div>
            </div>
            <x-salary-card :salaries="$salaries" :city="$city" />
        </section>

        {{-- Где учиться --}}
        <section id="education" class="scroll-mt-36">
            <div class="flex items-center gap-3 mb-6">
                <span class="text-3xl">🎓</span>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900">Где учиться в {{ $city->name }}</h2>
                    <p class="text-sm text-slate-500">
                        ИИ подбирает вузы и колледжи для «{{ $profession->name }}»
                        @auth с учётом профиля и теста @endauth
                    </p>
                </div>
            </div>

            <x-education-institutions
                :items="$education['items'] ?? []"
                :summary="$education['summary'] ?? null"
                :admission-tips="$education['admission_tips'] ?? []"
                :source="$education['source'] ?? 'db'"
                :type-labels="$typeLabels"
            />
        </section>

        {{-- Вакансии --}}
        <section id="vacancies" class="scroll-mt-36">
            <div class="flex items-center gap-3 mb-6">
                <span class="text-3xl">💼</span>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900">Где искать работу в {{ $city->name }}</h2>
                    <p class="text-sm text-slate-500">Примеры вакансий и площадки для поиска по профессии «{{ $profession->name }}»</p>
                </div>
            </div>

            @if ($vacancies->count())
                <div class="grid gap-4 sm:grid-cols-2 mb-8">
                    @foreach ($vacancies as $vacancy)
                        <div class="youth-card p-5 sm:p-6 hover:shadow-card-hover transition-all group">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-extrabold text-slate-900 group-hover:text-emerald-700 transition leading-snug">
                                        {{ $vacancy->title }}
                                    </h3>
                                    <p class="text-sm text-slate-600 mt-1">{{ $vacancy->company }}</p>
                                </div>
                                @if ($vacancy->salary_text)
                                    <span class="shrink-0 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold">
                                        {{ $vacancy->salary_text }}
                                    </span>
                                @endif
                            </div>
                            @if ($vacancy->description)
                                <p class="text-sm text-slate-500 mt-3">{{ $vacancy->description }}</p>
                            @endif
                            @if ($vacancy->experience_level)
                                <span class="inline-block mt-3 text-xs font-semibold text-slate-400 uppercase">
                                    {{ $vacancy->experience_level === 'intern' ? 'Стажировка' : ($vacancy->experience_level === 'junior' ? 'Без опыта / junior' : 'С опытом') }}
                                </span>
                            @endif
                            <a href="{{ $vacancy->external_url }}" target="_blank" rel="noopener"
                               class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-emerald-600 hover:text-emerald-800">
                                Смотреть на {{ $vacancy->source ?? 'сайте' }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="youth-card p-6 sm:p-8">
                <h3 class="font-extrabold text-slate-900 mb-2">🔍 Искать самому на площадках</h3>
                <p class="text-sm text-slate-500 mb-5">Нажми — откроется поиск «{{ $profession->name }}» в {{ $city->name }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($jobPlatforms as $platform)
                        <a href="{{ $platform->searchUrl($searchQuery) }}" target="_blank" rel="noopener"
                           class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition group">
                            <span class="text-2xl">{{ $platform->icon }}</span>
                            <div>
                                <p class="font-bold text-slate-900 group-hover:text-emerald-700">{{ $platform->name }}</p>
                                @if ($platform->description)
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $platform->description }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Навыки и перспективы --}}
        <section id="skills" class="scroll-mt-36">
            <div class="grid gap-6 sm:grid-cols-2">
                @if (! empty($profession->skills))
                    <div class="youth-card p-6">
                        <h3 class="font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                            <span>⚡</span> Нужные навыки
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($profession->skills as $skill)
                                <span class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-brand-50 to-fuchsia-50 text-sm font-semibold text-slate-700 border border-brand-100">{{ $skill }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($profession->outlook)
                    <div class="youth-card p-6">
                        <h3 class="font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                            <span>📈</span> Перспективы
                        </h3>
                        <p class="text-slate-600 leading-relaxed">{{ $profession->outlook }}</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- CTA --}}
        <div class="rounded-3xl bg-gradient-to-r from-brand-600 via-fuchsia-600 to-cyan-500 p-6 sm:p-8 text-white text-center">
            <h3 class="text-xl font-extrabold">Не уверен, что это твоё?</h3>
            <p class="text-white/80 mt-2 text-sm">Пройди личный тест — подберём профессии именно для тебя</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <a href="{{ route('quiz.show') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-brand-700 font-bold rounded-xl hover:bg-white/90 transition shadow-lg">
                    ✨ Пройти тест
                </a>
                <a href="{{ route('career-change.show', ['to' => $profession->id]) }}" class="inline-flex items-center gap-2 px-6 py-3 border-2 border-white/40 text-white font-bold rounded-xl hover:bg-white/10 transition">
                    Сменить профессию
                </a>
            </div>
        </div>
    </div>
@endsection
