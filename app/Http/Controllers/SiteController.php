<?php

namespace App\Http\Controllers;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
        ]);

        $site = $request->user()->sites()->create([
            'business_name' => $validated['business_name'],
            'city' => $validated['city'],
            'slug' => $this->generateUniqueSlug($validated['business_name']),
            'status' => SiteStatus::Draft,
            'template' => 'clean',
            'language' => 'en',
        ]);

        return redirect()->route('sites.show', $site);
    }

    public function show(Site $site): View
    {
        Gate::authorize('view', $site);

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
}
