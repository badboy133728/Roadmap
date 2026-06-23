@props(['profession'])

<article {{ $attributes->merge(['class' => 'card-hover group p-5 sm:p-6 flex flex-col h-full']) }}>
    <div class="flex items-start justify-between gap-3 mb-3">
        @if ($profession->category)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-brand-50 text-brand-700">
                @if ($profession->category->icon)
                    <span>{{ $profession->category->icon }}</span>
                @endif
                {{ $profession->category->name }}
            </span>
        @endif
    </div>

    <h3 class="text-lg font-bold text-slate-900 group-hover:text-brand-700 transition leading-snug mb-2">
        <a href="{{ route('professions.show', $profession) }}" class="focus:outline-none focus:ring-2 focus:ring-brand-500 rounded">
            {{ $profession->name }}
        </a>
    </h3>

    @if ($profession->description)
        <p class="text-sm text-slate-600 line-clamp-2 flex-grow mb-4 leading-relaxed">
            {{ Str::limit($profession->description, 120) }}
        </p>
    @endif

    @if (! empty($profession->skills))
        <div class="flex flex-wrap gap-1.5 mb-4">
            @foreach (array_slice($profession->skills, 0, 3) as $skill)
                <span class="inline-flex px-2 py-0.5 rounded-md text-xs bg-slate-100 text-slate-600 font-medium">{{ $skill }}</span>
            @endforeach
            @if (count($profession->skills) > 3)
                <span class="inline-flex px-2 py-0.5 rounded-md text-xs bg-slate-100 text-slate-400">+{{ count($profession->skills) - 3 }}</span>
            @endif
        </div>
    @endif

    <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between">
        <span class="text-sm font-semibold text-brand-600 group-hover:text-brand-700 transition">Подробнее</span>
        <svg class="w-5 h-5 text-brand-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </div>
</article>
