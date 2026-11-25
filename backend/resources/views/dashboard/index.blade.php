<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-slate-900 leading-tight">
                    {{ __('My sites') }}
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">Manage your verified business websites, AI copy, and incoming leads.</p>
            </div>
            <a
                href="{{ route('sites.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-sm"
            >
                + {{ __('Create new site') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($sites->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200 p-12 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">No sites yet</h3>
                    <p class="mt-2 text-sm text-slate-600 max-w-md mx-auto">
                        Share your business name, city, links, PDFs, or price lists to generate a verified, ready-to-publish website.
                    </p>
                    <div class="mt-6">
                        <a
                            href="{{ route('sites.create') }}"
                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 rounded-lg font-semibold text-xs text-white hover:bg-indigo-700 transition"
                        >
                            Create your first site
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Total Websites</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ $sites->count() }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Published Live</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $sites->where('status.value', 'published')->count() }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Drafts / In Progress</p>
                        <p class="mt-1 text-2xl font-bold text-slate-700">{{ $sites->where('status.value', '!=', 'published')->count() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($sites as $site)
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow transition flex flex-col justify-between p-6">
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="h-9 w-9 rounded-lg bg-indigo-600 text-white text-sm font-bold flex items-center justify-center shrink-0">
                                            {{ mb_strtoupper(mb_substr(trim($site->business_name) ?: 'S', 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-base font-semibold text-slate-900 truncate">{{ $site->business_name }}</h3>
                                            <p class="text-xs text-slate-500 truncate">{{ $site->city }}</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium capitalize shrink-0 {{ $site->status->value === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        {{ $site->status->value }}
                                    </span>
                                </div>
                                <p class="mt-4 text-xs font-mono text-slate-400 truncate">/s/{{ $site->slug }}</p>
                            </div>

                            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                                <a href="{{ route('sites.show', $site) }}" class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-semibold transition">
                                    Manage
                                </a>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('sites.studio', $site) }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-medium transition">
                                        Studio
                                    </a>
                                    @if ($site->status->value === 'published')
                                        <a href="{{ route('public.site', $site->slug) }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-medium transition">
                                            Live &nearr;
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
