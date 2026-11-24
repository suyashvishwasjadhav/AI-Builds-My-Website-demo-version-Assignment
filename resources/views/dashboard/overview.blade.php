@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'overview'])

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-slate-900">Overview</h2>
        <p class="mt-1 text-sm text-slate-600">
            {{ $site->business_name }} &middot; {{ $site->city }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @include('dashboard.partials.attention-card', ['site' => $site, 'openQuestions' => $openQuestions ?? collect()])

        <!-- Sources -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Sources</h3>
                <span class="text-xs font-medium bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-full">
                    {{ $site->sources_count ?? $site->sources()->count() }} added
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-500">
                Links, uploaded documents, and search results used to gather verified facts for your website.
            </p>
        </section>

        <!-- Sections -->
        <section
            class="bg-white rounded-xl border border-slate-200 shadow-sm p-6"
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
                                window.location.reload();
                            }
                        });
                }
            }"
            x-init="if (state === 'running') poll()"
        >
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-base font-semibold text-slate-900">Sections</h3>
                <span class="text-xs font-medium bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-full">
                    {{ $site->sections_count ?? $site->sections()->count() }} built
                </span>
            </div>
            <p class="mt-2 text-sm text-slate-500">
                Generated website sections (Hero, About, Services, Contact) built only from your confirmed facts.
            </p>

            @if (session('error'))
                <div class="mt-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <template x-if="state === 'running'">
                <div class="mt-3 rounded-lg bg-indigo-50 border border-indigo-200 px-3 py-2 text-xs font-medium text-indigo-800" x-text="stage || 'Working…'"></div>
            </template>

            <template x-if="state === 'failed' && error">
                <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 space-y-2">
                    <p class="text-xs text-amber-900" x-text="error"></p>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('sites.generate', $site) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">
                                Retry AI
                            </button>
                        </form>
                        <form method="POST" action="{{ route('sites.generate.fallback', $site) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium bg-slate-800 text-white hover:bg-slate-900">
                                Build basic site without AI
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                @if ($canGenerate ?? true)
                    <form method="POST" action="{{ route('sites.generate', $site) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition"
                        >
                            Generate website
                        </button>
                    </form>
                @else
                    <button
                        type="button"
                        disabled
                        class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-slate-200 text-slate-500 cursor-not-allowed"
                    >
                        Generate website
                    </button>
                    <span class="text-xs text-amber-700">Resolve open business/conflict questions first.</span>
                @endif

                <a
                    href="{{ route('sites.preview', $site) }}"
                    class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition"
                >
                    Preview &amp; theme
                </a>
            </div>
        </section>

        <!-- Publish -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-slate-900">Publish</h3>
            <p class="mt-2 text-sm text-slate-500">
                Your site is currently <span class="font-medium capitalize">{{ $site->status->value }}</span>.
                Public URL: <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">/s/{{ $site->slug }}</code>
            </p>
        </section>
    </div>
@endsection
