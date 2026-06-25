@props([
    'items' => [],
    'summary' => null,
    'admissionTips' => [],
    'source' => 'db',
    'typeLabels' => [],
])

@php
    $defaultTypeLabels = [
        'university' => 'Вуз',
        'college' => 'Колледж',
        'courses' => 'Курсы',
    ];
    $labels = array_merge($defaultTypeLabels, $typeLabels);
@endphp

<div class="space-y-4">
    @if ($summary)
        <div class="rounded-xl bg-gradient-to-r from-fuchsia-50 to-brand-50 border border-fuchsia-100 p-4">
            <p class="text-sm text-slate-700 leading-relaxed">{{ $summary }}</p>
            @if ($source === 'ai')
                <p class="text-xs text-violet-600 font-semibold mt-2">Подобрано ИИ с учётом профиля и теста</p>
            @endif
        </div>
    @endif

    @if (count($admissionTips))
        <div class="rounded-xl bg-amber-50 border border-amber-100 p-4">
            <p class="text-xs font-bold text-amber-800 uppercase tracking-wider mb-2">Советы по поступлению</p>
            <ul class="space-y-1">
                @foreach ($admissionTips as $tip)
                    <li class="text-sm text-amber-900 flex items-start gap-2">
                        <span class="text-amber-500 mt-0.5">•</span>
                        {{ $tip }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (count($items))
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($items as $institution)
                <div class="youth-card p-5 sm:p-6 hover:shadow-card-hover transition-all group">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold
                                {{ ($institution->type ?? '') === 'university' ? 'bg-brand-100 text-brand-700' : (($institution->type ?? '') === 'college' ? 'bg-amber-100 text-amber-700' : 'bg-fuchsia-100 text-fuchsia-700') }}">
                                {{ $institution->type_label ?? $labels[$institution->type ?? ''] ?? $institution->type }}
                            </span>
                            <h3 class="font-extrabold text-slate-900 mt-2 group-hover:text-brand-700 transition leading-snug">
                                {{ $institution->name }}
                            </h3>
                            @if (! empty($institution->address))
                                <p class="text-sm text-slate-500 mt-1">📍 {{ $institution->address }}</p>
                            @endif
                        </div>
                    </div>

                    @if (! empty($institution->why_fit))
                        <p class="text-sm text-slate-600 leading-relaxed mb-3">{{ $institution->why_fit }}</p>
                    @endif

                    @if (! empty($institution->programs))
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Программы</p>
                            <ul class="space-y-2">
                                @foreach ($institution->programs as $program)
                                    <li class="flex items-start gap-2 text-sm">
                                        <span class="text-brand-500 mt-0.5">→</span>
                                        <div>
                                            <span class="font-semibold text-slate-800">{{ $program->name }}</span>
                                            <span class="text-slate-400 text-xs ml-1">
                                                @if (! empty($program->duration_years)){{ $program->duration_years }} лет@endif
                                                @if (! empty($program->study_form)) · {{ $program->study_form }}@endif
                                            </span>
                                            @if (! empty($program->entrance_notes))
                                                <p class="text-xs text-slate-500 mt-0.5">{{ $program->entrance_notes }}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($institution->website))
                        <a href="{{ str_starts_with($institution->website, 'http') ? $institution->website : 'https://' . $institution->website }}"
                           target="_blank" rel="noopener"
                           class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-brand-600 hover:text-brand-800">
                            Перейти на сайт
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="youth-card p-8 text-center">
            <span class="text-4xl">🏫</span>
            <p class="mt-3 font-semibold text-slate-700">Программы для этой профессии скоро появятся</p>
            <a href="{{ route('quiz.show') }}" class="btn-glow mt-5 inline-flex">Пройти тест — подберём направление</a>
        </div>
    @endif
</div>
