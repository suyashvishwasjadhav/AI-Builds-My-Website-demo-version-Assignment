@php
    $publicUrl = route('public.site', $site->slug);
    $qrSvg = \App\Http\Controllers\QrCodeController::generateSvg($publicUrl);
@endphp

<section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-full flex flex-col justify-between gap-4">
    <div class="flex items-start justify-between gap-4">
        <div class="space-y-1.5 min-w-0">
            <h3 class="text-base font-semibold text-slate-900">Instant Printable QR Code</h3>
            <p class="text-xs text-slate-500">
                Scannable QR code for shop counters, packaging, or visiting cards.
            </p>
            <p class="text-xs font-mono text-indigo-700 break-all pt-1">{{ $publicUrl }}</p>
        </div>

        <div class="shrink-0 w-24 h-24 p-1.5 rounded-xl border border-slate-200 bg-white flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
            {!! $qrSvg !!}
        </div>
    </div>

    <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
        <a
            href="{{ route('sites.qr.download', $site) }}"
            class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition"
        >
            Download QR (SVG)
        </a>
        <a
            href="{{ route('sites.qr.poster', $site) }}"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition"
        >
            Print Poster
        </a>
    </div>
</section>
