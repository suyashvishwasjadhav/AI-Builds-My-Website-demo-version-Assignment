<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Site $site): View
    {
        Gate::authorize('view', $site);

        $media = $site->media()
            ->where('is_placeholder', false)
            ->latest('id')
            ->get();

        return view('dashboard.media', [
            'site' => $site,
            'media' => $media,
        ]);
    }

    public function store(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'set_as_hero' => ['nullable', 'boolean'],
        ]);

        $setAsHero = $request->boolean('set_as_hero');
        $hasExistingHero = $site->media()->where('is_hero', true)->exists();

        if ($setAsHero) {
            $site->media()->update(['is_hero' => false]);
        }

        foreach ($request->file('photos', []) as $index => $file) {
            $path = $file->store("sites/{$site->id}/media", 'public');
            $shouldBeHero = $setAsHero ? ($index === 0) : (! $hasExistingHero && $index === 0);

            $site->media()->create([
                'path' => $path,
                'alt_text' => trim((string) ($validated['alt_text'] ?? '')) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'is_hero' => $shouldBeHero,
                'is_placeholder' => false,
            ]);
        }

        $route = $request->input('redirect_to') === 'studio' ? 'sites.studio' : 'sites.media';

        return redirect()
            ->route($route, $site)
            ->with('status', 'Images uploaded successfully.');
    }

    public function setHero(Request $request, Site $site, Media $media): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($media->site_id === $site->id, 404);

        $site->media()->update(['is_hero' => false]);
        $media->update(['is_hero' => true]);

        $route = $request->input('redirect_to') === 'studio' ? 'sites.studio' : 'sites.media';

        return redirect()
            ->route($route, $site)
            ->with('status', 'Header background image updated.');
    }

    public function destroy(Request $request, Site $site, Media $media): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($media->site_id === $site->id, 404);

        if ($media->path && Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        $media->delete();

        $route = $request->input('redirect_to') === 'studio' ? 'sites.studio' : 'sites.media';

        return redirect()
            ->route($route, $site)
            ->with('status', 'Image deleted.');
    }
}
