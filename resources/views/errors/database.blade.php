@extends('layouts.public')

@section('title', 'База данных недоступна')

@section('content')
    <div class="page-container py-20">
        <div class="max-w-lg mx-auto text-center youth-card p-8 sm:p-10">
            <span class="text-5xl">🔌</span>
            <h1 class="mt-4 text-2xl font-extrabold text-slate-900">База данных не подключена</h1>
            <p class="mt-3 text-slate-600 leading-relaxed">
                Главная страница открывается, но профессии и тест требуют MySQL.
                На Railway нужно связать сервис <strong>web</strong> с <strong>MySQL</strong>.
            </p>
            <div class="mt-6 text-left text-sm bg-slate-50 rounded-xl p-4 space-y-2 text-slate-700">
                <p><strong>1.</strong> Сервис <code class="text-xs bg-white px-1 rounded">web</code> → Variables</p>
                <p><strong>2.</strong> Add Reference → MySQL → <code class="text-xs bg-white px-1 rounded">MYSQL_URL</code></p>
                <p><strong>3.</strong> Имя переменной: <code class="text-xs bg-white px-1 rounded">DATABASE_URL</code></p>
                <p><strong>4.</strong> Redeploy и проверь <a href="/health?db=1" class="text-brand-600 font-semibold">/health?db=1</a></p>
            </div>
            <a href="{{ route('home') }}" class="btn-secondary mt-6 inline-flex">На главную</a>
        </div>
    </div>
@endsection
