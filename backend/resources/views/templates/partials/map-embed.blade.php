@if (! empty($mapEmbedUrl))
    <div class="mt-6 space-y-3">
        <div class="overflow-hidden rounded-xl border border-slate-200/80 shadow-sm">
            <iframe
                src="{{ $mapEmbedUrl }}"
                title="Location map for {{ $site->business_name }}"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                class="h-64 w-full border-0"
                allowfullscreen
            ></iframe>
        </div>
        @if (! empty($mapDirectionsUrl))
            <div>
                <a
                    href="{{ $mapDirectionsUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 transition"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-6-5.686-6-10a6 6 0 1 1 12 0c0 4.314-6 10-6 10Z" />
                        <circle cx="12" cy="11" r="2.5" />
                    </svg>
                    <span>Get Directions</span>
                </a>
            </div>
        @endif
    </div>
@endif
