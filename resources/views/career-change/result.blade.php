@extends('layouts.public')

@section('title', 'План смены профессии')

@section('content')
    @php
        $difficultyLabels = [
            'easy' => ['label' => 'Лёгкий', 'color' => 'bg-emerald-100 text-emerald-800'],
            'medium' => ['label' => 'Средний', 'color' => 'bg-amber-100 text-amber-800'],
            'hard' => ['label' => 'Сложный', 'color' => 'bg-red-100 text-red-800'],
        ];
        $difficulty = $difficultyLabels[$plan['difficulty'] ?? 'medium'] ?? $difficultyLabels['medium'];
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <nav class="text-sm text-slate-500 mb-4">
                <a href="{{ route('career-change.show') }}" class="hover:text-indigo-600 transition">Смена профессии</a>
                <span class="mx-2">/</span>
                <span class="text-slate-800">План перехода</span>
            </nav>

            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">План перехода</h1>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                @if ($plan['from'] ?? null)
                    <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 font-medium">{{ $plan['from']->name }}</span>
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                @else
                    <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-500 italic">С нуля</span>
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                @endif
                <span class="px-3 py-1.5 rounded-lg bg-indigo-100 text-indigo-800 font-semibold">{{ $plan['to']->name }}</span>
            </div>

            @if (! empty($plan['description']))
                <p class="mt-4 text-slate-600 leading-relaxed">{{ $plan['description'] }}</p>
            @endif

            <div class="mt-4 flex flex-wrap gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $difficulty['color'] }}">
                    Сложность: {{ $difficulty['label'] }}
                </span>
                @if (! empty($plan['duration_months']))
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ~{{ $plan['duration_months'] }} мес.
                    </span>
                @endif
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2 mb-8">
            <section class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900 flex items-center gap-2 mb-4">
                    <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    Общие навыки
                </h2>
                @if (! empty($plan['shared_skills']))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($plan['shared_skills'] as $skill)
                            <span class="inline-flex px-2.5 py-1 rounded-lg text-sm bg-emerald-50 text-emerald-800 border border-emerald-200">{{ $skill }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500 italic">Нет пересечений — вы начинаете с нуля или смена радикальная</p>
                @endif
            </section>

            <section class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900 flex items-center gap-2 mb-4">
                    <span class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </span>
                    Нужно освоить
                </h2>
                @if (! empty($plan['missing_skills']))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($plan['missing_skills'] as $skill)
                            <span class="inline-flex px-2.5 py-1 rounded-lg text-sm bg-amber-50 text-amber-800 border border-amber-200">{{ $skill }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500 italic">Все ключевые навыки уже есть</p>
                @endif
            </section>
        </div>

        <section class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-8">
            <h2 class="text-xl font-bold text-slate-900 mb-2">Шаги перехода</h2>
            @if (! empty($plan['path_summary']))
                <p class="text-sm text-slate-600 mb-4 leading-relaxed">{{ $plan['path_summary'] }}</p>
            @endif
            @if (($plan['path_source'] ?? '') === 'ai')
                <p class="text-xs text-violet-600 font-semibold mb-4">Маршрут построен ИИ · {{ $plan['path_total_label'] ?? '' }}</p>
            @endif
            <x-path-timeline :steps="$plan['steps']" />
        </section>

        @if (! empty($plan['education']))
            <section class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-8">
                <h2 class="text-xl font-bold text-slate-900 mb-4">Где учиться в {{ $plan['city']->name ?? '' }}</h2>
                <x-education-institutions
                    :items="$plan['education']['items'] ?? []"
                    :summary="$plan['education']['summary'] ?? null"
                    :admission-tips="$plan['education']['admission_tips'] ?? []"
                    :source="$plan['education']['source'] ?? 'db'"
                />
            </section>
        @endif

        <div class="flex flex-wrap gap-3 justify-center">
            <a href="{{ route('professions.show', $plan['to']) }}"
               class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
                Подробнее о {{ $plan['to']->name }}
            </a>
            <a href="{{ route('career-change.show') }}"
               class="px-5 py-2.5 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                Другой переход
            </a>
        </div>

        <p class="mt-8 text-center text-xs text-slate-400">
            Рекомендации носят ознакомительный характер
        </p>
    </div>
@endsection
