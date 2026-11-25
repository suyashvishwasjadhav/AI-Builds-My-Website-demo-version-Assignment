<!DOCTYPE html>
<html lang="{{ $site->language ?: 'en' }}">
<head>
    @include('templates.partials.head')
</head>
<body class="bg-slate-950 text-slate-100 antialiased">
    @include('templates.partials.preview-bar')
    @include('templates.partials.demo-nav')

    @php
        $hero = $sections->get('hero')?->content ?? [];
        $about = $sections->get('about')?->content ?? [];
        $services = $sections->get('services')?->content ?? [];
        $contact = $sections->get('contact')?->content ?? [];
        $heroPhoto = $photos->firstWhere('is_hero', true) ?? $photos->first();
        $hasContact = ! empty($contact['intro']) || ! empty($facts['phone']) || ! empty($facts['email']) || ! empty($facts['address']) || ! empty($facts['hours']);
    @endphp

    <!-- Navbar -->
    <nav class="sticky top-0 z-30 border-b border-slate-800/90 bg-slate-950/90 backdrop-blur-md">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
            <a href="#" class="flex items-center gap-2.5 min-w-0">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center text-white text-sm font-bold shrink-0" style="background-color: var(--brand-accent);">
                    {{ mb_strtoupper(mb_substr(trim($site->business_name) ?: 'S', 0, 1)) }}
                </span>
                <span class="font-bold text-white truncate">{{ $site->business_name }}</span>
            </a>

            <div class="hidden md:flex items-center gap-6 text-sm text-slate-300">
                @if (! empty($about['body']))
                    <a href="#about" class="hover:text-white transition">About</a>
                @endif
                @if (! empty($services['items']))
                    <a href="#services" class="hover:text-white transition">Services</a>
                @endif
                @if (! empty($priceItems))
                    <a href="#pricing" class="hover:text-white transition">Pricing</a>
                @endif
                @if ($hasContact)
                    <a href="#contact" class="hover:text-white transition">Contact</a>
                @endif
            </div>

            <div class="flex items-center gap-2">
                @if (! empty($facts['phone'][0]))
                    <a
                        href="tel:{{ preg_replace('/\s+/', '', $facts['phone'][0]) }}"
                        class="tpl-btn inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold text-white shadow-sm"
                        style="background-color: var(--brand-accent);"
                    >
                        Call {{ $facts['phone'][0] }}
                    </a>
                @elseif ($hasContact)
                    <a
                        href="#contact"
                        class="tpl-btn inline-flex items-center px-4 py-2 rounded-lg text-xs font-semibold text-white shadow-sm"
                        style="background-color: var(--brand-accent);"
                    >
                        Contact Us
                    </a>
                @endif
            </div>
        </div>
    </nav>

    @if (! empty($hero['headline']))
        @php
            $bgImageUrl = $heroPhoto ? asset('storage/'.$heroPhoto->path) : ($demoHeroImage ?? $defaultHeroImage ?? null);
        @endphp
        <header
            class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-950 to-indigo-950 border-b border-slate-800"
            @if ($bgImageUrl) style="background-image: url('{{ $bgImageUrl }}'); background-size: cover; background-position: center;" @endif
        >
            @if ($bgImageUrl)
                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/80 to-indigo-950/65" aria-hidden="true"></div>
            @endif
            <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 py-20 sm:py-28">
                <div class="tpl-fade-up max-w-2xl">
                    <div class="flex flex-wrap items-center gap-2.5 mb-5">
                        <span class="inline-flex items-center rounded-lg bg-slate-800/80 backdrop-blur border border-slate-700 px-3 py-1 text-xs font-medium text-slate-200">
                            {{ $site->city }}
                        </span>
                        @if (! empty($openStatus))
                            @include('templates.partials.open-status-badge')
                        @endif
                    </div>

                    <h1
                        class="text-4xl sm:text-5xl font-extrabold tracking-tight text-white leading-[1.1]"
                        @if ($isPreview && $sections->has('hero')) data-inline-field="headline" data-inline-url="{{ route('sites.sections.inline', [$site, $sections->get('hero')]) }}" @endif
                    >
                        {{ $hero['headline'] }}
                    </h1>
                    @if (! empty($hero['subheadline']))
                        <p
                            class="mt-5 text-lg text-indigo-200 leading-relaxed"
                            @if ($isPreview && $sections->has('hero')) data-inline-field="subheadline" data-inline-url="{{ route('sites.sections.inline', [$site, $sections->get('hero')]) }}" @endif
                        >{{ $hero['subheadline'] }}</p>
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-3 tpl-fade-up-delay">
                        @if ($hasContact)
                            <a
                                href="#contact"
                                class="tpl-btn inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm"
                                style="background-color: var(--brand-accent);"
                            >
                                Get Started
                            </a>
                        @endif
                        @if (! empty($services['items']))
                            <a
                                href="#services"
                                class="tpl-btn inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold bg-slate-900/80 backdrop-blur border border-slate-700 text-slate-200 hover:border-slate-500"
                            >
                                View Services
                            </a>
                        @endif
                    </div>
                </div>

                @if (! $bgImageUrl && $showPreviewPlaceholder)
                    <a href="{{ route('sites.media', $site) }}" target="_top" class="mt-8 w-full h-32 rounded-2xl border-2 border-dashed border-slate-700 bg-slate-900 hover:border-indigo-400 flex items-center justify-center text-xs font-medium text-slate-300 hover:text-indigo-300 transition">
                        Click here to upload a Header Background photo (preview only)
                    </a>
                @endif
            </div>
        </header>
    @endif

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-16 space-y-20">
        @if (! empty($about['body']))
            <section id="about" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                <div class="md:col-span-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400">About</p>
                    <h2 class="mt-1.5 text-2xl sm:text-3xl font-bold text-white">{{ $about['heading'] ?? 'About Us' }}</h2>
                </div>
                <div class="md:col-span-8 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 sm:p-8 text-slate-300 leading-relaxed whitespace-pre-line">{{ $about['body'] }}</div>
            </section>
        @endif

        @php
            $serviceItems = collect($services['items'] ?? [])
                ->filter(fn ($i) => ! empty(trim((string) ($i['name'] ?? ''))))
                ->values();
        @endphp
        @if ($serviceItems->isNotEmpty())
            <section id="services">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400">What We Offer</p>
                    <h2 class="mt-1.5 text-2xl sm:text-3xl font-bold text-white">{{ $services['heading'] ?? 'Our Services' }}</h2>
                </div>
                <div class="tpl-services-grid">
                    @foreach ($serviceItems as $idx => $item)
                        @php
                            $itemName = trim((string) $item['name']);
                            $itemDesc = trim((string) ($item['description'] ?? ''));
                        @endphp
                        <article class="tpl-card tpl-card-subgrid p-6 rounded-2xl bg-slate-900 border border-slate-800 hover:border-indigo-500/60 {{ ($serviceItems->count() % 2 === 1 && $loop->last) ? 'tpl-card-full' : '' }}">
                            <div>
                                <span class="text-xs font-mono font-semibold text-indigo-400">{{ str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <h3 class="mt-2 font-semibold text-lg text-white leading-snug">{{ $itemName }}</h3>
                            </div>
                            @if ($itemDesc !== '')
                                <p class="pt-3 border-t border-slate-800 text-sm text-slate-300 leading-relaxed">{{ $itemDesc }}</p>
                            @else
                                <div aria-hidden="true"></div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($photos->isNotEmpty() || ! empty($demoGalleryImages) || $showPreviewPlaceholder)
            <section id="gallery">
                <h2 class="text-2xl font-bold text-white">Gallery</h2>
                @if ($photos->isNotEmpty())
                    <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach ($photos as $photo)
                            <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $photo->alt_text ?: $site->business_name }}" loading="lazy" class="tpl-card w-full h-48 object-cover rounded-xl ring-1 ring-white/10">
                        @endforeach
                    </div>
                @elseif (! empty($demoGalleryImages))
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach ($demoGalleryImages as $img)
                            <img src="{{ $img['url'] }}" alt="{{ $img['alt'] }}" loading="lazy" class="tpl-card w-full h-48 object-cover rounded-xl ring-1 ring-white/10">
                        @endforeach
                    </div>
                @else
                    <div class="mt-4 p-6 rounded-xl border-2 border-dashed border-slate-700 bg-slate-900 text-center text-xs text-slate-400">
                        Placeholder gallery (preview only — hidden on public site until photos are uploaded)
                    </div>
                @endif
            </section>
        @endif

        @include('templates.partials.price-list')

        @if ($hasContact)
            <section id="contact" class="bg-slate-900 rounded-2xl border border-slate-800 p-6 sm:p-10">
                <h2 class="text-2xl sm:text-3xl font-bold text-white">{{ $contact['heading'] ?? 'Contact' }}</h2>
                @if (! empty($contact['intro']))
                    <p class="mt-2 text-slate-300">{{ $contact['intro'] }}</p>
                @endif
                <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @if (! empty($facts['phone'][0]))
                        <div class="bg-slate-950 rounded-xl border border-slate-800 p-4"><dt class="text-xs font-medium text-slate-400">Phone</dt><dd class="mt-1 font-medium text-white">{{ $facts['phone'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['email'][0]))
                        <div class="bg-slate-950 rounded-xl border border-slate-800 p-4"><dt class="text-xs font-medium text-slate-400">Email</dt><dd class="mt-1 font-medium text-white">{{ $facts['email'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['address'][0]))
                        <div class="bg-slate-950 rounded-xl border border-slate-800 p-4"><dt class="text-xs font-medium text-slate-400">Address</dt><dd class="mt-1 font-medium text-white">{{ $facts['address'][0] }}</dd></div>
                    @endif
                    @if (! empty($facts['hours'][0]))
                        <div class="bg-slate-950 rounded-xl border border-slate-800 p-4"><dt class="text-xs font-medium text-slate-400">Opening Hours</dt><dd class="mt-1 font-medium text-white">{{ $facts['hours'][0] }}</dd></div>
                    @endif
                </dl>
                @include('templates.partials.social-links')
                @include('templates.partials.map-embed')
                @include('templates.partials.enquiry-form')
            </section>
        @endif
    </main>

    <footer class="border-t border-slate-800 bg-slate-950 py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 grid grid-cols-1 sm:grid-cols-3 gap-8 text-sm">
            <div>
                <p class="font-bold text-white">{{ $site->business_name }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $site->city }}</p>
            </div>
            <div class="space-y-1.5 text-xs text-slate-400">
                @if (! empty($about['body']))
                    <div><a href="#about" class="hover:text-white">About</a></div>
                @endif
                @if (! empty($services['items']))
                    <div><a href="#services" class="hover:text-white">Services</a></div>
                @endif
                @if ($hasContact)
                    <div><a href="#contact" class="hover:text-white">Contact</a></div>
                @endif
            </div>
            <div class="space-y-1 text-xs text-slate-400 sm:text-right">
                @if (! empty($facts['phone'][0]))
                    <div>{{ $facts['phone'][0] }}</div>
                @endif
                @if (! empty($facts['email'][0]))
                    <div>{{ $facts['email'][0] }}</div>
                @endif
                <div class="pt-2 text-slate-500">&copy; {{ date('Y') }} {{ $site->business_name }}</div>
            </div>
        </div>
    </footer>

    @include('templates.partials.language-switcher')
    @include('templates.partials.whatsapp-widget')
    @include('templates.partials.inline-editor')
    @include('templates.partials.template-animations')
</body>
</html>
