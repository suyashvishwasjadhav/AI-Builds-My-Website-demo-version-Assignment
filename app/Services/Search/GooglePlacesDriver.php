<?php

namespace App\Services\Search;

use App\Services\Contracts\SearchDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GooglePlacesDriver implements SearchDriver
{
    public function __construct(
        private readonly string $apiKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function search(string $businessName, string $city): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Goog-Api-Key' => $this->apiKey,
                    'X-Goog-FieldMask' => 'places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.websiteUri,places.googleMapsUri',
                ])
                ->post('https://places.googleapis.com/v1/places:searchText', [
                    'textQuery' => trim("{$businessName} {$city}"),
                    'maxResultCount' => 5,
                ]);

            if (! $response->successful()) {
                Log::warning('Google Places search failed', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $places = $response->json('places', []);
            if (! is_array($places)) {
                return [];
            }

            $candidates = [];
            foreach ($places as $place) {
                if (! is_array($place)) {
                    continue;
                }

                $name = trim((string) ($place['displayName']['text'] ?? ''));
                $sourceUrl = (string) ($place['googleMapsUri'] ?? $place['websiteUri'] ?? '');

                if ($name === '' || $sourceUrl === '') {
                    continue;
                }

                $candidates[] = [
                    'name' => $name,
                    'address' => $place['formattedAddress'] ?? null,
                    'phone' => $place['nationalPhoneNumber'] ?? null,
                    'website' => $place['websiteUri'] ?? null,
                    'source_url' => $sourceUrl,
                ];
            }

            return $candidates;
        } catch (Throwable $e) {
            Log::warning('Google Places search exception', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
