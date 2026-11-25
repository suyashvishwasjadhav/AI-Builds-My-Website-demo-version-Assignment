<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DomainController extends Controller
{
    public function update(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $raw = strtolower(trim((string) $request->input('custom_domain', '')));
        $raw = preg_replace('#^https?://#', '', $raw) ?? $raw;
        $raw = rtrim($raw, '/');

        $request->merge(['custom_domain' => $raw !== '' ? $raw : null]);

        $validated = $request->validate([
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/',
                Rule::unique('sites', 'custom_domain')->ignore($site->id),
            ],
        ], [
            'custom_domain.regex' => 'Enter a valid domain such as www.mybusiness.com.',
        ]);

        $site->update([
            'custom_domain' => $validated['custom_domain'],
            'domain_verified_at' => null,
        ]);

        return back()->with('status', $validated['custom_domain'] ? 'Custom domain saved. Run DNS verification once your CNAME record is live.' : 'Custom domain removed.');
    }

    public function verify(Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        if (! $site->custom_domain) {
            return back()->with('error', 'Save a custom domain before verifying DNS.');
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
        $records = @dns_get_record($site->custom_domain, DNS_CNAME | DNS_A) ?: [];

        $matched = false;
        foreach ($records as $record) {
            $target = strtolower((string) ($record['target'] ?? ''));
            if ($target !== '' && ($target === strtolower($host) || str_ends_with($target, '.' . strtolower($host)))) {
                $matched = true;
                break;
            }
        }

        if ($matched) {
            $site->update(['domain_verified_at' => now()]);

            return back()->with('status', "DNS verified for {$site->custom_domain}!");
        }

        return back()->with('error', "No matching CNAME record pointing to {$host} found yet for {$site->custom_domain}. DNS propagation can take a few minutes.");
    }
}
