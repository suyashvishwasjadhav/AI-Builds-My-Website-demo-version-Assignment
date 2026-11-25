@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'overview'])

@section('content')
    <!-- Page Header + Primary Actions -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Workspace Overview</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $site->business_name }} &middot; {{ $site->city }} &middot; Theme: <span class="capitalize font-medium text-slate-700">{{ $site->template }}</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a
                href="{{ route('sites.studio', $site) }}"
                class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 transition shadow-sm"
            >
                Split-Screen Studio
            </a>
            <a
                href="{{ route('sites.preview', $site) }}"
                class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition"
            >
                Preview &amp; Theme
            </a>
            <a
                href="{{ route('sites.export.zip', $site) }}"
                class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm"
            >
                Download Static ZIP
            </a>
        </div>
    </div>

    @if (session('status') || request()->boolean('generated'))
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') ?: 'Website generated with AI! Review your sections or preview your live site below.' }}
        </div>
    @endif

    <!-- Interactive Tutorial / Step Indicator -->
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-indigo-900 to-slate-900 p-5 text-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-indigo-800/80 pb-3.5">
            <div class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-500/30 text-xs font-bold text-indigo-300">★</span>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-200">Website Creation Guide</span>
            </div>
            @if ($site->status->value === 'published')
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-300">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Live on Web
                </span>
            @else
                <span class="text-xs text-slate-300">3 simple steps to live site</span>
            @endif
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <!-- Step 1 -->
            <div class="rounded-xl p-3 border {{ $openQuestions->isNotEmpty() ? 'border-amber-400 bg-amber-500/20 ring-2 ring-amber-400/50' : 'border-indigo-800/60 bg-indigo-950/40 text-slate-300' }}">
                <div class="flex items-center gap-2 font-bold {{ $openQuestions->isNotEmpty() ? 'text-amber-200' : 'text-slate-200' }}">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] {{ $openQuestions->isNotEmpty() ? 'bg-amber-400 text-slate-950 animate-bounce' : 'bg-emerald-500 text-white' }}">
                        {{ $openQuestions->isNotEmpty() ? '1' : '✓' }}
                    </span>
                    <span>1. Confirm Details</span>
                </div>
                <p class="mt-1 text-[11px] leading-relaxed {{ $openQuestions->isNotEmpty() ? 'text-amber-100' : 'text-slate-400' }}">
                    {{ $openQuestions->isNotEmpty() ? 'Answer or skip open questions in Attention Card →' : 'All facts verified cleanly!' }}
                </p>
            </div>

            <!-- Step 2 -->
            <div class="rounded-xl p-3 border {{ $openQuestions->isEmpty() && ($site->sections_count ?? $site->sections()->count()) === 0 ? 'border-indigo-400 bg-indigo-500/20 ring-2 ring-indigo-400/50' : 'border-indigo-800/60 bg-indigo-950/40 text-slate-300' }}">
                <div class="flex items-center gap-2 font-bold {{ $openQuestions->isEmpty() && ($site->sections_count ?? $site->sections()->count()) === 0 ? 'text-indigo-200' : 'text-slate-200' }}">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] {{ ($site->sections_count ?? $site->sections()->count()) > 0 ? 'bg-emerald-500 text-white' : ($openQuestions->isEmpty() ? 'bg-indigo-400 text-slate-950 animate-pulse' : 'bg-slate-700 text-slate-300') }}">
                        {{ ($site->sections_count ?? $site->sections()->count()) > 0 ? '✓' : '2' }}
                    </span>
                    <span>2. Generate with AI</span>
                </div>
                <p class="mt-1 text-[11px] leading-relaxed {{ $openQuestions->isEmpty() && ($site->sections_count ?? $site->sections()->count()) === 0 ? 'text-indigo-100' : 'text-slate-400' }}">
                    Click "Generate website" below to build all sections.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="rounded-xl p-3 border {{ $site->status->value === 'published' ? 'border-emerald-400 bg-emerald-500/20' : 'border-indigo-800/60 bg-indigo-950/40 text-slate-300' }}">
                <div class="flex items-center gap-2 font-bold {{ $site->status->value === 'published' ? 'text-emerald-200' : 'text-slate-200' }}">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] {{ $site->status->value === 'published' ? 'bg-emerald-400 text-slate-950' : 'bg-slate-700 text-slate-300' }}">
                        {{ $site->status->value === 'published' ? '✓' : '3' }}
                    </span>
                    <span>3. Auto-Published</span>
                </div>
                <p class="mt-1 text-[11px] leading-relaxed text-slate-300">
                    @if ($site->status->value === 'published')
                        <a href="{{ route('public.site', $site->slug) }}" target="_blank" class="underline font-semibold text-emerald-300 hover:text-emerald-200">
                            Open live website &nearr;
                        </a>
                    @else
                        Website automatically publishes when generation completes.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- KPI Telemetry Strip -->
    <div class="mb-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('sites.sources', $site) }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:border-indigo-300 transition">
            <p class="text-xs font-medium text-slate-500">Sources Added</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $site->sources_count ?? $site->sources()->count() }}</p>
        </a>
        <a href="{{ route('sites.facts', $site) }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:border-indigo-300 transition">
            <p class="text-xs font-medium text-slate-500">Confirmed Facts</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $site->facts()->whereIn('status', ['confirmed', 'user_provided'])->count() }}</p>
        </a>
        <a href="{{ route('sites.sections', $site) }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:border-indigo-300 transition">
            <p class="text-xs font-medium text-slate-500">Sections Built</p>
            <p class="mt-1 text-2xl font-bold text-indigo-600">{{ $site->sections_count ?? $site->sections()->count() }}</p>
        </a>
        <a href="{{ route('sites.enquiries', $site) }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:border-indigo-300 transition">
            <p class="text-xs font-medium text-slate-500">Lead Enquiries</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $site->enquiries()->count() }}</p>
        </a>
    </div>

    <!-- Aligned 2-Column Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
        <!-- Attention Card -->
        @include('dashboard.partials.attention-card', ['site' => $site, 'openQuestions' => $openQuestions ?? collect()])

        <!-- Generation & Sections Card -->
        <section
            class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full flex flex-col justify-between"
            x-data="{
                state: @js($generationStatus['state'] ?? 'idle'),
                stage: @js($generationStatus['stage'] ?? ''),
                error: @js($generationStatus['error'] ?? ''),
                poll() {
                    if (this.state !== 'running') return;
                    fetch(@js(route('sites.generate.status', $site)))
                        .then(r => r.json())
                        .then(data => {
                            this.state = data.state;
                            this.stage = data.stage || '';
                            this.error = data.error || '';
                            if (this.state === 'running') {
                                setTimeout(() => this.poll(), 1500);
                            } else if (this.state === 'done') {
                                const url = new URL(window.location.href);
                                url.searchParams.set('generated', '1');
                                window.location.href = url.toString();
                            }
                        });
                }
            }"
            x-init="if (state === 'running') poll()"
        >
            <div>
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-base font-semibold text-slate-900">AI Website Builder</h3>
                    <span class="text-xs font-medium bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg">
                        {{ $site->sections_count ?? $site->sections()->count() }} built
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-500">
                    Generates Hero, About, Services, and Contact copy strictly from your verified facts.
                </p>
            </div>

            @if (session('error'))
                <div class="mt-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <template x-if="state === 'running'">
                <div class="mt-3 flex items-center gap-2 rounded-lg bg-indigo-50 border border-indigo-200 px-3 py-2 text-xs font-medium text-indigo-800">
                    <svg class="h-4 w-4 animate-spin shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="stage || 'Generating website with AI…'"></span>
                </div>
            </template>

            <template x-if="state === 'failed'">
                <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 space-y-2">
                    <p class="text-xs font-medium text-amber-900" x-text="error || 'AI generation failed. You can retry or build a basic site without AI.'"></p>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('sites.generate', $site) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">
                                Retry AI
                            </button>
                        </form>
                        <form method="POST" action="{{ route('sites.generate.fallback', $site) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800 text-white hover:bg-slate-900">
                                Build basic site without AI
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <div class="mt-5 flex flex-wrap items-center gap-2.5">
                @if ($canGenerate ?? true)
                    <form method="POST" action="{{ route('sites.generate', $site) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-md ring-2 ring-indigo-400 ring-offset-2 animate-pulse cursor-pointer"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>Generate website &amp; Auto-Publish</span>
                        </button>
                    </form>
                @else
                    <button
                        type="button"
                        disabled
                        class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-slate-200 text-slate-500 cursor-not-allowed"
                    >
                        Generate website
                    </button>
                    <span class="text-xs text-amber-700">Resolve open business/conflict questions first.</span>
                @endif

                <a href="{{ route('sites.sections', $site) }}" class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-medium border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Edit Sections
                </a>
                <a href="{{ route('sites.media', $site) }}" class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-medium border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Upload Images
                </a>
            </div>
        </section>

        <!-- Publish Card -->
        @include('dashboard.partials.publish-card', [
            'site' => $site,
            'publishBlockReason' => $publishBlockReason ?? null,
        ])

        <!-- QR Code Card -->
        @include('dashboard.partials.qr-card', ['site' => $site])

        <!-- WhatsApp Chat Widget -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">WhatsApp Chat Widget</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Floating WhatsApp button using your verified phone number.
                    </p>
                </div>
                @if ($site->whatsapp_enabled ?? true)
                    <span class="text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-lg">
                        Enabled
                    </span>
                @else
                    <span class="text-xs font-medium bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg">
                        Disabled
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('sites.whatsapp.update', $site) }}" class="mt-4 space-y-3">
                @csrf
                @method('PATCH')

                <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                    <input
                        type="checkbox"
                        name="whatsapp_enabled"
                        value="1"
                        @checked($site->whatsapp_enabled ?? true)
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <span>Show floating WhatsApp button on live site</span>
                </label>

                <div>
                    <label for="whatsapp_greeting" class="block text-xs font-medium text-slate-700">
                        Custom pre-filled message (optional)
                    </label>
                    <input
                        id="whatsapp_greeting"
                        name="whatsapp_greeting"
                        type="text"
                        value="{{ old('whatsapp_greeting', $site->whatsapp_greeting) }}"
                        placeholder="Auto-fills with business name & service"
                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 transition"
                >
                    Save WhatsApp settings
                </button>
            </form>
        </section>

        <!-- Multi-Language Translation -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full flex flex-col">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Multi-Language Translation</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Translate copy while keeping phone numbers and addresses verbatim.
                    </p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($site->translations as $tr)
                        <a
                            href="{{ route('public.site', $site->slug) }}?lang={{ $tr->locale }}"
                            target="_blank"
                            rel="noopener"
                            class="text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-0.5 rounded-lg hover:bg-indigo-100"
                        >
                            {{ \App\Services\Translator::SUPPORTED_LOCALES[$tr->locale] ?? strtoupper($tr->locale) }}
                        </a>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('sites.translate', $site) }}" class="mt-5 flex flex-wrap items-center gap-3">
                @csrf
                <select name="locale" class="flex-1 min-w-[160px] rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (\App\Services\Translator::SUPPORTED_LOCALES as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition"
                >
                    Translate Site
                </button>
            </form>
        </section>

        <!-- Custom Domain Binding -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Custom Domain</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Connect your own domain (e.g. <code class="bg-slate-100 px-1.5 py-0.5 rounded">www.yourbusiness.com</code>) by pointing a CNAME record to <code class="bg-slate-100 px-1.5 py-0.5 rounded">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost() }}</code>.
                    </p>
                </div>
                @if ($site->domain_verified_at)
                    <span class="text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-lg">
                        Verified
                    </span>
                @elseif ($site->custom_domain)
                    <span class="text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-lg">
                        Pending DNS
                    </span>
                @endif
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('sites.domain.update', $site) }}" class="flex flex-wrap items-center gap-2 flex-1">
                    @csrf
                    @method('PATCH')
                    <input
                        type="text"
                        name="custom_domain"
                        value="{{ old('custom_domain', $site->custom_domain) }}"
                        placeholder="www.yourbusiness.com"
                        class="flex-1 min-w-[220px] rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <button
                        type="submit"
                        class="px-4 py-2 rounded-lg text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 transition"
                    >
                        Save Domain
                    </button>
                </form>

                @if ($site->custom_domain)
                    <form method="POST" action="{{ route('sites.domain.verify', $site) }}">
                        @csrf
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition"
                        >
                            Verify DNS
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
