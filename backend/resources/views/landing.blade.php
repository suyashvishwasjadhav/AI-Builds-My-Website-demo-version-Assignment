<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'AI Builds My Website') }} — Verified Websites from Real Business Data</title>
    <meta name="description" content="Share your business name, PDFs, links, and photos. AI builds a verified, ready-to-publish website without inventing facts.">

    @php
        $manifestPath = public_path('landing/.vite/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
        $entry = $manifest['index.html'] ?? null;
    @endphp

    @if ($entry)
        @foreach ($entry['css'] ?? [] as $cssFile)
            <link rel="stylesheet" href="{{ asset('landing/' . $cssFile) }}">
        @endforeach
    @endif
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    <div id="root"></div>

    <script>
        window.__APP_BRIDGE__ = @json($bridge);
    </script>

    @if ($entry && isset($entry['file']))
        <script type="module" src="{{ asset('landing/' . $entry['file']) }}"></script>
    @endif
</body>
</html>
