<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $seoTitle }}</title>
<link rel="icon" type="image/svg+xml" href="{{ route('public.site.favicon', $site->slug) }}">
@if ($seoDescription !== '')
    <meta name="description" content="{{ $seoDescription }}">
    <meta property="og:description" content="{{ $seoDescription }}">
@endif
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:type" content="website">
<meta property="og:image" content="{{ route('public.site.og', $site->slug) }}">
@if ($isPreview || $site->status->value !== 'published')
    <meta name="robots" content="noindex, nofollow">
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
    :root {
        --brand-accent: {{ $accentColor ?? '#4f46e5' }};
    }
    html {
        scroll-behavior: smooth;
    }
    @if (($fontPairing ?? 'modern') === 'classic')
        body { font-family: Georgia, Cambria, "Times New Roman", Times, serif; }
    @elseif (($fontPairing ?? 'modern') === 'warm')
        body { font-family: "Trebuchet MS", "Segoe UI", system-ui, sans-serif; }
    @endif
    .tpl-card {
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .tpl-card:hover {
        transform: translateY(-2px);
    }
    .tpl-services-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1.25rem;
    }
    .tpl-card-subgrid {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    @media (min-width: 640px) {
        .tpl-services-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .tpl-card-subgrid {
            display: grid;
            grid-row: span 2;
            grid-template-rows: subgrid;
            row-gap: 0.75rem;
        }
        .tpl-card-full {
            grid-column: 1 / -1;
        }
    }
    .tpl-btn {
        transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
    }
    .tpl-btn:hover {
        transform: translateY(-1px);
    }
    .tpl-btn:active {
        transform: scale(0.98);
    }
    @media (prefers-reduced-motion: no-preference) {
        .tpl-fade-up {
            animation: tplFadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .tpl-fade-up-delay {
            animation: tplFadeUp 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;
        }
        @keyframes tplFadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    }
</style>
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
