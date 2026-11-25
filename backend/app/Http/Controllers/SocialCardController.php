<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Response;

class SocialCardController extends Controller
{
    public function ogImage(string $slug): Response
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $accent = $site->theme_config['accent_color'] ?? '#4f46e5';
        $name = htmlspecialchars($site->business_name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $city = htmlspecialchars($site->city, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <rect width="1200" height="630" fill="#0f172a"/>
  <rect x="0" y="0" width="1200" height="14" fill="{$accent}"/>
  <circle cx="1060" cy="140" r="180" fill="{$accent}" fill-opacity="0.16"/>
  <text x="80" y="250" font-family="system-ui, -apple-system, sans-serif" font-size="62" font-weight="800" fill="#ffffff">{$name}</text>
  <text x="80" y="330" font-family="system-ui, -apple-system, sans-serif" font-size="34" font-weight="500" fill="#94a3b8">{$city}</text>
  <rect x="80" y="420" width="290" height="54" rx="27" fill="{$accent}"/>
  <text x="225" y="456" text-anchor="middle" font-family="system-ui, -apple-system, sans-serif" font-size="22" font-weight="700" fill="#ffffff">Verified Business Website</text>
</svg>
SVG;

        return response($svg, 200, ['Content-Type' => 'image/svg+xml; charset=utf-8']);
    }

    public function favicon(string $slug): Response
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $accent = $site->theme_config['accent_color'] ?? '#4f46e5';
        $initial = mb_strtoupper(mb_substr(trim($site->business_name) ?: 'S', 0, 1));
        $safeInitial = htmlspecialchars($initial, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">
  <rect width="64" height="64" rx="16" fill="{$accent}"/>
  <text x="32" y="44" text-anchor="middle" font-family="system-ui, -apple-system, sans-serif" font-size="34" font-weight="800" fill="#ffffff">{$safeInitial}</text>
</svg>
SVG;

        return response($svg, 200, ['Content-Type' => 'image/svg+xml; charset=utf-8']);
    }
}
