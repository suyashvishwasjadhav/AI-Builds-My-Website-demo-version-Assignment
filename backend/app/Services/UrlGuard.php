<?php

namespace App\Services;

class UrlGuard
{
    /**
     * Validate that a URL is safe to fetch (http/https only, no private/reserved IPs).
     * Returns null if safe, or a friendly error message if blocked.
     */
    public function validate(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return 'Please enter a valid http or https URL.';
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return 'Only http and https URLs are allowed.';
        }

        $host = strtolower(trim($parts['host'], '[]'));

        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return 'Private or local network URLs are not allowed.';
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            if (! $this->isPublicIp($host)) {
                return 'Private or internal IP addresses are not allowed.';
            }

            return null;
        }

        $resolvedIps = @gethostbynamel($host);
        if (is_array($resolvedIps)) {
            foreach ($resolvedIps as $ip) {
                if (! $this->isPublicIp($ip)) {
                    return 'This domain resolves to a private or internal IP address.';
                }
            }
        }

        return null;
    }

    public function isAllowed(string $url): bool
    {
        return $this->validate($url) === null;
    }

    private function isPublicIp(string $ip): bool
    {
        if ($ip === '0.0.0.0' || $ip === '::') {
            return false;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
