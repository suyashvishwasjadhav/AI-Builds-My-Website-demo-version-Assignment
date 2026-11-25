@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'facts'])

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Verified Facts</h2>
            <p class="mt-1 text-sm text-slate-500">
                Structured facts extracted from your sources. Only confirmed or user-provided facts appear on your published website.
            </p>
        </div>
        <div class="text-xs font-medium text-slate-500 bg-white border border-slate-200 rounded-lg px-3 py-1.5 shadow-sm">
            {{ $facts->count() }} total records
        </div>
    </div>

    @if ($facts->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
            <p class="text-sm text-slate-500">No facts extracted yet.</p>
        </div>
    @else
        <div x-data="{ filter: 'all' }" class="space-y-4">
            <!-- Quick Filter Bar -->
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['all' => 'All', 'confirmed' => 'Confirmed', 'user_provided' => 'User Provided', 'conflict' => 'Conflict', 'rejected' => 'Rejected'] as $key => $label)
                    <button
                        type="button"
                        @click="filter = '{{ $key }}'"
                        :class="filter === '{{ $key }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                        class="px-3 py-1.5 rounded-lg border text-xs font-medium transition cursor-pointer"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="divide-x divide-slate-200/70">
                                <th scope="col" class="w-36 px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Key</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Value</th>
                                <th scope="col" class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Source &amp; Snippet</th>
                                <th scope="col" class="w-28 px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Confidence</th>
                                <th scope="col" class="w-32 px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($facts as $fact)
                                @php
                                    $statusVal = $fact->status->value;
                                    $statusBadge = match ($statusVal) {
                                        'confirmed', 'user_provided' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                        'conflict' => 'bg-amber-50 text-amber-800 border border-amber-200',
                                        'rejected' => 'bg-rose-50 text-rose-700 border border-rose-200',
                                        default => 'bg-slate-100 text-slate-700 border border-slate-200',
                                    };
                                @endphp
                                <tr
                                    x-show="filter === 'all' || filter === '{{ $statusVal }}'"
                                    class="divide-x divide-slate-100 even:bg-slate-50/40 hover:bg-indigo-50/30 transition-colors"
                                >
                                    <td class="px-4 py-3.5 align-top font-semibold text-slate-900 whitespace-nowrap">
                                        {{ str_replace('_', ' ', ucfirst($fact->key)) }}
                                    </td>
                                    <td class="px-4 py-3.5 align-top text-slate-800 leading-relaxed break-words max-w-xs">
                                        {{ $fact->value }}
                                    </td>
                                    <td class="px-4 py-3.5 align-top text-slate-600 max-w-md">
                                        <div class="font-medium text-xs text-slate-800">
                                            {{ $fact->source?->label ?: 'Direct input' }}
                                        </div>
                                        @if ($fact->snippet)
                                            <blockquote class="mt-1.5 text-xs text-slate-500 border-l-2 border-slate-300 pl-2.5 leading-relaxed">
                                                {{ $fact->snippet }}
                                            </blockquote>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 align-top text-xs font-medium text-slate-600 capitalize whitespace-nowrap">
                                        {{ $fact->confidence->value }}
                                    </td>
                                    <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-medium capitalize {{ $statusBadge }}">
                                            {{ str_replace('_', ' ', $statusVal) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
