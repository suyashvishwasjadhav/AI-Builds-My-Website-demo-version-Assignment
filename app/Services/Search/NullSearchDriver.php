<?php

namespace App\Services\Search;

use App\Services\Contracts\SearchDriver;

class NullSearchDriver implements SearchDriver
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function search(string $businessName, string $city): array
    {
        return [];
    }
}
