<!DOCTYPE html>
<html lang="{{ $site->language ?: 'en' }}">
<head>
    @include('templates.partials.head')
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    @include('templates.partials.preview-bar')

    @php
        $hero = $sections->get('hero')?->content ?? [];
        $about = $sections->get('about')?->content ?? [];
        $services = $sections->get('services')?->content ?? [];
        $contact = $sections->get('contact')?->content ?? [];
        $heroPhoto = $photos->firstWhere('is_hero', true) ?? $photos->first();
    @endphp

    @if (! empty($hero['headline']))
        <header class="bg-gradient-to-br from-slate-900 to-indigo-950 border-b border-slate-800">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-16 sm:py-24 grid grid-cols-1 {{ ($heroPhoto || $showPreviewPlaceholder) ? 'md:grid-cols-2' : '' }} gap-8 items-center">
                <div>
                    <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-white">
                        {{ $hero['headline'] }}
                    </h1>
                    @if (! empty($hero['subheadline']))
                        <p class="mt-4 text-lg text-indigo-200">{{ $hero['subheadline'] }}</p>
                    @endif
                </div>
                @if ($heroPhoto)
                    <img src="{{ asset('storage/'.$heroPhoto->path) }}" alt="{{ $heroPhoto->alt_text ?: $site->business_name }}" class="w-full h-64 object-cover rounded-2xl ring-1 ring-white/10">
                @elseif ($showPreviewPlaceholder)
                    <div class="w-full h-64 rounded-2xl border-2 border-dashed border-slate-700 bg-slate-900 flex items-center justify-center text-xs text-slate-400">
                        Placeholder image (preview only — upload photos to replace)
                    </div>
                @endif
            </div>
        </header>
    @endif

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-14 space-y-16">
        @if (! empty($about['body']))
            <section id="about">
                <h2 class="text-2xl font-bold text-white">{{ $about['heading'] ?? 'About Us' }}</h2>
                <div class="mt-4 text-slate-300 leading-relaxed whitespace-pre-line">{{ $about['body'] }}</div>
            </section>
        @endif

        @if (! empty($services['items']))
            <section id="services">
                <h2 class="text-2xl font-bold text-white">{{ $services['heading'] ?? 'Our Services' }}</h2>
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($services['items'] as $item)
                        @if (! empty($item['name']))
                            <div class="p-5 rounded-xl border border-slate-800 bg-slate-900">
                                <h3 class="font-semibold text-indigo-300">{{ $item['name'] }}</h3>
                                @if (! empty($item['description']))
                                    <p class="mt-1 text-sm text-slate-300">{{ $item['description'] }}</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @if ($photos->isNotEmpty() || $showPreviewPlaceholder)
            <section id="gallery">
                <h2 class="text-2xl font-bold text-white">Gallery</h2>
                @if ($photos->isNotEmpty())
                    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach ($photos as $photo)
                            <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $photo->alt_text ?: $site->business_name }}" class="w-full h-44 object-cover rounded-xl">
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 p-6 rounded-xl border-2 border-dashed border-slate-700 bg-slate-900 text-center text-xs text-slate-400">
                        Placeholder gallery (preview only — hidden on public site until photos are uploaded)
                    </div>
                @endif
            </section>
        @endif

        @if (! empty($contact['intro']) || ! empty($facts['phone']) || ! empty($facts['email']) || ! empty($facts['address']) || ! empty($facts['hours']))
            <section id="contact" class="bg-slate-900 rounded-2xl border border-slate-800 p-6 sm:p-8">
                <h2 class="text-2xl font-bold text-white">{{ $contact['heading'] ?? 'Contact' }}</h2>
                @if (! empty($contact['intro']))
                    <p class="mt-2 text-slate-300">{{ $contact['intro'] }}</p>
                @endif
                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @if (! empty($facts['phone'][0]))
                        <div><dt class="font-medium text-slate-400">Phone</dt><dd class="text-white">{{ $facts['phone'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['email'][0]))
                        <div><dt class="font-medium text-slate-400">Email</dt><dd class="text-white">{{ $facts['email'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['address'][0]))
                        <div><dt class="font-medium text-slate-400">Address</dt><dd class="text-white">{{ $facts['address'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['hours'][0]))
                        <div><dt class="font-medium text-slate-400">Opening Hours</dt><dd class="text-white">{{ $facts['hours'][0] }}</dd></div>
                    @endif
                </dl>
                @include('templates.partials.enquiry-form')
            </section>
        @endif
    </main>

    <footer class="border-t border-slate-800 py-8 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} {{ $site->business_name }}
    </footer>
</body>
</html>
