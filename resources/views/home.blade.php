@extends('layouts.public')

@section('title', 'Главная')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden min-h-[85vh] flex items-center mesh-bg">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-20 right-10 w-72 h-72 bg-fuchsia-400/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 left-10 w-96 h-96 bg-cyan-400/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-brand-400/10 rounded-full blur-3xl"></div>
        </div>

        <div class="page-container relative py-16 sm:py-20 w-full">
            <div class="max-w-3xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/80 border border-brand-200 text-sm font-bold text-brand-700 mb-8 shadow-sm backdrop-blur">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Актуально для {{ $currentCity->name }}
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-[1.1] tracking-tight">
                    Кем <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 via-fuchsia-500 to-cyan-500">ты</span> будешь?
                </h1>
                <p class="mt-6 text-lg sm:text-xl text-slate-600 leading-relaxed max-w-xl mx-auto">
                    Личный тест, 150+ профессий, зарплаты в твоём городе и конкретные вузы — всё в одном месте
                </p>

                <div class="mt-10 flex flex-wrap justify-center gap-4">
                    <a href="{{ route('quiz.show') }}" class="btn-glow text-base !px-8 !py-4">
                        ✨ Пройти личный тест
                    </a>
                    <a href="{{ route('professions.index') }}" class="btn-secondary text-base !px-8 !py-4">
                        Смотреть профессии
                    </a>
                </div>

                <p class="mt-4 text-sm text-slate-400">Бесплатно · ~3 минуты · результат сразу</p>
            </div>

            {{-- Floating cards preview --}}
            <div class="mt-16 grid grid-cols-3 gap-3 sm:gap-5 max-w-lg mx-auto">
                @foreach ([['🚀', 'IT', 'от 85к'], ['💚', 'Медицина', 'от 50к'], ['🎨', 'Творчество', 'от 45к']] as [$emoji, $label, $salary])
                    <div class="youth-card p-4 text-center hover:-translate-y-1 transition-transform">
                        <span class="text-2xl sm:text-3xl">{{ $emoji }}</span>
                        <p class="text-xs sm:text-sm font-bold text-slate-800 mt-2">{{ $label }}</p>
                        <p class="text-[10px] sm:text-xs text-slate-400 mt-0.5">{{ $salary }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Как это работает --}}
    <section class="page-container py-16 sm:py-20">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 text-center mb-2">Как это работает</h2>
        <p class="text-slate-500 text-center mb-10">Три шага — и ты знаешь, куда двигаться</p>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([
                ['num' => '01', 'emoji' => '👋', 'title' => 'Расскажи о себе', 'desc' => 'Имя, класс или статус — тест подстроится под тебя', 'route' => 'quiz.show', 'color' => 'from-violet-500 to-brand-600'],
                ['num' => '02', 'emoji' => '🧠', 'title' => 'Ответь на вопросы', 'desc' => 'Узнаем твой тип личности и интересы — без скучных формулировок', 'route' => 'quiz.show', 'color' => 'from-fuchsia-500 to-pink-500'],
                ['num' => '03', 'emoji' => '🎯', 'title' => 'Получи свой план', 'desc' => 'Топ-профессии, зарплаты, вузы в твоём городе и дорожная карта', 'route' => 'professions.index', 'color' => 'from-cyan-500 to-brand-500'],
            ] as $step)
                <a href="{{ route($step['route']) }}" class="youth-card p-6 sm:p-7 group hover:-translate-y-1 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl">{{ $step['emoji'] }}</span>
                        <span class="text-4xl font-extrabold text-slate-100 group-hover:text-brand-100 transition">{{ $step['num'] }}</span>
                    </div>
                    <h3 class="text-lg font-extrabold text-slate-900 group-hover:text-brand-700 transition">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $step['desc'] }}</p>
                    <div class="mt-4 h-1 rounded-full bg-gradient-to-r {{ $step['color'] }} opacity-60 group-hover:opacity-100 transition"></div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- CTA блок --}}
    <section class="page-container pb-16 sm:pb-20">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-brand-900 to-fuchsia-900 p-8 sm:p-12 text-center text-white">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute top-0 right-0 w-64 h-64 bg-fuchsia-400 rounded-full blur-3xl"></div>
            </div>
            <div class="relative">
                <p class="text-5xl mb-4">🤔</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold">Всё ещё не знаешь, кем хочешь быть?</h2>
                <p class="mt-3 text-white/70 max-w-md mx-auto">Это нормально. Наш тест не даст шаблонный ответ — он покажет твой тип и профессии с процентом совпадения.</p>
                <a href="{{ route('quiz.show') }}" class="btn-glow mt-8 inline-flex !text-base">
                    Начать прямо сейчас
                </a>
            </div>
        </div>
    </section>
@endsection
