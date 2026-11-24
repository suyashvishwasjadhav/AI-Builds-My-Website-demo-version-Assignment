@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'overview'])

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-slate-900">Overview</h2>
        <p class="mt-1 text-sm text-slate-600">
            {{ $site->business_name }} &middot; {{ $site->city }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Needs your attention -->
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Needs your attention</h3>
                @if (($openQuestions ?? collect())->isNotEmpty())
                    <span class="text-xs font-medium bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full">
                        {{ $openQuestions->count() }} open
                    </span>
                @endif
            </div>
            @if (($openQuestions ?? collect())->isEmpty())
                <p class="mt-2 text-sm text-slate-500">
                    No open questions right now. Any conflicts, missing details, or flagged items will appear here.
                </p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($openQuestions as $question)
                        <li class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            {{ $question->prompt }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

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
        <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-slate-900">Sections</h3>
            <p class="mt-2 text-sm text-slate-500">
                Generated website sections (Hero, About, Services, Gallery, Contact) will appear here once built.
            </p>
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
