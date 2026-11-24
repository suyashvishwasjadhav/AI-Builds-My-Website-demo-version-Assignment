<?php

namespace App\Http\Controllers;

use App\Enums\SiteStatus;
use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Http\Requests\StoreSiteRequest;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(Request $request): View
    {
        $sites = $request->user()
            ->sites()
            ->latest()
            ->get();

        return view('dashboard.index', [
            'sites' => $sites,
        ]);
    }

    public function create(): View
    {
        return view('dashboard.create');
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $site = DB::transaction(function () use ($request, $validated): Site {
            $site = $request->user()->sites()->create([
                'business_name' => $validated['business_name'],
                'city' => $validated['city'],
                'slug' => $this->generateUniqueSlug($validated['business_name']),
                'status' => SiteStatus::Draft,
                'template' => 'clean',
                'language' => 'en',
            ]);

            if (! empty($validated['google_business_url'])) {
                $site->sources()->create([
                    'type' => SourceType::Link,
                    'label' => 'Google Business',
                    'path_or_url' => $validated['google_business_url'],
                    'status' => SourceStatus::Pending,
                ]);
            }

            foreach ($validated['social_links'] ?? [] as $link) {
                $site->sources()->create([
                    'type' => SourceType::Link,
                    'label' => $this->inferLinkLabel($link),
                    'path_or_url' => $link,
                    'status' => SourceStatus::Pending,
                ]);
            }

            foreach ($request->file('files', []) as $file) {
                $storedPath = $file->store("uploads/{$site->id}", 'local');

                $site->sources()->create([
                    'type' => SourceType::File,
                    'label' => $file->getClientOriginalName(),
                    'path_or_url' => $storedPath,
                    'status' => SourceStatus::Pending,
                ]);
            }

            if (! empty($validated['notes'])) {
                $site->sources()->create([
                    'type' => SourceType::User,
                    'label' => 'Owner notes',
                    'path_or_url' => null,
                    'status' => SourceStatus::Read,
                    'raw_text' => $validated['notes'],
                ]);
            }

            return $site;
        });

        return redirect()->route('sites.show', $site);
    }

    public function show(Site $site): View
    {
        Gate::authorize('view', $site);

        $site->loadCount('sources');

        return view('dashboard.overview', [
            'site' => $site,
        ]);
    }

    public function tab(Site $site, string $tab): View
    {
        Gate::authorize('view', $site);

        $titles = [
            'sources' => 'Sources',
            'facts' => 'Facts',
            'sections' => 'Sections',
            'media' => 'Media',
            'enquiries' => 'Enquiries',
            'settings' => 'Settings',
        ];

        abort_unless(array_key_exists($tab, $titles), 404);

        return view('dashboard.section-placeholder', [
            'site' => $site,
            'activeTab' => $tab,
            'title' => $titles[$tab],
        ]);
    }

    private function generateUniqueSlug(string $businessName): string
    {
        $base = Str::slug($businessName) ?: 'site';
        $slug = $base;

        while (Site::where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    private function inferLinkLabel(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return match (true) {
            str_contains($host, 'facebook.com'), str_contains($host, 'fb.com') => 'Facebook',
            str_contains($host, 'instagram.com') => 'Instagram',
            default => $host !== '' ? $host : 'Website link',
        };
    }
}
