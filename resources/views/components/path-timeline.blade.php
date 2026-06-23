@props(['steps'])

@php
    $stepIcons = [
        'school' => 'M12 14l9-5-9-5-9 5 9 5z',
        'college' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'university' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'course' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
        'work' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    ];
    $stepLabels = [
        'school' => 'Школа',
        'college' => 'Колледж',
        'university' => 'Вуз',
        'course' => 'Курсы',
        'work' => 'Работа',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }}>
  @forelse ($steps as $index => $step)
    <div class="relative flex gap-4 pb-8 last:pb-0">
      @if (! $loop->last)
        <div class="absolute left-5 top-10 bottom-0 w-0.5 bg-indigo-100"></div>
      @endif

      <div class="relative z-10 flex-shrink-0 w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center shadow-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="{{ $stepIcons[$step->step_type ?? 'course'] ?? $stepIcons['course'] }}"/>
        </svg>
      </div>

      <div class="flex-grow min-w-0 pt-1">
        <div class="flex flex-wrap items-center gap-2 mb-1">
          <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
            Шаг {{ $index + 1 }}
          </span>
          @if (! empty($step->step_type))
            <span class="text-xs text-slate-500">{{ $stepLabels[$step->step_type] ?? $step->step_type }}</span>
          @endif
          @if (! empty($step->duration_months))
            <span class="text-xs text-slate-400">~{{ $step->duration_months }} мес.</span>
          @endif
        </div>
        <h4 class="font-semibold text-slate-900">{{ $step->title }}</h4>
        @if (! empty($step->description))
          <p class="text-sm text-slate-600 mt-1">{{ $step->description }}</p>
        @endif
      </div>
    </div>
  @empty
    <p class="text-sm text-slate-500 italic">Дорожная карта для этой профессии пока не заполнена.</p>
  @endforelse
</div>
