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
    public function buildViewData(Site $site, bool $isPreview = false, ?string $locale = null): array
    {
        $sections = $site->sections()
            ->where('is_visible', true)
            ->get()
            ->keyBy(fn ($section) => $section->type->value);

        $translations = $site->translations()->get()->keyBy('locale');
        $baseLocale = $site->language ?: 'en';
        $availableLocales = [$baseLocale => Translator::NATIVE_LABELS[$baseLocale] ?? 'English'];
        foreach ($translations as $code => $translation) {
            $availableLocales[$code] = Translator::NATIVE_LABELS[$code] ?? Translator::SUPPORTED_LOCALES[$code] ?? strtoupper($code);
        }

        $activeLocale = $baseLocale;
        if ($locale !== null && $locale !== $baseLocale && $translations->has($locale)) {
            $activeLocale = $locale;
            $translatedSections = $translations->get($locale)->sections ?? [];
            foreach ($sections as $typeKey => $section) {
                if (isset($translatedSections[$typeKey]) && is_array($translatedSections[$typeKey])) {
                    $cloned = clone $section;
                    $cloned->content = array_merge($section->content ?? [], $translatedSections[$typeKey]);
                    $sections->put($typeKey, $cloned);
                }
            }
        }

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

        $defaultHeroImage = $this->resolveCuratedHeroImage($site, $factsByKey);

        $hero = $sections->get('hero');
        $heroContent = $hero?->content ?? [];

        $seoTitle = trim((string) ($heroContent['seo_title'] ?? '')) ?: "{$site->business_name} — {$site->city}";
        $seoDescription = trim((string) ($heroContent['seo_description'] ?? '')) ?: '';

        $sourceLinks = $site->sources()
            ->where('type', \App\Enums\SourceType::Link)
            ->get()
            ->filter(fn ($s) => is_string($s->path_or_url) && str_starts_with($s->path_or_url, 'http'));

        $sameAs = $sourceLinks->pluck('path_or_url')->values()->all();

        $socialLinks = $sourceLinks->map(function ($source): array {
            $url = (string) $source->path_or_url;
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));

            $platform = match (true) {
                str_contains($host, 'instagram.com') => 'instagram',
                str_contains($host, 'facebook.com'), str_contains($host, 'fb.com') => 'facebook',
                str_contains($host, 'linkedin.com') => 'linkedin',
                str_contains($host, 'youtube.com'), str_contains($host, 'youtu.be') => 'youtube',
                str_contains($host, 'x.com'), str_contains($host, 'twitter.com') => 'x',
                str_contains($host, 'maps.google'), str_contains($host, 'goo.gl') => 'google',
                default => 'website',
            };

            $label = $source->label ?: match ($platform) {
                'instagram' => 'Instagram',
                'facebook' => 'Facebook',
                'linkedin' => 'LinkedIn',
                'youtube' => 'YouTube',
                'x' => 'X (Twitter)',
                'google' => 'Google Maps',
                default => 'Website',
            };

            return [
                'platform' => $platform,
                'label' => $label,
                'url' => $url,
            ];
        })->values()->all();

        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $factsByKey['business_name'][0] ?? $site->business_name,
            'telephone' => $factsByKey['phone'][0] ?? null,
            'email' => $factsByKey['email'][0] ?? null,
            'address' => $factsByKey['address'][0] ?? null,
            'openingHours' => $factsByKey['hours'][0] ?? null,
            'url' => $factsByKey['website'][0] ?? null,
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ], fn ($v) => $v !== null && $v !== '');

        $template = in_array($site->template, self::TEMPLATES, true)
            ? $site->template
            : 'clean';

        $whatsappUrl = $this->buildWhatsappUrl($site, $factsByKey, $sections);
        $openStatus = app(BusinessHoursParser::class)->evaluate(
            $factsByKey['hours'][0] ?? null,
            $site->timezone ?: 'Asia/Kolkata'
        );

        $contactContent = $sections->get('contact')?->content ?? [];
        $showMap = (bool) ($contactContent['show_map'] ?? true);
        $mapQuery = trim((string) ($factsByKey['address'][0] ?? ''))
            ?: trim("{$site->business_name}, {$site->city}", ', ');
        $mapEmbedUrl = ($showMap && $mapQuery !== '')
            ? 'https://maps.google.com/maps?q=' . rawurlencode($mapQuery) . '&t=&z=15&ie=UTF8&iwloc=&output=embed'
            : null;
        $mapDirectionsUrl = ($showMap && $mapQuery !== '')
            ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapQuery)
            : null;

        $priceItems = [];
        foreach ($factsByKey['price_item'] ?? [] as $rawItem) {
            $decoded = json_decode((string) $rawItem, true);
            if (is_array($decoded) && ! empty($decoded['name']) && ! empty($decoded['price'])) {
                $priceItems[] = [
                    'name' => (string) $decoded['name'],
                    'price' => (string) $decoded['price'],
                    'unit' => (string) ($decoded['unit'] ?? ''),
                ];
            }
        }

        return [
            'site' => $site,
            'template' => $template,
            'isPreview' => $isPreview,
            'sections' => $sections,
            'facts' => $factsByKey,
            'photos' => $photos,
            'defaultHeroImage' => $defaultHeroImage,
            'showPreviewPlaceholder' => $isPreview && $photos->isEmpty(),
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'jsonLd' => $jsonLd,
            'whatsappUrl' => $whatsappUrl,
            'openStatus' => $openStatus,
            'mapEmbedUrl' => $mapEmbedUrl,
            'mapDirectionsUrl' => $mapDirectionsUrl,
            'priceItems' => $priceItems,
            'availableLocales' => $availableLocales,
            'activeLocale' => $activeLocale,
            'accentColor' => $site->theme_config['accent_color'] ?? '#4f46e5',
            'fontPairing' => $site->theme_config['font_pairing'] ?? 'modern',
            'socialLinks' => $socialLinks,
        ];
    }

    /**
     * @param array<string, array<int, string>> $factsByKey
     * @param \Illuminate\Support\Collection<string, \App\Models\Section> $sections
     */
    private function buildWhatsappUrl(Site $site, array $factsByKey, $sections): ?string
    {
        if (! ($site->whatsapp_enabled ?? true)) {
            return null;
        }

        $rawPhone = $factsByKey['whatsapp'][0] ?? $factsByKey['phone'][0] ?? null;
        if ($rawPhone === null) {
            return null;
        }

        $digits = (string) preg_replace('/\D+/', '', $rawPhone);
        if (strlen($digits) < 7) {
            return null;
        }

        $businessName = $factsByKey['business_name'][0] ?? $site->business_name;
        $servicesItems = $sections->get('services')?->content['items'] ?? [];
        $primaryService = is_array($servicesItems) && isset($servicesItems[0]['name'])
            ? (string) $servicesItems[0]['name']
            : ($factsByKey['service'][0] ?? null);

        $customGreeting = trim((string) ($site->whatsapp_greeting ?? ''));
        $greeting = $customGreeting !== ''
            ? $customGreeting
            : "Hi {$businessName}, I saw your website and would like to enquire" . ($primaryService ? " about {$primaryService}" : '') . '.';

        return "https://wa.me/{$digits}?text=" . rawurlencode($greeting);
    }

    /**
     * Curated, royalty-free high-res stock photo matching business category
     *
     * @param array<string, array<int, string>> $factsByKey
     */
    private function resolveCuratedHeroImage(Site $site, array $factsByKey): string
    {
        $haystack = strtolower(implode(' ', [
            $site->business_name,
            implode(' ', $factsByKey['service'] ?? []),
            implode(' ', $factsByKey['product'] ?? []),
            implode(' ', $factsByKey['category'] ?? []),
        ]));

        return match (true) {
            str_contains($haystack, 'coffee') || str_contains($haystack, 'cafe') || str_contains($haystack, 'roaster') =>
                'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1600&q=80',
            str_contains($haystack, 'bakery') || str_contains($haystack, 'bread') || str_contains($haystack, 'cake') =>
                'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1600&q=80',
            str_contains($haystack, 'dental') || str_contains($haystack, 'dentist') || str_contains($haystack, 'clinic') || str_contains($haystack, 'doctor') =>
                'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&w=1600&q=80',
            str_contains($haystack, 'gym') || str_contains($haystack, 'fitness') || str_contains($haystack, 'crossfit') =>
                'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1600&q=80',
            str_contains($haystack, 'car') || str_contains($haystack, 'auto') || str_contains($haystack, 'garage') || str_contains($haystack, 'repair') =>
                'https://images.unsplash.com/photo-1486006920555-c77dce18193b?auto=format&fit=crop&w=1600&q=80',
            str_contains($haystack, 'restaurant') || str_contains($haystack, 'food') || str_contains($haystack, 'dining') =>
                'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1600&q=80',
            default =>
                'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1600&q=80', // modern studio/workspace
        };
    }
}
