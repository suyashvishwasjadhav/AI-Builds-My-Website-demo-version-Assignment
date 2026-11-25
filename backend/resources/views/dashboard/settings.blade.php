@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'settings'])

@section('content')
    <div class="mb-6">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Site Settings</h2>
        <p class="mt-1 text-sm text-slate-500">
            Configure business identity, timezone for live business hours, SEO endpoints, and domain settings.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
        <!-- General & Timezone Settings -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full flex flex-col justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-900">General &amp; Timezone</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Timezone controls when your live "Open Now / Closed" badge switches.
                </p>

                <form id="general-settings-form" method="POST" action="{{ route('sites.settings.update', $site) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="settings_business_name" class="block text-xs font-medium text-slate-700">Business Name</label>
                        <input
                            id="settings_business_name"
                            name="business_name"
                            type="text"
                            required
                            value="{{ old('business_name', $site->business_name) }}"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="settings_city" class="block text-xs font-medium text-slate-700">City</label>
                        <input
                            id="settings_city"
                            name="city"
                            type="text"
                            required
                            value="{{ old('city', $site->city) }}"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="settings_timezone" class="block text-xs font-medium text-slate-700">Business Timezone</label>
                        <select
                            id="settings_timezone"
                            name="timezone"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach (['Asia/Kolkata' => 'Asia/Kolkata (IST)', 'Asia/Dubai' => 'Asia/Dubai (GST)', 'Asia/Singapore' => 'Asia/Singapore (SGT)', 'Europe/London' => 'Europe/London (GMT/BST)', 'America/New_York' => 'America/New_York (EST/EDT)', 'UTC' => 'UTC'] as $tz => $label)
                                <option value="{{ $tz }}" @selected(($site->timezone ?? 'Asia/Kolkata') === $tz)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-100">
                <button
                    type="submit"
                    form="general-settings-form"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition"
                >
                    Save Settings
                </button>
            </div>
        </section>

        <!-- SEO & Export Tools -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full flex flex-col justify-between">
            <div class="space-y-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">SEO &amp; Technical Endpoints</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Automatically generated when your website is published.
                    </p>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 flex items-center justify-between gap-2">
                        <span class="font-medium text-slate-700">XML Sitemap</span>
                        <a href="{{ route('public.site.sitemap', $site->slug) }}" target="_blank" rel="noopener" class="font-mono text-indigo-600 hover:underline truncate">
                            /s/{{ $site->slug }}/sitemap.xml
                        </a>
                    </div>
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 flex items-center justify-between gap-2">
                        <span class="font-medium text-slate-700">Robots.txt</span>
                        <a href="{{ route('public.site.robots', $site->slug) }}" target="_blank" rel="noopener" class="font-mono text-indigo-600 hover:underline truncate">
                            /s/{{ $site->slug }}/robots.txt
                        </a>
                    </div>
                    <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 flex items-center justify-between gap-2">
                        <span class="font-medium text-slate-700">OpenGraph Social Card</span>
                        <a href="{{ route('public.site.og', $site->slug) }}" target="_blank" rel="noopener" class="font-mono text-indigo-600 hover:underline truncate">
                            /s/{{ $site->slug }}/og-image.svg
                        </a>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-100 flex flex-wrap items-center gap-2">
                <a href="{{ route('sites.export.zip', $site) }}" class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                    Download Static ZIP
                </a>
                <a href="{{ route('sites.enquiries.export', $site) }}" class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                    Export Leads (CSV)
                </a>
            </div>
        </section>

        <!-- Danger Zone -->
        <section class="bg-white rounded-xl border border-rose-200 shadow-sm p-6 lg:col-span-2 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-rose-700">Delete Website</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Permanently deletes this site, its sources, verified facts, sections, and enquiries. This cannot be undone.
                </p>
            </div>

            <form method="POST" action="{{ route('sites.destroy', $site) }}" onsubmit="return confirm('Permanently delete {{ addslashes($site->business_name) }}?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg text-xs font-semibold bg-rose-600 text-white hover:bg-rose-700 transition">
                    Delete Website
                </button>
            </form>
        </section>
    </div>
@endsection
