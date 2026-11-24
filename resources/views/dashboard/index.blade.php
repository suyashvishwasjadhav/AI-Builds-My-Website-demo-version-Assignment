<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My sites') }}
            </h2>
            <a
                href="{{ route('sites.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
            >
                {{ __('Create new site') }}
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($sites->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center">
                    <h3 class="text-lg font-medium text-slate-900">No sites yet</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Share your business details, links, or documents to build your first website.
                    </p>
                    <div class="mt-6">
                        <a
                            href="{{ route('sites.create') }}"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                        >
                            Create new site
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($sites as $site)
                        <a
                            href="{{ route('sites.show', $site) }}"
                            class="block bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 hover:border-indigo-300 hover:shadow transition p-6"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900">{{ $site->business_name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $site->city }}</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize bg-slate-100 text-slate-700">
                                    {{ $site->status->value }}
                                </span>
                            </div>
                            <div class="mt-4 text-xs text-slate-400">
                                /s/{{ $site->slug }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
