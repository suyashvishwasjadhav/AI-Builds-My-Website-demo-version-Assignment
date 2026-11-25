<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SiteTranslation;
use Illuminate\Support\Facades\Log;
use Throwable;

class Translator
{
    /**
     * @var array<string, string>
     */
    public const SUPPORTED_LOCALES = [
        'hi' => 'Hindi',
        'mr' => 'Marathi',
        'gu' => 'Gujarati',
        'ta' => 'Tamil',
        'es' => 'Spanish',
        'fr' => 'French',
    ];

    /**
     * Native script labels for public language switcher pills.
     *
     * @var array<string, string>
     */
    public const NATIVE_LABELS = [
        'en' => 'English',
        'hi' => 'हिन्दी',
        'mr' => 'मराठी',
        'gu' => 'ગુજરાતી',
        'ta' => 'தமிழ்',
        'es' => 'Español',
        'fr' => 'Français',
    ];

    public function __construct(
        protected ?AiService $aiService = null,
    ) {
        $this->aiService ??= new AiService();
    }

    public function translate(Site $site, string $locale): ?SiteTranslation
    {
        if (! isset(self::SUPPORTED_LOCALES[$locale])) {
            return null;
        }

        $sectionsPayload = [];
        foreach ($site->sections()->where('is_visible', true)->get() as $section) {
            $sectionsPayload[$section->type->value] = $section->content ?? [];
        }

        if ($sectionsPayload === [] || ! $this->aiService->isAnyConfigured()) {
            return null;
        }

        $template = (string) file_get_contents(resource_path('prompts/translate_site.txt'));
        $prompt = str_replace(
            ['{{TARGET_LANGUAGE}}', '{{DATA}}'],
            [self::SUPPORTED_LOCALES[$locale], json_encode($sectionsPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
            $template
        );

        try {
            $translated = $this->aiService->generateJson($prompt, 45);
        } catch (Throwable $e) {
            Log::warning('Site translation failed', ['site_id' => $site->id, 'locale' => $locale, 'error' => $e->getMessage()]);

            return null;
        }

        if (! is_array($translated) || $translated === []) {
            return null;
        }

        return SiteTranslation::updateOrCreate(
            ['site_id' => $site->id, 'locale' => $locale],
            ['sections' => $translated]
        );
    }
}
