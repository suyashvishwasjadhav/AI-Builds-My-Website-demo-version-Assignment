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
            class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-auto"
        >
            <div class="h-16 px-5 flex items-center justify-between border-b border-slate-200">
                <a href="{{ route('sites.index') }}" class="text-sm font-semibold text-slate-800 hover:text-indigo-600 flex items-center gap-2">
                    <span aria-hidden="true">&larr;</span>
                    <span>My sites</span>
                </a>
                <button
                    type="button"
                    @click="sidebarOpen = false"
                    class="lg:hidden p-1.5 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100"
                    aria-label="Close sidebar"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Site navigation">
                @php
                    $navItems = [
                        ['key' => 'overview', 'label' => 'Overview', 'route' => route('sites.show', $site)],
                        ['key' => 'sources', 'label' => 'Sources', 'route' => route('sites.tab', [$site, 'sources'])],
                        ['key' => 'facts', 'label' => 'Facts', 'route' => route('sites.tab', [$site, 'facts'])],
                        ['key' => 'sections', 'label' => 'Sections', 'route' => route('sites.tab', [$site, 'sections'])],
                        ['key' => 'media', 'label' => 'Media', 'route' => route('sites.tab', [$site, 'media'])],
                        ['key' => 'enquiries', 'label' => 'Enquiries', 'route' => route('sites.tab', [$site, 'enquiries'])],
                        ['key' => 'settings', 'label' => 'Settings', 'route' => route('sites.tab', [$site, 'settings'])],
                    ];
                @endphp

                @foreach ($navItems as $item)
                    @php
                        $isActive = ($activeTab ?? 'overview') === $item['key'];
                    @endphp
                    <a
                        href="{{ $item['route'] }}"
                        class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ $isActive ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="p-4 border-t border-slate-200 text-xs text-slate-500">
                <div class="font-medium text-slate-700 truncate">{{ auth()->user()->name }}</div>
                <div class="truncate">{{ auth()->user()->email }}</div>
            </div>
        </aside>

        <!-- Main content area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 -ml-2 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                        aria-label="Open sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <h1 class="text-lg font-semibold text-slate-900 truncate">
                        {{ $site->business_name }}
                    </h1>

                    @php
                        $badgeClasses = match ($site->status->value) {
                            'published' => 'bg-emerald-100 text-emerald-800',
                            'ready' => 'bg-amber-100 text-amber-800',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $badgeClasses }}">
                        {{ $site->status->value }}
                    </span>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('profile.edit') }}" class="text-sm text-slate-600 hover:text-slate-900 hidden sm:inline">
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-slate-600 hover:text-slate-900">
                            Log out
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
