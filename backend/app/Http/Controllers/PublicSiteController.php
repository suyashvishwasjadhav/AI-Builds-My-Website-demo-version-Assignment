<?php

namespace App\Http\Controllers;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Services\SitePresenter;
use Illuminate\Http\Response;

class PublicSiteController extends Controller
{
    public function show(string $slug, SitePresenter $presenter): Response
    {
        $site = Site::where('slug', $slug)->first();

        if (! $site || $site->status !== SiteStatus::Published) {
            return response()->view('public.not-found', [], 404);
        }

        $data = $presenter->buildViewData($site, isPreview: false, locale: request()->query('lang'));

        return response()
            ->view('templates.'.$data['template'], $data)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
