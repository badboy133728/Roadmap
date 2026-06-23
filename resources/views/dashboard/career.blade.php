<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Личный кабинет
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Профиль --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Профиль</h3>
                    <p class="text-sm text-gray-600 mb-6">Укажите город и текущую профессию для персонализации рекомендаций</p>

                    <form method="POST" action="{{ route('dashboard.profile.update') }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="name" value="Имя" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                          :value="old('name', $user->name)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                          :value="old('email', $user->email)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label for="city_id" value="Город" />
                            <select id="city_id" name="city_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Не выбран</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}" @selected(old('city_id', $user->city_id) == $city->id)>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('city_id')" />
                        </div>

                        <div>
                            <x-input-label for="current_profession_id" value="Текущая профессия" />
                            <select id="current_profession_id" name="current_profession_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Не указана</option>
                                @foreach ($professions as $profession)
                                    <option value="{{ $profession->id }}" @selected(old('current_profession_id', $user->current_profession_id) == $profession->id)>
                                        {{ $profession->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('current_profession_id')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Сохранить</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Избранные профессии --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Избранные профессии</h3>
                    <p class="text-sm text-gray-600 mb-4">Профессии, которые вы сохранили</p>

                    @if ($favorites->count())
                        <ul class="divide-y divide-gray-100">
                            @foreach ($favorites as $profession)
                                <li class="py-3 flex items-center justify-between gap-4">
                                    <div>
                                        <a href="{{ route('professions.show', $profession) }}"
                                           class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ $profession->name }}
                                        </a>
                                        @if ($profession->category)
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $profession->category->name }}</p>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('dashboard.favorites.destroy', $profession) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                            Удалить
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500 italic">
                            Пока нет избранных.
                            <a href="{{ route('professions.index') }}" class="text-indigo-600 hover:underline">Перейти в каталог</a>
                        </p>
                    @endif
                </div>
            </div>

            {{-- История тестов --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-1">История тестов</h3>
                    <p class="text-sm text-gray-600 mb-4">Результаты профориентационного теста</p>

                    @if ($quizHistory->count())
                        <ul class="divide-y divide-gray-100">
                            @foreach ($quizHistory as $result)
                                <li class="py-3 flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $result->created_at->format('d.m.Y H:i') }}
                                        </p>
                                        @php
                                            $recs = $result->recommendations;
                                            $recCount = is_array($recs) && isset($recs['list']) ? count($recs['list']) : (is_array($recs) ? count($recs) : 0);
                                        @endphp
                                        @if ($recCount > 0)
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                {{ $recCount }} рекомендаций
                                            </p>
                                        @endif
                                    </div>
                                    @if ($result->session_id)
                                        <a href="{{ route('quiz.result', $result->session_id) }}"
                                           class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                            Смотреть результат
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500 italic">
                            Вы ещё не проходили тест.
                            <a href="{{ route('quiz.show') }}" class="text-indigo-600 hover:underline">Пройти тест</a>
                        </p>
                    @endif
                </div>
            </div>

            {{-- Сохранённые пути --}}
            @if (isset($savedPaths) && $savedPaths->count())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-1">Сохранённые пути</h3>
                        <ul class="divide-y divide-gray-100 mt-4">
                            @foreach ($savedPaths as $path)
                                <li class="py-3">
                                    <a href="{{ route('professions.show', $path->profession) }}"
                                       class="font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ $path->profession->name }}
                                    </a>
                                    @if ($path->notes)
                                        <p class="text-sm text-gray-500 mt-1">{{ $path->notes }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
