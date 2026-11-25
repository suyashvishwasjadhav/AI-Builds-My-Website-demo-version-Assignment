<?php

namespace App\Http\Controllers;

use App\Models\Site;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QrCodeController extends Controller
{
    public static function generateSvg(string $url): string
    {
        $options = new QROptions([
            'outputType' => 'svg',
            'outputBase64' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($url);
    }

    public function download(Site $site): Response
    {
        Gate::authorize('view', $site);

        $url = route('public.site', $site->slug);
        $svg = self::generateSvg($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$site->slug}-qr.svg\"",
        ]);
    }

    public function poster(Site $site): View
    {
        Gate::authorize('view', $site);

        $url = route('public.site', $site->slug);
        $svg = self::generateSvg($url);

        return view('dashboard.qr-poster', [
            'site' => $site,
            'publicUrl' => $url,
            'qrSvg' => $svg,
        ]);
    }
}
