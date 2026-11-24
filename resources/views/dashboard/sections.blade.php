@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'sections'])

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Sections</h2>
            <p class="mt-1 text-sm text-slate-600">
                Edit section text by hand, regenerate one section with AI, toggle visibility, or restore a previous version.
            </p>
        </div>
        <a href="{{ route('sites.preview', $site) }}" class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50">
            Preview &amp; theme
        </a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
            {{ session('error') }}
        </div>
    @endif

    @if ($sections->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
            <p class="text-sm text-slate-500">
                No sections generated yet. Go to <a href="{{ route('sites.show', $site) }}" class="text-indigo-600 underline">Overview</a> to generate your website.
            </p>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($sections as $section)
                @php
                    $content = $section->content ?? [];
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-base font-semibold text-slate-900 capitalize">{{ $section->type->value }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $section->is_visible ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $section->is_visible ? 'Visible' : 'Hidden' }}
                            </span>
                            @if ($section->currentVersion)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $section->currentVersion->created_by->value === 'ai' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $section->currentVersion->created_by->value === 'ai' ? 'AI-written' : 'User-written' }}
                                </span>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('sites.sections.toggle', [$site, $section]) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium border border-slate-300 text-slate-700 hover:bg-slate-50">
                                {{ $section->is_visible ? 'Hide section' : 'Show section' }}
                            </button>
                        </form>
                    </div>

                    <!-- Edit form -->
                    <form method="POST" action="{{ route('sites.sections.update', [$site, $section]) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')

                        @if ($section->type->value === 'hero')
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Headline</label>
                                <input type="text" name="headline" value="{{ $content['headline'] ?? '' }}" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Subheadline</label>
                                <input type="text" name="subheadline" value="{{ $content['subheadline'] ?? '' }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                            </div>
                        @elseif ($section->type->value === 'about')
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Heading</label>
                                <input type="text" name="heading" value="{{ $content['heading'] ?? 'About Us' }}" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Body</label>
                                <textarea name="body" rows="4" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">{{ $content['body'] ?? '' }}</textarea>
                            </div>
                        @elseif ($section->type->value === 'services')
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Heading</label>
                                <input type="text" name="heading" value="{{ $content['heading'] ?? 'Our Services' }}" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Items (one per line: Name | Description)</label>
                                @php
                                    $itemsLines = collect($content['items'] ?? [])
                                        ->map(fn ($i) => trim(($i['name'] ?? '').(! empty($i['description']) ? ' | '.$i['description'] : '')))
                                        ->implode("\n");
                                @endphp
                                <textarea name="items_text" rows="4" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">{{ $itemsLines }}</textarea>
                            </div>
                        @else
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Heading</label>
                                <input type="text" name="heading" value="{{ $content['heading'] ?? 'Contact Us' }}" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700">Intro</label>
                                <textarea name="intro" rows="3" required class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm">{{ $content['intro'] ?? '' }}</textarea>
                            </div>
                        @endif

                        <button type="submit" class="px-3.5 py-1.5 rounded-md text-xs font-semibold bg-slate-800 text-white hover:bg-slate-900">
                            Save changes
                        </button>
                    </form>

                    <!-- Regenerate one section -->
                    <form method="POST" action="{{ route('sites.sections.regenerate', [$site, $section]) }}" class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2">
                        @csrf
                        <input
                            type="text"
                            name="instruction"
                            placeholder="Optional instruction (e.g. shorter, more formal)"
                            class="flex-1 min-w-[200px] rounded-md border-slate-300 text-xs shadow-sm"
                        >
                        <button type="submit" class="px-3.5 py-1.5 rounded-md text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700">
                            Regenerate section
                        </button>
                    </form>

                    <!-- Version history -->
                    @if ($section->versions->isNotEmpty())
                        <details class="pt-3 border-t border-slate-100">
                            <summary class="text-xs font-medium text-slate-600 hover:text-slate-900 cursor-pointer">
                                Version history ({{ $section->versions->count() }})
                            </summary>
                            <ul class="mt-3 space-y-2">
                                @foreach ($section->versions as $version)
                                    <li class="flex flex-wrap items-center justify-between gap-2 text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="px-2 py-0.5 rounded-full font-medium {{ $version->created_by->value === 'ai' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $version->created_by->value === 'ai' ? 'AI-written' : 'User-written' }}
                                            </span>
                                            <span class="text-slate-700">{{ $version->note ?: 'Saved version' }}</span>
                                            <span class="text-slate-400">{{ $version->created_at?->diffForHumans() }}</span>
                                            @if ($section->current_version_id === $version->id)
                                                <span class="font-semibold text-emerald-700">(Current)</span>
                                            @endif
                                        </div>

                                        @if ($section->current_version_id !== $version->id)
                                            <form method="POST" action="{{ route('sites.sections.restore', [$site, $section, $version]) }}">
                                                @csrf
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-800 font-medium underline">
                                                    Restore this version
                                                </button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
