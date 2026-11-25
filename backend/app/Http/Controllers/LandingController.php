<?php

namespace App\Http\Controllers;

use App\Enums\FactStatus;
use App\Enums\SiteStatus;
use App\Models\Fact;
use App\Models\Section;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(Request $request): View
    {
        $bridge = $this->buildBridgePayload($request);

        return view('landing', [
            'bridge' => $bridge,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->buildBridgePayload($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBridgePayload(Request $request): array
    {
        $user = $request->user();
        $hasTables = Schema::hasTable('sites') && Schema::hasTable('facts') && Schema::hasTable('sections');

        $publishedSites = $hasTables
            ? Site::query()
                ->where('status', SiteStatus::Published)
                ->whereNotNull('published_at')
                ->with(['sections' => fn ($q) => $q->where('type', 'hero')])
                ->latest('published_at')
                ->take(6)
                ->get()
                ->map(function (Site $site): array {
                    $hero = $site->sections->first();
                    $content = is_array($hero?->content) ? $hero->content : [];

                    return [
                        'id' => $site->id,
                        'business_name' => $site->business_name,
                        'city' => $site->city,
                        'slug' => $site->slug,
                        'template' => $site->template->value ?? (string) $site->template,
                        'url' => route('public.site', $site->slug),
                        'headline' => $content['headline'] ?? $site->business_name,
                        'subheadline' => $content['subheadline'] ?? "Verified local business in {$site->city}",
                        'published_at' => $site->published_at?->diffForHumans(),
                    ];
                })
                ->values()
                ->all()
            : [];

        return [
            'authenticated' => $user !== null,
            'user' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'csrfToken' => csrf_token(),
            'routes' => [
                'login' => route('login'),
                'register' => route('register'),
                'dashboard' => route('dashboard'),
                'createSite' => route('sites.create'),
                'apiLandingData' => route('api.landing.data'),
            ],
            'stats' => $hasTables ? [
                'published_sites' => Site::where('status', SiteStatus::Published)->count(),
                'total_sites' => Site::count(),
                'verified_facts' => Fact::whereIn('status', [FactStatus::Confirmed, FactStatus::UserProvided])->count(),
                'sections_built' => Section::count(),
            ] : [
                'published_sites' => 0,
                'total_sites' => 0,
                'verified_facts' => 0,
                'sections_built' => 0,
            ],
            'publishedSites' => $publishedSites,
        ];
    }
}
