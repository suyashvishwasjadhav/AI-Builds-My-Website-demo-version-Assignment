<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $site->business_name }} — {{ config('app.name', 'AI Builds My Website') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <!-- Mobile sidebar backdrop -->
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
            style="display: none;"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200/90 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-auto"
        >
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-200/90">
                <a href="{{ route('sites.index') }}" class="text-sm font-semibold text-slate-800 hover:text-indigo-600 flex items-center gap-2 transition">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 text-xs font-bold" aria-hidden="true">&larr;</span>
                    <span>All Websites</span>
                </a>
                <button
                    type="button"
                    @click="sidebarOpen = false"
                    class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100"
                    aria-label="Close sidebar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-2.5">
                    <span class="h-8 w-8 rounded-lg bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                        {{ mb_strtoupper(mb_substr(trim($site->business_name) ?: 'S', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $site->business_name }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ $site->city }}</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Site navigation">
                @php
                    $navItems = [
                        ['key' => 'overview', 'label' => 'Overview', 'route' => route('sites.show', $site)],
                        ['key' => 'sources', 'label' => 'Sources', 'route' => route('sites.sources', $site)],
                        ['key' => 'facts', 'label' => 'Verified Facts', 'route' => route('sites.facts', $site)],
                        ['key' => 'sections', 'label' => 'Sections & Copy', 'route' => route('sites.sections', $site)],
                        ['key' => 'media', 'label' => 'Media & Header BG', 'route' => route('sites.media', $site)],
                        ['key' => 'enquiries', 'label' => 'Leads & Enquiries', 'route' => route('sites.enquiries', $site)],
                        ['key' => 'settings', 'label' => 'Settings', 'route' => route('sites.tab', [$site, 'settings'])],
                    ];
                @endphp

                @foreach ($navItems as $item)
                    @php
                        $isActive = ($activeTab ?? 'overview') === $item['key'];
                    @endphp
                    <a
                        href="{{ $item['route'] }}"
                        class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ $isActive ? 'bg-indigo-50 text-indigo-700 border border-indigo-200/60' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        <span>{{ $item['label'] }}</span>
                        @if ($isActive)
                            <span class="h-1.5 w-1.5 rounded-sm bg-indigo-600"></span>
                        @endif
                    </a>
                @endforeach

                <div class="pt-4 mt-4 border-t border-slate-100 space-y-1">
                    <a
                        href="{{ route('sites.studio', $site) }}"
                        class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg text-slate-700 hover:bg-slate-100 transition"
                    >
                        <span>Split-Screen Studio</span>
                        <span class="text-xs text-indigo-600">&rarr;</span>
                    </a>
                    @if ($site->status->value === 'published')
                        <a
                            href="{{ route('public.site', $site->slug) }}"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg text-emerald-700 hover:bg-emerald-50 transition"
                        >
                            <span>View Live Website</span>
                            <span class="text-xs">&nearr;</span>
                        </a>
                    @endif
                </div>
            </nav>

            <div class="p-4 border-t border-slate-200/90 space-y-2.5 text-xs">
                <a href="{{ route('profile.edit') }}" class="flex items-center justify-between rounded-lg px-2.5 py-2 hover:bg-slate-100 transition group">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-800 group-hover:text-indigo-600 truncate">{{ auth()->user()->name }}</div>
                        <div class="truncate text-slate-400">{{ auth()->user()->email }}</div>
                    </div>
                    <span class="text-slate-400 group-hover:text-indigo-600 shrink-0">&rarr;</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-2.5 py-1.5 rounded-lg text-slate-600 hover:bg-rose-50 hover:text-rose-700 font-medium transition cursor-pointer">
                        Log out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main content area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header class="sticky top-0 z-20 h-16 bg-white/95 backdrop-blur border-b border-slate-200/90 px-4 sm:px-8 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 -ml-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                        aria-label="Open sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="flex items-center gap-2 text-sm min-w-0">
                        <span class="font-semibold text-slate-900 truncate">{{ $site->business_name }}</span>
                        <span class="text-slate-300 hidden sm:inline">/</span>
                        <span class="text-slate-500 hidden sm:inline capitalize">{{ $activeTab ?? 'Overview' }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium capitalize {{ $site->status->value === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                        {{ $site->status->value }}
                    </span>
                    <a
                        href="{{ route('sites.studio', $site) }}"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 transition"
                    >
                        Studio
                    </a>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-8 max-w-6xl w-full mx-auto">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
