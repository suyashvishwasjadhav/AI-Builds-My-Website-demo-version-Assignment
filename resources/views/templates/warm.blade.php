<!DOCTYPE html>
<html lang="{{ $site->language ?: 'en' }}">
<head>
    @include('templates.partials.head')
</head>
<body class="bg-amber-50/40 text-stone-800 antialiased">
    @include('templates.partials.preview-bar')

    @php
        $hero = $sections->get('hero')?->content ?? [];
        $about = $sections->get('about')?->content ?? [];
        $services = $sections->get('services')?->content ?? [];
        $contact = $sections->get('contact')?->content ?? [];
        $heroPhoto = $photos->firstWhere('is_hero', true) ?? $photos->first();
    @endphp

    @if (! empty($hero['headline']))
        <header class="bg-amber-100/70 border-b border-amber-200">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-16 sm:py-20 grid grid-cols-1 {{ ($heroPhoto || $showPreviewPlaceholder) ? 'md:grid-cols-2' : '' }} gap-8 items-center">
                <div>
                    <h1 class="text-3xl sm:text-5xl font-bold tracking-tight text-stone-900">
                        {{ $hero['headline'] }}
                    </h1>
                    @if (! empty($hero['subheadline']))
                        <p class="mt-4 text-lg text-stone-700">{{ $hero['subheadline'] }}</p>
                    @endif
                </div>
                @if ($heroPhoto)
                    <img src="{{ asset('storage/'.$heroPhoto->path) }}" alt="{{ $heroPhoto->alt_text ?: $site->business_name }}" class="w-full h-64 object-cover rounded-2xl shadow-sm">
                @elseif ($showPreviewPlaceholder)
                    <div class="w-full h-64 rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50 flex items-center justify-center text-xs text-stone-500">
                        Placeholder image (preview only — upload photos to replace)
                    </div>
                @endif
            </div>
        </header>
    @endif

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-12 space-y-16">
        @if (! empty($about['body']))
            <section id="about">
                <h2 class="text-2xl font-semibold text-stone-900">{{ $about['heading'] ?? 'About Us' }}</h2>
                <div class="mt-4 text-stone-700 leading-relaxed whitespace-pre-line">{{ $about['body'] }}</div>
            </section>
        @endif

        @if (! empty($services['items']))
            <section id="services">
                <h2 class="text-2xl font-semibold text-stone-900">{{ $services['heading'] ?? 'Our Services' }}</h2>
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($services['items'] as $item)
                        @if (! empty($item['name']))
                            <div class="p-5 rounded-xl border border-amber-200 bg-white">
                                <h3 class="font-semibold text-stone-900">{{ $item['name'] }}</h3>
                                @if (! empty($item['description']))
                                    <p class="mt-1 text-sm text-stone-600">{{ $item['description'] }}</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @if ($photos->isNotEmpty() || $showPreviewPlaceholder)
            <section id="gallery">
                <h2 class="text-2xl font-semibold text-stone-900">Gallery</h2>
                @if ($photos->isNotEmpty())
                    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach ($photos as $photo)
                            <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $photo->alt_text ?: $site->business_name }}" class="w-full h-44 object-cover rounded-xl">
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 p-6 rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 text-center text-xs text-stone-500">
                        Placeholder gallery (preview only — hidden on public site until photos are uploaded)
                    </div>
                @endif
            </section>
        @endif

        @if (! empty($contact['intro']) || ! empty($facts['phone']) || ! empty($facts['email']) || ! empty($facts['address']) || ! empty($facts['hours']))
            <section id="contact" class="bg-white rounded-2xl border border-amber-200 p-6 sm:p-8">
                <h2 class="text-2xl font-semibold text-stone-900">{{ $contact['heading'] ?? 'Contact' }}</h2>
                @if (! empty($contact['intro']))
                    <p class="mt-2 text-stone-600">{{ $contact['intro'] }}</p>
                @endif
                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @if (! empty($facts['phone'][0]))
                        <div><dt class="font-medium text-stone-500">Phone</dt><dd class="text-stone-900">{{ $facts['phone'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['email'][0]))
                        <div><dt class="font-medium text-stone-500">Email</dt><dd class="text-stone-900">{{ $facts['email'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['address'][0]))
                        <div><dt class="font-medium text-stone-500">Address</dt><dd class="text-stone-900">{{ $facts['address'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['hours'][0]))
                        <div><dt class="font-medium text-stone-500">Opening Hours</dt><dd class="text-stone-900">{{ $facts['hours'][0] }}</dd></div>
                    @endif
                </dl>
                @include('templates.partials.enquiry-form')
            </section>
        @endif
    </main>

    <footer class="border-t border-amber-200 py-8 text-center text-xs text-stone-500">
        &copy; {{ date('Y') }} {{ $site->business_name }}
    </footer>
</body>
</html>
