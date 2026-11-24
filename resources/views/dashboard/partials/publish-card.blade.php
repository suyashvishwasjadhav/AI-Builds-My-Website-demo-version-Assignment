<section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
    <div class="flex items-center justify-between gap-2">
        <h3 class="text-base font-semibold text-slate-900">Publish</h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $site->status->value === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
            {{ $site->status->value }}
        </span>
    </div>

    @php
        $publicUrl = route('public.site', $site->slug);
    @endphp

    <div class="text-xs text-slate-600 flex flex-wrap items-center gap-2">
        <span>Public URL:</span>
        <code class="bg-slate-100 px-2 py-0.5 rounded text-slate-800">{{ $publicUrl }}</code>
    </div>

    @if ($site->status->value === 'published')
        <div class="flex flex-wrap items-center gap-2" x-data="{ copied: false }">
            <a
                href="{{ $publicUrl }}"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition"
            >
                View live site
            </a>

            <button
                type="button"
                @click="navigator.clipboard?.writeText(@js($publicUrl)); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition"
            >
                <span x-show="!copied">Copy public link</span>
                <span x-show="copied" style="display: none;">Copied!</span>
            </button>

            <form method="POST" action="{{ route('sites.unpublish', $site) }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-medium text-slate-600 hover:text-red-700 border border-slate-200 rounded-md">
                    Unpublish
                </button>
            </form>
        </div>
    @else
        @if (! empty($publishBlockReason))
            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                {{ $publishBlockReason }}
            </p>
            <button
                type="button"
                disabled
                class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-slate-200 text-slate-500 cursor-not-allowed"
            >
                Publish website
            </button>
        @else
            <form
                method="POST"
                action="{{ route('sites.publish', $site) }}"
                onsubmit="return confirm('Publish this website to the public URL {{ $publicUrl }}?')"
            >
                @csrf
                <button
                    type="submit"
                    class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition"
                >
                    Publish website
                </button>
            </form>
        @endif
    @endif

    <form method="POST" action="{{ route('sites.slug.update', $site) }}" class="pt-3 border-t border-slate-100 space-y-2">
        @csrf
        @method('PATCH')
        <label for="slug" class="block text-xs font-medium text-slate-700">Rename public URL slug</label>
        <div class="flex flex-wrap items-center gap-2">
            <input
                id="slug"
                name="slug"
                type="text"
                value="{{ old('slug', $site->slug) }}"
                pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                required
                class="flex-1 min-w-[180px] rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium bg-slate-800 text-white hover:bg-slate-900">
                Update slug
            </button>
        </div>
        <x-input-error class="mt-1" :messages="$errors->get('slug')" />
    </form>
</section>
