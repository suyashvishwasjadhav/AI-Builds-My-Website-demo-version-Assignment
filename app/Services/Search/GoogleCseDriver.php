<?php

namespace App\Services\Search;

use App\Services\Contracts\SearchDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCseDriver implements SearchDriver
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $engineId,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->engineId !== '';
    }

    public function search(string $businessName, string $city): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout(10)->get('https://www.googleapis.com/customsearch/v1', [
                'key' => $this->apiKey,
                'cx' => $this->engineId,
                'q' => trim("{$businessName} {$city}"),
                'num' => 5,
            ]);

            if (! $response->successful()) {
                Log::warning('Google CSE search failed', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $items = $response->json('items', []);
            if (! is_array($items)) {
                return [];
            }

            $candidates = [];
            foreach ($items as $item) {
                if (! is_array($item) || empty($item['link'])) {
                    continue;
                }

                $snippet = (string) ($item['snippet'] ?? '');
                $candidates[] = [
                    'name' => trim((string) ($item['title'] ?? $businessName)),
                    'address' => $item['pagemap']['postaladdress'][0]['streetaddress'] ?? null,
                    'phone' => $this->extractPhone($snippet),
                    'website' => (string) $item['link'],
                    'source_url' => (string) $item['link'],
                ];
            }

            return $candidates;
        } catch (Throwable $e) {
            Log::warning('Google CSE search exception', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function extractPhone(string $text): ?string
    {
        if (preg_match('/(\+?\d[\d\s\-()]{7,}\d)/', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
