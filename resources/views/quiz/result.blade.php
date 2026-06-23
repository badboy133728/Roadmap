@extends('layouts.public')

@section('title', 'Твой результат')

@section('content')
    <div class="mesh-bg min-h-[calc(100vh-4rem)]">
        <div class="page-container py-10 sm:py-14">
            <div class="max-w-2xl mx-auto">

                @if ($greeting)
                    <p class="text-center text-lg font-semibold text-slate-700 mb-6 animate-fade-in">{{ $greeting }}</p>
                @endif

                @if ($archetype)
                    <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 mb-8 text-white shadow-xl animate-slide-up bg-gradient-to-br {{ $archetype['color'] ?? 'from-brand-500 to-indigo-600' }}">
                        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white/10"></div>
                        <div class="relative">
                            <span class="text-5xl">{{ $archetype['emoji'] ?? '✨' }}</span>
                            <p class="text-sm font-semibold text-white/80 mt-4 uppercase tracking-wider">Твой тип личности</p>
                            <h1 class="text-2xl sm:text-3xl font-extrabold mt-1">{{ $archetype['title'] ?? 'Искатель' }}</h1>
                            <p class="text-white/90 font-medium mt-2">{{ $archetype['tagline'] ?? '' }}</p>
                            <p class="text-white/80 text-sm mt-3 leading-relaxed">{{ $archetype['description'] ?? '' }}</p>
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

                <h2 class="text-xl font-extrabold text-slate-900 mb-2 text-center">Твои топ-профессии</h2>
                <p class="text-sm text-slate-500 text-center mb-6">Подобрано по ответам, приоритетам и твоему статусу</p>

                <div class="space-y-4">
                    @foreach ($recommendations as $index => $item)
                        <article class="youth-card p-5 sm:p-6 animate-slide-up group">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 text-center">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-100 to-fuchsia-100 flex items-center justify-center text-2xl">
                                        {{ $item['category_icon'] ?? '💼' }}
                                    </div>
                                    @if (isset($item['match_percent']))
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
                                        {{ $item['profession_name'] ?? $item['name'] ?? 'Профессия' }}
                                    </h3>

                                    @if (isset($item['match_percent']))
                                        <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-brand-500 to-fuchsia-500 rounded-full"
                                                 style="width: {{ $item['match_percent'] }}%"></div>
                                        </div>
                                    @endif

                                    @if (! empty($item['reason']))
                                        <p class="text-sm text-slate-600 mt-3 leading-relaxed">{{ $item['reason'] }}</p>
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
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

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
