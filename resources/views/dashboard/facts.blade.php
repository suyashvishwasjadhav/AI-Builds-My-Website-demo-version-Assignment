@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'facts'])

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-slate-900">Facts</h2>
        <p class="mt-1 text-sm text-slate-600">
            Structured facts extracted from your sources. Only confirmed or user-provided facts appear on your published website.
        </p>
    </div>

    @if ($facts->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
            <p class="text-sm text-slate-500">No facts extracted yet.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Key</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Value</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Source &amp; Snippet</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Confidence</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($facts as $fact)
                            @php
                                $statusBadge = match ($fact->status->value) {
                                    'confirmed', 'user_provided' => 'bg-emerald-100 text-emerald-800',
                                    'conflict' => 'bg-amber-100 text-amber-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap">
                                    {{ str_replace('_', ' ', ucfirst($fact->key)) }}
                                </td>
                                <td class="px-4 py-3 text-slate-800">
                                    {{ $fact->value }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-medium text-slate-700">
                                        {{ $fact->source?->label ?: 'Direct input' }}
                                    </div>
                                    @if ($fact->snippet)
                                        <blockquote class="mt-1 text-xs text-slate-500 border-l-2 border-slate-300 pl-2">
                                            {{ $fact->snippet }}
                                        </blockquote>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600 capitalize whitespace-nowrap">
                                    {{ $fact->confidence->value }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusBadge }}">
                                        {{ str_replace('_', ' ', $fact->status->value) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
