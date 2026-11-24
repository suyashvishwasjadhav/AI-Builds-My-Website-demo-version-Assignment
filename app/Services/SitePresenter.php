<?php

namespace App\Services;

use App\Enums\FactStatus;
use App\Models\Site;

class SitePresenter
{
    public const TEMPLATES = ['clean', 'bold', 'warm'];

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(Site $site, bool $isPreview = false): array
    {
        $sections = $site->sections()
            ->where('is_visible', true)
            ->get()
            ->keyBy(fn ($section) => $section->type->value);

        $confirmedFacts = $site->facts()
            ->whereIn('status', [FactStatus::Confirmed, FactStatus::UserProvided])
            ->get();

        $factsByKey = [];
        foreach ($confirmedFacts as $fact) {
            $factsByKey[$fact->key][] = $fact->value;
        }

        $photos = $site->media()
            ->where('is_placeholder', false)
            ->get();

        $hero = $sections->get('hero');
        $heroContent = $hero?->content ?? [];

        $seoTitle = trim((string) ($heroContent['seo_title'] ?? '')) ?: "{$site->business_name} — {$site->city}";
        $seoDescription = trim((string) ($heroContent['seo_description'] ?? '')) ?: '';

        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $factsByKey['business_name'][0] ?? $site->business_name,
            'telephone' => $factsByKey['phone'][0] ?? null,
            'email' => $factsByKey['email'][0] ?? null,
            'address' => $factsByKey['address'][0] ?? null,
            'openingHours' => $factsByKey['hours'][0] ?? null,
            'url' => $factsByKey['website'][0] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $template = in_array($site->template, self::TEMPLATES, true)
            ? $site->template
            : 'clean';

        return [
            'site' => $site,
            'template' => $template,
            'isPreview' => $isPreview,
            'sections' => $sections,
            'facts' => $factsByKey,
            'photos' => $photos,
            'showPreviewPlaceholder' => $isPreview && $photos->isEmpty(),
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'jsonLd' => $jsonLd,
        ];
    }
}
