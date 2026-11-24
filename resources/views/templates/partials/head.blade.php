<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $seoTitle }}</title>
@if ($seoDescription !== '')
    <meta name="description" content="{{ $seoDescription }}">
    <meta property="og:description" content="{{ $seoDescription }}">
@endif
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:type" content="website">
@if ($isPreview || $site->status->value !== 'published')
    <meta name="robots" content="noindex, nofollow">
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
