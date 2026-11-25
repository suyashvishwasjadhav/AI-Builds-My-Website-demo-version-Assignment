@if (! empty($availableLocales) && count($availableLocales) > 1)
    <div class="fixed bottom-5 left-5 z-40 inline-flex items-center gap-1 rounded-lg bg-white/95 backdrop-blur border border-slate-200 px-2 py-1 shadow-md text-xs">
        @foreach ($availableLocales as $code => $label)
            <a
                href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}"
                class="rounded-lg px-2.5 py-1 font-medium transition {{ ($activeLocale ?? 'en') === $code ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>
@endif
