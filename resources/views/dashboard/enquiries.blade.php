@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'enquiries'])

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-slate-900">Enquiries</h2>
        <p class="mt-1 text-sm text-slate-600">
            Messages submitted from your public website contact form, newest first.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($enquiries->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
            <p class="text-sm text-slate-500">No enquiries received yet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($enquiries as $enquiry)
                <div class="bg-white rounded-xl border {{ $enquiry->read_at ? 'border-slate-200' : 'border-indigo-300' }} shadow-sm p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-slate-900">{{ $enquiry->name }}</h3>
                                @if (! $enquiry->read_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        New
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                        Read
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $enquiry->email }}
                                @if ($enquiry->phone)
                                    &middot; {{ $enquiry->phone }}
                                @endif
                                &middot; {{ $enquiry->created_at?->diffForHumans() }}
                            </p>
                        </div>

                        @if (! $enquiry->read_at)
                            <form method="POST" action="{{ route('sites.enquiries.read', [$site, $enquiry]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium bg-slate-100 text-slate-700 hover:bg-slate-200">
                                    Mark as read
                                </button>
                            </form>
                        @endif
                    </div>

                    <p class="mt-3 text-sm text-slate-700 whitespace-pre-line">{{ $enquiry->message }}</p>
                </div>
            @endforeach
        </div>
    @endif
@endsection
