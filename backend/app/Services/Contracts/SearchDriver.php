<?php

namespace App\Services\Contracts;

interface SearchDriver
{
    public function isConfigured(): bool;

    /**
     * Search for businesses matching the given name and city.
     *
     * @return list<array{
     *     name: string,
     *     address: ?string,
     *     phone: ?string,
     *     website: ?string,
     *     source_url: string
     * }>
     */
    public function search(string $businessName, string $city): array;
}
