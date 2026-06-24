<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Курс на развитие') — {{ config('app.name', 'Курс на развитие') }}</title>
    <meta name="description" content="@yield('meta_description', 'Курс на развитие — карьерный навигатор: выбери профессию, узнай зарплату в своём городе, пройди тест и построй путь к цели.')">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" rel="stylesheet"></noscript>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen flex flex-col">
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/80">
        <div class="page-container">
            <div class="flex items-center justify-between gap-4 h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group shrink-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 via-fuchsia-500 to-cyan-500 flex items-center justify-center shadow-sm shadow-brand-500/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="hidden sm:block">
                        <span class="block text-base font-bold text-slate-900 group-hover:text-brand-700 transition">Курс на развитие</span>
                        <span class="block text-[11px] text-slate-500 leading-tight">от школы до карьеры</span>
                    </div>
                </a>

                <nav class="hidden lg:flex items-center gap-1">
                    @foreach ([
                        ['route' => 'home', 'label' => 'Главная', 'match' => 'home'],
                        ['route' => 'professions.index', 'label' => 'Профессии', 'match' => 'professions.*'],
                        ['route' => 'quiz.show', 'label' => '✨ Мой тест', 'match' => 'quiz.*'],
                        ['route' => 'career-change.show', 'label' => 'Смена профессии', 'match' => 'career-change.*'],
                    ] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="px-3.5 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs($item['match']) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:text-brand-700 hover:bg-slate-50' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="flex items-center rounded-xl border border-slate-200 bg-slate-50 p-1">
                        @foreach (['volgograd' => 'Волгоград', 'astrakhan' => 'Астрахань'] as $slug => $label)
                            <form method="POST" action="{{ route('city.switch', $slug) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="px-2.5 sm:px-3 py-1.5 text-[11px] sm:text-xs font-semibold rounded-lg transition {{ $currentCity->slug === $slug ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                                    {{ $label }}
                                </button>
                            </form>
                        @endforeach
                    </div>

                    @auth
                        <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex btn-primary !px-4 !py-2 !text-sm">
                            Кабинет
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium text-slate-600 hover:text-brand-700 transition px-2">
                            Войти
                        </a>
                        <a href="{{ route('register') }}" class="btn-primary !px-4 !py-2 !text-sm">
                            Регистрация
                        </a>
                    @endauth
                </div>
            </div>

            <nav class="lg:hidden flex gap-1.5 pb-3 overflow-x-auto scrollbar-hide -mt-1">
                @foreach ([
                    ['route' => 'home', 'label' => 'Главная', 'match' => 'home'],
                    ['route' => 'professions.index', 'label' => 'Профессии', 'match' => 'professions.*'],
                    ['route' => 'quiz.show', 'label' => 'Тест', 'match' => 'quiz.*'],
                    ['route' => 'career-change.show', 'label' => 'Смена', 'match' => 'career-change.*'],
                ] as $item)
                    <a href="{{ route($item['route']) }}"
                       class="shrink-0 px-3.5 py-1.5 text-xs font-semibold rounded-full transition {{ request()->routeIs($item['match']) ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    @if (session('success'))
        <div class="page-container mt-4">
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="flex-grow">
        @yield('content')
    </main>

    <footer class="mt-auto border-t border-slate-200 bg-white">
        <div class="page-container py-10">
            <div class="grid sm:grid-cols-3 gap-8 mb-8">
                <div class="sm:col-span-1">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <span class="font-bold text-slate-900">Курс на развитие</span>
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed">Помогаем определиться с профессией, узнать зарплаты и построить карьерный путь.</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Разделы</p>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('quiz.show') }}" class="text-slate-600 hover:text-brand-600 transition">Профориентационный тест</a></li>
                        <li><a href="{{ route('professions.index') }}" class="text-slate-600 hover:text-brand-600 transition">Каталог профессий</a></li>
                        <li><a href="{{ route('career-change.show') }}" class="text-slate-600 hover:text-brand-600 transition">Смена профессии</a></li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Город</p>
                    <p class="text-sm text-slate-600">{{ $currentCity->name }}, {{ $currentCity->region }}</p>
                    <p class="text-xs text-slate-400 mt-3">Рекомендации носят ознакомительный характер</p>
                </div>
            </div>
            <div class="pt-6 border-t border-slate-100 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} Курс на развитие
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
