@extends('layouts.public')

@section('title', 'Твой личный тест')

@section('content')
    @php
        $questionsData = $quiz->questions->map(fn ($q) => [
            'id' => $q->id,
            'question' => $q->question,
            'emoji' => $q->emoji,
            'hint' => $q->hint,
            'target_statuses' => $q->target_statuses,
            'options' => $q->options->map(fn ($o) => ['id' => $o->id, 'text' => $o->text])->values(),
        ])->values();
    @endphp

    <div class="mesh-bg min-h-[calc(100vh-4rem)]">
        <div class="page-container py-8 sm:py-12">
            <div class="max-w-2xl mx-auto" x-data="quizWizard({{ Js::from($questionsData) }})">

                <div class="mb-6">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-500 mb-2">
                        <span x-text="phaseLabel"></span>
                        <span class="text-brand-600" x-text="overallProgress + '%'"></span>
                    </div>
                    <div class="h-2 bg-white/80 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-gradient-to-r from-fuchsia-500 via-brand-500 to-cyan-400 rounded-full transition-all duration-500"
                             :style="'width: ' + overallProgress + '%'"></div>
                    </div>
                </div>

                <div x-show="encouragement" x-transition
                     class="mb-4 text-center text-sm font-bold text-brand-700 bg-brand-50 border border-brand-100 rounded-xl py-2 px-4"
                     x-text="encouragement"></div>

                <form method="POST" action="{{ route('quiz.submit') }}" @submit="!allAnswered && $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="name" :value="profile.name">
                    <input type="hidden" name="status" :value="profile.status">
                    <template x-for="(priority, idx) in priorities" :key="'priority-' + idx">
                        <input type="hidden" :name="'priorities[' + idx + ']'" :value="priority">
                    </template>
                    <template x-for="q in visibleQuestions" :key="'hidden-' + q.id">
                        <input type="hidden" :name="'answers[' + q.id + ']'" :value="answers[q.id] ?? ''">
                    </template>

                    {{-- Профиль --}}
                    <div x-show="phase === 'profile'" x-cloak class="animate-slide-up">
                        <div class="text-center mb-8">
                            <span class="text-5xl mb-4 block">👋</span>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Давай познакомимся</h1>
                            <p class="mt-2 text-slate-600">Тест подстроится под твой возраст и цели</p>
                        </div>

                        <div class="youth-card p-6 sm:p-8 space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Как тебя зовут? <span class="font-normal text-slate-400">(необязательно)</span></label>
                                <input type="text" x-model="profile.name" maxlength="50" placeholder="Например, Аня"
                                       class="input-field text-base !py-3">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-3">Кто ты сейчас?</label>
                                <div class="space-y-2">
                                    <template x-for="opt in statusOptions" :key="opt.value">
                                        <button type="button"
                                                @click="selectStatus(opt.value)"
                                                :class="profile.status === opt.value ? 'quiz-option-selected' : 'quiz-option-default'">
                                            <span class="text-2xl" x-text="opt.emoji"></span>
                                            <span class="font-semibold text-slate-800" x-text="opt.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <button type="button" @click="startPriorities()" :disabled="!profileComplete"
                                    class="btn-glow w-full justify-center">
                                Дальше — что для тебя важно
                            </button>
                        </div>
                    </div>

                    {{-- Приоритеты --}}
                    <div x-show="phase === 'priorities'" x-cloak class="animate-slide-up">
                        <div class="text-center mb-8">
                            <span class="text-5xl mb-4 block">🎯</span>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Что для тебя важнее?</h1>
                            <p class="mt-2 text-slate-600">Выбери 1–2 пункта — это сильно влияет на подбор</p>
                        </div>

                        <div class="youth-card p-6 sm:p-8 space-y-3">
                            <template x-for="opt in priorityOptions" :key="opt.value">
                                <button type="button"
                                        @click="togglePriority(opt.value)"
                                        :class="isPrioritySelected(opt.value) ? 'quiz-option-selected' : 'quiz-option-default'">
                                    <span class="text-2xl" x-text="opt.emoji"></span>
                                    <div class="text-left">
                                        <span class="font-semibold text-slate-800 block" x-text="opt.label"></span>
                                        <span class="text-xs text-slate-500" x-text="opt.desc"></span>
                                    </div>
                                </button>
                            </template>

                            <p class="text-xs text-center text-slate-400 pt-2" x-show="priorities.length === 0">
                                Выбери хотя бы один приоритет
                            </p>

                            <button type="button" @click="startQuestions()" :disabled="!prioritiesComplete"
                                    class="btn-glow w-full justify-center mt-4">
                                Погнали — к вопросам
                            </button>
                        </div>
                    </div>

                    {{-- Вопросы --}}
                    <div x-show="phase === 'questions'" x-cloak>
                        <div class="youth-card p-6 sm:p-8 animate-slide-up" x-show="currentQuestion">
                            <div class="flex items-start gap-3 mb-4">
                                <span class="text-3xl" x-text="currentQuestion?.emoji || '💭'"></span>
                                <div>
                                    <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 leading-snug"
                                        x-text="currentQuestion?.question"></h2>
                                    <p class="text-xs text-slate-400 mt-1" x-show="currentQuestion?.hint" x-text="currentQuestion?.hint"></p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <template x-for="option in currentQuestion?.options ?? []" :key="option.id">
                                    <button type="button"
                                            @click="selectAnswer(option.id)"
                                            :class="isSelected(option.id) ? 'quiz-option-selected' : 'quiz-option-default'">
                                        <span class="flex-shrink-0 w-7 h-7 rounded-full border-2 flex items-center justify-center transition-all"
                                              :class="isSelected(option.id) ? 'border-brand-600 bg-brand-600' : 'border-slate-300 bg-white'">
                                            <svg x-show="isSelected(option.id)" class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                        <span class="text-sm sm:text-base text-slate-800 font-medium text-left" x-text="option.text"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-6 gap-4">
                            <button type="button" @click="prev()" class="btn-ghost">
                                Назад
                            </button>

                            <button type="button" @click="next()" x-show="currentStep < visibleQuestions.length - 1"
                                    :disabled="!currentAnswered" class="btn-glow">
                                Далее
                            </button>

                            <button type="submit" x-show="currentStep === visibleQuestions.length - 1"
                                    :disabled="!allAnswered" class="btn-glow">
                                ✨ Узнать мой результат
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>[x-cloak] { display: none !important; }</style>
@endsection
