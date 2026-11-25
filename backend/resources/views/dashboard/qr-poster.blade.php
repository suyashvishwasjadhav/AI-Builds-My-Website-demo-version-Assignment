<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $site->business_name }} — QR Code Poster</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 flex flex-col items-center justify-center p-6">
    <div class="no-print mb-6 flex items-center gap-3">
        <a href="{{ route('sites.show', $site) }}" class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50">
            &larr; Back to Dashboard
        </a>
        <button
            type="button"
            onclick="window.print()"
            class="px-4 py-2 rounded-lg bg-indigo-600 text-xs font-semibold text-white hover:bg-indigo-700"
        >
            Print Poster
        </button>
    </div>

    <main class="w-full max-w-md rounded-3xl bg-white border border-slate-200 shadow-xl p-10 text-center space-y-6">
        <div class="space-y-1">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">Scan to Visit Our Website</p>
            <h1 class="text-2xl font-bold text-slate-900">{{ $site->business_name }}</h1>
            <p class="text-sm text-slate-500">{{ $site->city }}</p>
        </div>

        <div class="mx-auto w-64 h-64 p-4 rounded-2xl border-2 border-slate-900 bg-white flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
            {!! $qrSvg !!}
        </div>

        <div class="pt-2 border-t border-slate-100">
            <p class="text-xs font-mono text-slate-600 break-all">{{ $publicUrl }}</p>
        </div>
    </main>
</body>
</html>
