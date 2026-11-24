<?php

namespace App\Services;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Enums\SourceStatus;
use App\Models\Fact;
use App\Models\Site;
use App\Models\Source;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FactExtractor
{
    public const ALLOWED_KEYS = [
        'business_name',
        'category',
        'phone',
        'email',
        'address',
        'hours',
        'website',
        'social_link',
        'service',
        'about_text',
        'year_founded',
        'other',
    ];

    public function __construct(
        protected ?SafetyGuard $safetyGuard = null,
        protected ?AiService $aiService = null,
    ) {
        $this->safetyGuard ??= new SafetyGuard();
        $this->aiService ??= new AiService();
    }

    public function extractForSite(Site $site): int
    {
        $sources = $site->sources()
            ->where('status', SourceStatus::Read)
            ->get();

        $createdCount = 0;

        foreach ($sources as $source) {
            $createdCount += $this->extractFromSource($site, $source);
        }

        return $createdCount;
    }

    public function extractFromSource(Site $site, Source $source): int
    {
        $rawText = trim((string) $source->raw_text);
        if ($rawText === '') {
            return 0;
        }

        $sanitizedText = $this->safetyGuard->sanitize($source);
        if (trim($sanitizedText) === '') {
            return 0;
        }

        $candidates = $this->isCleanPriceTable($sanitizedText)
            ? $this->parsePriceTable($sanitizedText)
            : $this->extractWithGemini($source, $sanitizedText);

        $createdCount = 0;

        foreach ($candidates as $candidate) {
            if (! $this->snippetExistsInSource($candidate['snippet'], $sanitizedText)) {
                continue;
            }

            if ($this->isDuplicateFact($site, $candidate['key'], $candidate['value'])) {
                continue;
            }

            $site->facts()->create([
                'source_id' => $source->id,
                'key' => $candidate['key'],
                'value' => $candidate['value'],
                'snippet' => $candidate['snippet'],
                'status' => FactStatus::Candidate,
                'confidence' => $candidate['confidence'],
            ]);

            $createdCount++;
        }

        return $createdCount;
    }

    protected function isCleanPriceTable(string $rawText): bool
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $rawText)),
            fn (string $line) => $line !== '' && ! str_starts_with($line, 'Sheet:')
        ));

        if (count($lines) < 2) {
            return false;
        }

        $pipeLines = array_filter($lines, function (string $line): bool {
            $cols = explode(' | ', $line);

            return count($cols) >= 2 && count($cols) <= 4;
        });

        return count($pipeLines) === count($lines);
    }

    /**
     * @return list<array{key: string, value: string, snippet: string, confidence: FactConfidence}>
     */
    protected function parsePriceTable(string $rawText): array
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $rawText)),
            fn (string $line) => $line !== '' && ! str_starts_with($line, 'Sheet:')
        ));

        $facts = [];
        $headerWords = ['service', 'item', 'name', 'product', 'description', 'menu', 'price', 'rate', 'cost', 'amount', 'fee'];

        foreach ($lines as $index => $line) {
            $cols = array_values(array_filter(
                array_map('trim', explode(' | ', $line)),
                fn (string $c) => $c !== ''
            ));

            if (count($cols) < 2) {
                continue;
            }

            if ($index === 0) {
                $firstLower = strtolower($cols[0]);
                $secondLower = strtolower($cols[1]);
                if (in_array($firstLower, $headerWords, true) || in_array($secondLower, $headerWords, true)) {
                    continue;
                }
            }

            $value = implode(' - ', $cols);

            $facts[] = [
                'key' => 'service',
                'value' => $value,
                'snippet' => $line,
                'confidence' => FactConfidence::High,
            ];
        }

        return $facts;
    }

    /**
     * @return list<array{key: string, value: string, snippet: string, confidence: FactConfidence}>
     */
    protected function extractWithGemini(Source $source, string $rawText): array
    {
        if (! $this->aiService->isAnyConfigured()) {
            return [];
        }

        $promptTemplate = file_get_contents(resource_path('prompts/extract_facts.txt'));
        $prompt = str_replace('{{DATA}}', $rawText, (string) $promptTemplate);

        try {
            $decoded = $this->aiService->generateJson($prompt);

            if (! is_array($decoded) || ! isset($decoded['facts']) || ! is_array($decoded['facts'])) {
                return [];
            }

            $validated = [];
            foreach ($decoded['facts'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $key = trim((string) ($item['key'] ?? ''));
                $value = trim((string) ($item['value'] ?? ''));
                $snippet = trim((string) ($item['snippet'] ?? ''));
                $confidenceRaw = strtolower(trim((string) ($item['confidence'] ?? '')));

                if (! in_array($key, self::ALLOWED_KEYS, true) || $value === '' || $snippet === '') {
                    continue;
                }

                $confidence = FactConfidence::tryFrom($confidenceRaw);
                if ($confidence === null) {
                    continue;
                }

                $validated[] = [
                    'key' => $key,
                    'value' => $value,
                    'snippet' => $snippet,
                    'confidence' => $confidence,
                ];
            }

            return $validated;
        } catch (Throwable $e) {
            Log::warning('FactExtractor AI exception', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function snippetExistsInSource(string $snippet, string $rawText): bool
    {
        $normalizedSnippet = $this->normalizeForMatch($snippet);
        $normalizedSource = $this->normalizeForMatch($rawText);

        if ($normalizedSnippet === '' || $normalizedSource === '') {
            return false;
        }

        return str_contains($normalizedSource, $normalizedSnippet);
    }

    protected function isDuplicateFact(Site $site, string $key, string $value): bool
    {
        $normalizedValue = $this->normalizeForMatch($value);

        return Fact::query()
            ->where('site_id', $site->id)
            ->where('key', $key)
            ->get(['value'])
            ->contains(fn (Fact $fact) => $this->normalizeForMatch($fact->value) === $normalizedValue);
    }

    private function normalizeForMatch(string $text): string
    {
        return strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
    }
}
