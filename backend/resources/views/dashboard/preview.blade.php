<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $site->business_name }} — Split-Screen Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="h-screen overflow-hidden bg-slate-950 text-slate-100 flex flex-col"
    x-data="{
        device: 'desktop',
        activeLang: 'en',
        regenSectionId: @js((string) ($site->sections->first()?->id ?? '')),
        regenInstruction: '',
        reloadFrame() {
            const frame = document.getElementById('studio-preview-frame');
            if (frame) {
                const base = @js(route('sites.preview', [$site, 'embed' => 1]));
                frame.src = base + '&lang=' + encodeURIComponent(this.activeLang) + '&t=' + Date.now();
            }
        }
    }"
>
    <!-- Top Studio Bar -->
    <header class="h-14 shrink-0 border-b border-slate-800 bg-slate-900 px-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('sites.show', $site) }}" class="text-xs font-medium text-slate-300 hover:text-white shrink-0">
                &larr; Dashboard
            </a>
            <span class="text-slate-700">|</span>
            <span class="text-sm font-semibold text-white truncate">{{ $site->business_name }}</span>
            <span class="text-xs rounded-full bg-indigo-500/20 text-indigo-300 px-2 py-0.5 capitalize shrink-0">
                {{ $site->template }}
            </span>
            @if (session('status'))
                <span class="hidden lg:inline-flex text-xs rounded-full bg-emerald-500/20 text-emerald-300 px-2.5 py-0.5">
                    {{ session('status') }}
                </span>
            @endif
        </div>

        <!-- Device Toggle Controls -->
        <div class="inline-flex items-center rounded-lg border border-slate-700 bg-slate-950 p-1 shrink-0" role="group" aria-label="Viewport device size">
            <button
                type="button"
                @click="device = 'desktop'"
                :class="device === 'desktop' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                class="px-3 py-1 rounded-md text-xs font-medium transition cursor-pointer"
            >
                Desktop
            </button>
            <button
                type="button"
                @click="device = 'tablet'"
                :class="device === 'tablet' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                class="px-3 py-1 rounded-md text-xs font-medium transition cursor-pointer"
            >
                Tablet (768px)
            </button>
            <button
                type="button"
                @click="device = 'mobile'"
                :class="device === 'mobile' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                class="px-3 py-1 rounded-md text-xs font-medium transition cursor-pointer"
            >
                Mobile (375px)
            </button>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button
                type="button"
                @click="reloadFrame()"
                class="px-3 py-1.5 rounded-md border border-slate-700 text-xs font-medium text-slate-300 hover:text-white hover:border-slate-500 cursor-pointer"
            >
                Refresh
            </button>
            <a
                href="{{ route('sites.preview', $site) }}"
                target="_blank"
                rel="noopener"
                class="px-3 py-1.5 rounded-md bg-slate-800 text-xs font-medium text-white hover:bg-slate-700"
            >
                Full Screen
            </a>
            @if ($site->status->value === 'published')
                <a
                    href="{{ route('public.site', $site->slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="px-3 py-1.5 rounded-md bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-500"
                >
                    View Live
                </a>
            @else
                <form method="POST" action="{{ route('sites.publish', $site) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-500 cursor-pointer">
                        Publish Now
                    </button>
                </form>
            @endif
        </div>
    </header>

    <!-- Split Workspace -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Sidebar Controls -->
        <aside class="w-80 shrink-0 border-r border-slate-800 bg-slate-900/90 p-5 overflow-y-auto space-y-6">
            @if ($errors->any())
                <div class="rounded-lg bg-rose-500/20 border border-rose-500/40 px-3 py-2 text-xs text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Header Background & Gallery Image Upload (Top of Sidebar) -->
            <div class="space-y-2.5 pb-4 border-b border-slate-800">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Header &amp; Gallery Images</h2>
                    <a href="{{ route('sites.media', $site) }}" class="text-xs text-indigo-400 hover:text-indigo-300">
                        Manage &rarr;
                    </a>
                </div>
                <form method="POST" action="{{ route('sites.media.store', $site) }}" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <input type="hidden" name="redirect_to" value="studio">
                    <input
                        type="file"
                        name="photos[]"
                        multiple
                        required
                        accept=".jpg,.jpeg,.png,.webp,image/*"
                        class="block w-full text-[11px] text-slate-300 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-[11px] file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer"
                    >
                    <label class="inline-flex items-center gap-2 text-[11px] text-slate-300 cursor-pointer">
                        <input type="checkbox" name="set_as_hero" value="1" checked class="rounded border-slate-700 bg-slate-950 text-indigo-600">
                        <span>Set as Header Background</span>
                    </label>
                    <button
                        type="submit"
                        class="w-full py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white transition cursor-pointer"
                    >
                        Upload Image(s)
                    </button>
                </form>
            </div>

            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Design Theme</h2>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    @foreach ($templates as $tpl)
                        <form method="POST" action="{{ route('sites.template.update', $site) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="template" value="{{ $tpl }}">
                            <input type="hidden" name="redirect_to" value="studio">
                            <button
                                type="submit"
                                class="w-full py-2 rounded-lg text-xs font-medium capitalize border transition cursor-pointer {{ $site->template === $tpl ? 'bg-indigo-600 border-indigo-500 text-white' : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-600' }}"
                            >
                                {{ $tpl }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            <!-- Brand Color & Font Pairing -->
            <form method="POST" action="{{ route('sites.theme_config.update', $site) }}" class="space-y-3 pt-4 border-t border-slate-800">
                @csrf
                @method('PATCH')
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Brand Style</h2>
                <div class="grid grid-cols-2 gap-2.5 items-end">
                    <div>
                        <label for="accent_color" class="block text-[11px] text-slate-400 mb-1">Accent Color</label>
                        <input
                            id="accent_color"
                            name="accent_color"
                            type="color"
                            value="{{ $site->theme_config['accent_color'] ?? '#4f46e5' }}"
                            class="h-9 w-full rounded border border-slate-700 bg-slate-950 p-1 cursor-pointer"
                        >
                    </div>
                    <div>
                        <label for="font_pairing" class="block text-[11px] text-slate-400 mb-1">Typography</label>
                        <select
                            id="font_pairing"
                            name="font_pairing"
                            class="w-full h-9 rounded-lg border border-slate-700 bg-slate-950 px-2 text-xs text-slate-200"
                        >
                            <option value="modern" @selected(($site->theme_config['font_pairing'] ?? 'modern') === 'modern')>Modern Sans</option>
                            <option value="classic" @selected(($site->theme_config['font_pairing'] ?? 'modern') === 'classic')>Classic Serif</option>
                            <option value="warm" @selected(($site->theme_config['font_pairing'] ?? 'modern') === 'warm')>Warm Humanist</option>
                        </select>
                    </div>
                </div>
                <button
                    type="submit"
                    class="w-full py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-medium text-white transition cursor-pointer"
                >
                    Apply Brand Style
                </button>
            </form>

            <!-- Sections Visibility Toggle -->
            <div class="pt-4 border-t border-slate-800">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sections (Click to Toggle)</h2>
                    <a href="{{ route('sites.sections', $site) }}" class="text-xs text-indigo-400 hover:text-indigo-300">
                        Edit all &rarr;
                    </a>
                </div>
                <ul class="mt-3 space-y-2">
                    @foreach ($site->sections as $section)
                        <li class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/70 px-3 py-2 text-xs">
                            <span class="font-medium text-slate-200 capitalize">{{ $section->type->value }}</span>
                            <form method="POST" action="{{ route('sites.sections.toggle', [$site, $section]) }}">
                                @csrf
                                <input type="hidden" name="redirect_to" value="studio">
                                <button
                                    type="submit"
                                    class="px-2 py-0.5 rounded-full text-[11px] font-medium cursor-pointer transition {{ $section->is_visible ? 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30' : 'bg-slate-800 text-slate-400 hover:text-white' }}"
                                >
                                    {{ $section->is_visible ? 'Visible' : 'Hidden' }}
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Quick AI Section Rewrite -->
            @if ($site->sections->isNotEmpty())
                <div class="pt-4 border-t border-slate-800 space-y-2.5">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">AI Section Rewrite</h2>
                    <form
                        method="POST"
                        :action="`{{ url('/sites/' . $site->id . '/sections') }}/${regenSectionId}/regenerate`"
                        class="space-y-2"
                    >
                        @csrf
                        <input type="hidden" name="redirect_to" value="studio">
                        <select
                            x-model="regenSectionId"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200"
                        >
                            @foreach ($site->sections as $section)
                                <option value="{{ $section->id }}">{{ ucfirst($section->type->value) }} section</option>
                            @endforeach
                        </select>
                        <input
                            type="text"
                            name="instruction"
                            x-model="regenInstruction"
                            placeholder="e.g. Make it punchier, shorter"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-200 placeholder-slate-500"
                        >
                        <button
                            type="submit"
                            class="w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white transition cursor-pointer"
                        >
                            Regenerate with AI
                        </button>
                    </form>
                </div>
            @endif

            <!-- Language Preview Switcher -->
            @if ($site->translations->isNotEmpty())
                <div class="pt-4 border-t border-slate-800 space-y-2">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Language Preview</h2>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            type="button"
                            @click="activeLang = 'en'; reloadFrame()"
                            :class="activeLang === 'en' ? 'bg-indigo-600 text-white' : 'bg-slate-950 text-slate-300 border border-slate-800'"
                            class="px-2.5 py-1 rounded-md text-xs font-medium cursor-pointer"
                        >
                            English
                        </button>
                        @foreach ($site->translations as $tr)
                            <button
                                type="button"
                                @click="activeLang = @js($tr->locale); reloadFrame()"
                                :class="activeLang === @js($tr->locale) ? 'bg-indigo-600 text-white' : 'bg-slate-950 text-slate-300 border border-slate-800'"
                                class="px-2.5 py-1 rounded-md text-xs font-medium cursor-pointer"
                            >
                                {{ \App\Services\Translator::SUPPORTED_LOCALES[$tr->locale] ?? strtoupper($tr->locale) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Quick Export & Tools -->
            <div class="pt-4 border-t border-slate-800 space-y-2">
                <a
                    href="{{ route('sites.export.zip', $site) }}"
                    class="block w-full text-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500 transition"
                >
                    Download Static ZIP
                </a>
                <a
                    href="{{ route('sites.qr.poster', $site) }}"
                    target="_blank"
                    rel="noopener"
                    class="block w-full text-center rounded-lg border border-slate-700 bg-slate-950 px-4 py-2 text-xs font-medium text-slate-200 hover:border-slate-500 transition"
                >
                    Print QR Poster
                </a>
                <p class="text-[11px] text-slate-500 text-center pt-1">
                    Tip: Click any headline in the preview to edit inline.
                </p>
            </div>
        </aside>

        <!-- Right Responsive Preview Canvas -->
        <main class="flex-1 bg-slate-950 p-4 sm:p-6 overflow-auto flex justify-center items-start">
            <div
                class="h-full bg-white rounded-xl shadow-2xl overflow-hidden transition-all duration-300 border border-slate-800"
                :style="device === 'mobile' ? 'width: 375px;' : (device === 'tablet' ? 'width: 768px;' : 'width: 100%;')"
            >
                <iframe
                    id="studio-preview-frame"
                    src="{{ route('sites.preview', [$site, 'embed' => 1]) }}"
                    title="{{ $site->business_name }} live preview"
                    class="w-full h-full border-0"
                ></iframe>
            </div>
        </main>
    </div>
</body>
</html>
