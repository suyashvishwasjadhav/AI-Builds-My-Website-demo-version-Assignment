@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'sources'])

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Sources</h2>
            <p class="mt-1 text-sm text-slate-600">
                Files, links, and notes used as evidence for your website.
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($sources->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
            <p class="text-sm text-slate-500">No sources added for this site yet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($sources as $source)
                @php
                    $badgeClasses = match ($source->status->value) {
                        'read' => 'bg-emerald-100 text-emerald-800',
                        'blocked' => 'bg-amber-100 text-amber-800',
                        'failed' => 'bg-red-100 text-red-800',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $source->type->value }}
                                </span>
                                <h3 class="text-sm font-semibold text-slate-900 truncate">
                                    {{ $source->label ?: 'Source #'.$source->id }}
                                </h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $badgeClasses }}">
                                    {{ $source->status->value }}
                                </span>
                            </div>

                            @if ($source->type->value === 'link' && $source->path_or_url)
                                <p class="mt-1 text-xs text-slate-500 break-all">
                                    {{ $source->path_or_url }}
                                </p>
                            @endif

                            @if ($source->error_message)
                                <p class="mt-2 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                                    {{ $source->error_message }}
                                </p>
                            @endif
                        </div>

                        @if (in_array($source->status->value, ['failed', 'blocked', 'pending'], true))
                            <form method="POST" action="{{ route('sites.sources.retry', [$site, $source]) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition"
                                >
                                    Retry
                                </button>
                            </form>
                        @endif
                    </div>

                    @if ($source->raw_text)
                        <details class="mt-3">
                            <summary class="text-xs font-medium text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                View extracted text
                            </summary>
                            <pre class="mt-2 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 whitespace-pre-wrap max-h-60 overflow-y-auto">{{ $source->raw_text }}</pre>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
