@extends('dashboard.layout', ['site' => $site, 'activeTab' => $activeTab])

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-xl font-semibold text-slate-900">{{ $title }}</h2>
        <p class="mt-2 text-sm text-slate-500">
            This section will be populated in an upcoming phase.
        </p>
    </div>
@endsection
