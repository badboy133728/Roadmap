@props(['salaries', 'city' => null])

@php
    $levels = [
        'junior' => ['label' => 'Junior', 'color' => 'bg-sky-50 border-sky-200 text-sky-800'],
        'middle' => ['label' => 'Middle', 'color' => 'bg-indigo-50 border-indigo-200 text-indigo-800'],
        'senior' => ['label' => 'Senior', 'color' => 'bg-violet-50 border-violet-200 text-violet-800'],
    ];
    $formatSalary = fn ($amount) => number_format($amount, 0, ',', ' ');
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200 overflow-hidden']) }}>
  <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-blue-50">
    <h3 class="font-semibold text-slate-900">Зарплаты</h3>
    @if ($city)
      <p class="text-sm text-slate-500 mt-0.5">в г. {{ $city->name }}</p>
    @endif
  </div>

  <div class="p-5 grid gap-4 sm:grid-cols-3">
    @foreach ($levels as $level => $meta)
      @php $salary = $salaries[$level] ?? null; @endphp
      <div class="rounded-lg border p-4 {{ $meta['color'] }}">
        <p class="text-xs font-semibold uppercase tracking-wide opacity-75 mb-2">{{ $meta['label'] }}</p>
        @if ($salary)
          <p class="text-xl font-bold">{{ $formatSalary($salary->salary_median) }} ₽</p>
          <p class="text-xs mt-1 opacity-75">
            {{ $formatSalary($salary->salary_min) }} — {{ $formatSalary($salary->salary_max) }} ₽
          </p>
          @if ($salary->source)
            <p class="text-xs mt-2 opacity-60">Источник: {{ $salary->source }}</p>
          @endif
        @else
          <p class="text-sm opacity-75">Нет данных</p>
        @endif
      </div>
    @endforeach
  </div>
</div>
