@extends('layouts.public')

@section('title', 'Профессии')

@section('content')
    <div class="bg-gradient-to-b from-brand-50/50 to-slate-50">
        <div class="page-container py-10 sm:py-12">
            <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">150+ профессий — найди свою</h1>
                    <p class="mt-2 text-slate-600">Дорожная карта, зарплата в {{ $currentCity->name }} и где учиться</p>
                </div>
                <a href="{{ route('quiz.show') }}" class="btn-glow shrink-0 text-center">
                    ✨ Не знаешь что выбрать? Пройди тест
                </a>
            </div>

            {{-- Фильтры --}}
            <form method="GET" action="{{ route('professions.index') }}" class="card p-4 sm:p-5 mb-6">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-grow relative">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="search"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Поиск по названию..."
                               class="input-field !pl-10">
                    </div>
                    <select name="category" class="input-field sm:w-56" onchange="this.form.submit()">
                        <option value="">Все категории</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->slug }}" @selected(request('category') === $cat->slug)>
                                {{ $cat->icon }} {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary sm:!px-8">Найти</button>
                </div>
            </form>

            {{-- Быстрые фильтры --}}
            @if (!request('search') && !request('category'))
                <div class="flex flex-wrap gap-2 mb-6">
                    @foreach ($categories->take(8) as $cat)
                        <a href="{{ route('professions.index', ['category' => $cat->slug]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:border-brand-300 hover:text-brand-700 hover:bg-brand-50 transition">
                            {{ $cat->icon }} {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($professions->count())
                <p class="text-sm text-slate-500 mb-5">
                    Найдено: <span class="font-semibold text-slate-700">{{ $professions->total() }}</span>
                </p>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($professions as $profession)
                        <x-profession-card :profession="$profession" class="relative" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $professions->withQueryString()->links() }}
                </div>
            @else
                <div class="card text-center py-16 px-6">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <p class="text-slate-700 font-semibold">Профессии не найдены</p>
                    <p class="mt-1 text-sm text-slate-500">Попробуйте изменить параметры поиска</p>
                    <a href="{{ route('professions.index') }}" class="btn-secondary mt-5 !inline-flex">
                        Сбросить фильтры
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
