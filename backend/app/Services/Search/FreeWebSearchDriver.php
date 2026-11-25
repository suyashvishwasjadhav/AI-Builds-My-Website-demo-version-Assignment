<?php

namespace App\Services\Search;

use App\Services\Contracts\SearchDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FreeWebSearchDriver implements SearchDriver
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function search(string $businessName, string $city): array
    {
        $query = trim("{$businessName} {$city}");
        if ($query === '') {
            return [];
        }

        $candidates = $this->searchNominatim($query, $businessName);

        if (count($candidates) < 3) {
            $ddg = $this->searchDuckDuckGo($query, $businessName);
            foreach ($ddg as $item) {
                if (count($candidates) >= 5) {
                    break;
                }
                $candidates[] = $item;
            }
        }

        return array_slice($candidates, 0, 5);
    }

    /**
     * @return list<array{name: string, address: ?string, phone: ?string, website: ?string, source_url: string}>
     */
    private function searchNominatim(string $query, string $fallbackName): array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'AIBuildsMyWebsite/1.0 (https://aibuildsmyweb.duckdns.org)'])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'extratags' => 1,
                    'limit' => 5,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $items = $response->json();
            if (! is_array($items)) {
                return [];
            }

            $candidates = [];
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $osmType = (string) ($item['osm_type'] ?? 'node');
                $osmId = (string) ($item['osm_id'] ?? '');
                $sourceUrl = $osmId !== ''
                    ? "https://www.openstreetmap.org/{$osmType}/{$osmId}"
                    : 'https://www.openstreetmap.org/';

                $extra = is_array($item['extratags'] ?? null) ? $item['extratags'] : [];
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    $name = trim(explode(',', (string) ($item['display_name'] ?? $fallbackName))[0]);
                }

                $candidates[] = [
                    'name' => $name !== '' ? $name : $fallbackName,
                    'address' => ! empty($item['display_name']) ? (string) $item['display_name'] : null,
                    'phone' => $extra['phone'] ?? $extra['contact:phone'] ?? null,
                    'website' => $extra['website'] ?? $extra['contact:website'] ?? null,
                    'source_url' => $sourceUrl,
                ];
            }

            return $candidates;
        } catch (Throwable $e) {
            Log::warning('Free Nominatim search failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<array{name: string, address: ?string, phone: ?string, website: ?string, source_url: string}>
     */
    private function searchDuckDuckGo(string $query, string $fallbackName): array
    {
        try {
            $response = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'AIBuildsMyWebsite/1.0'])
                ->get('https://api.duckduckgo.com/', [
                    'q' => $query,
                    'format' => 'json',
                    'no_html' => 1,
                    'skip_disambig' => 1,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            if (! is_array($data)) {
                return [];
            }

            $candidates = [];
            if (! empty($data['AbstractURL'])) {
                $candidates[] = [
                    'name' => trim((string) ($data['Heading'] ?? $fallbackName)) ?: $fallbackName,
                    'address' => null,
                    'phone' => null,
                    'website' => (string) $data['AbstractURL'],
                    'source_url' => (string) $data['AbstractURL'],
                ];
            }

            foreach ((array) ($data['Results'] ?? []) as $res) {
                if (is_array($res) && ! empty($res['FirstURL'])) {
                    $candidates[] = [
                        'name' => trim((string) ($res['Text'] ?? $fallbackName)) ?: $fallbackName,
                        'address' => null,
                        'phone' => null,
                        'website' => (string) $res['FirstURL'],
                        'source_url' => (string) $res['FirstURL'],
                    ];
                }
            }

            return $candidates;
        } catch (Throwable $e) {
            Log::warning('Free DuckDuckGo search failed', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
