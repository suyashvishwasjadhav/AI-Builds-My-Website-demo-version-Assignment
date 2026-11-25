<?php

namespace App\Http\Controllers;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Enums\QuestionStatus;
use App\Enums\SiteStatus;
use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Http\Requests\StoreSiteRequest;
use App\Jobs\GenerateSiteJob;
use App\Jobs\ReadSourceJob;
use App\Models\Site;
use App\Services\Publisher;
use App\Services\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
                $source = $site->sources()->create([
                    'type' => SourceType::Link,
                    'label' => 'Google Business',
                    'path_or_url' => $validated['google_business_url'],
                    'status' => SourceStatus::Pending,
                ]);

                ReadSourceJob::dispatch($source);
            }

            foreach ($validated['social_links'] ?? [] as $link) {
                $source = $site->sources()->create([
                    'type' => SourceType::Link,
                    'label' => $this->inferLinkLabel($link),
                    'path_or_url' => $link,
                    'status' => SourceStatus::Pending,
                ]);

                ReadSourceJob::dispatch($source);
            }

            foreach ($request->file('files', []) as $file) {
                $storedPath = $file->store("uploads/{$site->id}", 'local');

                $source = $site->sources()->create([
                    'type' => SourceType::File,
                    'label' => $file->getClientOriginalName(),
                    'path_or_url' => $storedPath,
                    'status' => SourceStatus::Pending,
                ]);

                ReadSourceJob::dispatch($source);
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

            foreach ($validated['price_items'] ?? [] as $item) {
                $site->facts()->create([
                    'key' => 'price_item',
                    'value' => json_encode([
                        'name' => trim((string) $item['name']),
                        'price' => trim((string) $item['price']),
                        'unit' => trim((string) ($item['unit'] ?? '')),
                    ], JSON_UNESCAPED_UNICODE),
                    'status' => FactStatus::UserProvided,
                    'confidence' => FactConfidence::High,
                ]);
            }

            return $site;
        });

        return redirect()->route('sites.show', $site);
    }

    public function show(Site $site, Writer $writer, Publisher $publisher): View
    {
        Gate::authorize('view', $site);

        $site->loadCount(['sources', 'sections']);
        $openQuestions = $site->questions()
            ->where('status', QuestionStatus::Open)
            ->latest()
            ->get();

        $generationStatus = Cache::get(GenerateSiteJob::cacheKey($site->id), [
            'stage' => null,
            'state' => 'idle',
            'error' => null,
        ]);

        return view('dashboard.overview', [
            'site' => $site,
            'openQuestions' => $openQuestions,
            'canGenerate' => $writer->canGenerate($site),
            'generationStatus' => $generationStatus,
            'publishBlockReason' => $publisher->publishBlockReason($site),
        ]);
    }

    public function tab(Site $site, string $tab): View
    {
        Gate::authorize('view', $site);

        if ($tab === 'settings') {
            return view('dashboard.settings', [
                'site' => $site,
            ]);
        }

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

    public function updateSettings(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $site->update($validated);

        return redirect()
            ->route('sites.tab', [$site, 'settings'])
            ->with('status', 'Site settings saved.');
    }

    public function destroy(Site $site): RedirectResponse
    {
        Gate::authorize('delete', $site);

        $site->delete();

        return redirect()
            ->route('sites.index')
            ->with('status', 'Website deleted.');
    }

    public function updateWhatsapp(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_greeting' => ['nullable', 'string', 'max:255'],
        ]);

        $site->update([
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'whatsapp_greeting' => trim((string) ($validated['whatsapp_greeting'] ?? '')) ?: null,
        ]);

        return back()->with('status', 'WhatsApp widget settings saved.');
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
