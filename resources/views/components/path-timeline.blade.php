@props(['steps'])

@php
    $stepIcons = [
        'start' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'assessment' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'school' => 'M12 14l9-5-9-5-9 5 9 5z',
        'exam' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'college' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'university' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'course' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
        'practice' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        'internship' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'residency' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        'accreditation' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
        'license' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
        'work' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'transition' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
    ];
    $stepLabels = [
        'start' => 'Старт',
        'assessment' => 'Анализ',
        'school' => 'Школа',
        'exam' => 'Экзамены',
        'college' => 'Колледж',
        'university' => 'Вуз',
        'course' => 'Курсы',
        'practice' => 'Практика',
        'internship' => 'Стажировка',
        'residency' => 'Ординатура',
        'accreditation' => 'Аккредитация',
        'license' => 'Допуск',
        'work' => 'Работа',
        'transition' => 'Переход',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }}>
  @forelse ($steps as $index => $step)
    @php
        $stepType = data_get($step, 'step_type', 'course');
        $title = data_get($step, 'title', '');
        $description = data_get($step, 'description');
        $durationMonths = data_get($step, 'duration_months');
    @endphp
    <div class="relative flex gap-4 pb-8 last:pb-0">
      @if (! $loop->last)
        <div class="absolute left-5 top-10 bottom-0 w-0.5 bg-indigo-100"></div>
      @endif

      <div class="relative z-10 flex-shrink-0 w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="{{ $stepIcons[$stepType] ?? $stepIcons['course'] }}"/>
        </svg>
      </div>

      <div class="flex-grow min-w-0 pt-1">
        <div class="flex flex-wrap items-center gap-2 mb-1">
          <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
            Шаг {{ $index + 1 }}
          </span>
          @if ($stepType)
            <span class="text-xs text-slate-500">{{ $stepLabels[$stepType] ?? $stepType }}</span>
          @endif
          @if ($durationMonths)
            <span class="text-xs text-slate-400">~{{ $durationMonths }} мес.</span>
          @endif
        </div>
        <h4 class="font-semibold text-slate-900">{{ $title }}</h4>
        @if ($description)
          <p class="text-sm text-slate-600 mt-1 leading-relaxed">{{ $description }}</p>
        @endif
      </div>
    </div>
  @empty
    <p class="text-sm text-slate-500 italic">Дорожная карта для этой профессии пока не заполнена.</p>
  @endforelse
</div>
