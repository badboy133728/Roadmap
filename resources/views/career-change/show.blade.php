@extends('layouts.public')

@section('title', 'Смена профессии')

@section('content')
    <div class="bg-gradient-to-b from-brand-50/50 to-slate-50">
        <div class="page-container py-10 sm:py-14">
            <div class="max-w-xl mx-auto">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white mb-4 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Смена профессии</h1>
                    <p class="mt-2 text-slate-600">
                        Выберите текущую и желаемую профессию — построим план перехода
                    </p>
                </div>

                <form method="POST" action="{{ route('career-change.result') }}" class="card p-6 sm:p-8 space-y-6">
                    @csrf

                    <div>
                        <label for="from_profession_id" class="block text-sm font-semibold text-slate-700 mb-2">
                            Текущая профессия
                        </label>
                        <select id="from_profession_id" name="from_profession_id" class="input-field">
                            <option value="">Нет опыта / начинаю с нуля</option>
                            @foreach ($professions as $profession)
                                <option value="{{ $profession->id }}" @selected(old('from_profession_id', request('from')) == $profession->id)>
                                    {{ $profession->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('from_profession_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-center">
                        <div class="w-10 h-10 rounded-full bg-brand-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <label for="to_profession_id" class="block text-sm font-semibold text-slate-700 mb-2">
                            Желаемая профессия <span class="text-red-500">*</span>
                        </label>
                        <select id="to_profession_id" name="to_profession_id" required class="input-field">
                            <option value="">Выберите профессию</option>
                            @foreach ($professions as $profession)
                                <option value="{{ $profession->id }}" @selected(old('to_profession_id', request('to')) == $profession->id)>
                                    {{ $profession->name }}
                                    @if ($profession->category) ({{ $profession->category->name }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('to_profession_id')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn-primary w-full">
                        Построить план перехода
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
