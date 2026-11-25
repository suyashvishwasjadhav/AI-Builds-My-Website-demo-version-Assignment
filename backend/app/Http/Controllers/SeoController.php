<?php

namespace App\Http\Controllers;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(string $slug): Response
    {
        $site = Site::where('slug', $slug)->first();

        if (! $site || $site->status !== SiteStatus::Published) {
            return response('Not Found', 404);
        }

        $url = htmlspecialchars(route('public.site', $site->slug), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $lastmod = ($site->updated_at ?? now())->toAtomString();

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{$url}</loc>
    <lastmod>{$lastmod}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
XML;

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    public function robots(string $slug): Response
    {
        $site = Site::where('slug', $slug)->first();

        if (! $site || $site->status !== SiteStatus::Published) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $sitemapUrl = route('public.site.sitemap', $site->slug);

        return response("User-agent: *\nAllow: /\nSitemap: {$sitemapUrl}\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
