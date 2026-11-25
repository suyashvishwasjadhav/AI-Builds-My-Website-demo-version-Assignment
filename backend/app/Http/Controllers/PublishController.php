<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\Publisher;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PublishController extends Controller
{
    public function publish(Site $site, Publisher $publisher): RedirectResponse
    {
        Gate::authorize('update', $site);

        try {
            $publisher->publish($site);
        } catch (DomainException $e) {
            return redirect()
                ->route('sites.show', $site)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sites.show', $site)
            ->with('status', 'Your website is now live!');
    }

    public function unpublish(Site $site, Publisher $publisher): RedirectResponse
    {
        Gate::authorize('update', $site);

        $publisher->unpublish($site);

        return redirect()
            ->route('sites.show', $site)
            ->with('status', 'Website unpublished.');
    }

    public function updateSlug(Request $request, Site $site, Publisher $publisher): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:sites,slug,'.$site->id,
            ],
        ], [
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This public URL slug is already taken.',
        ]);

        $publisher->updateSlug($site, $validated['slug']);

        return redirect()
            ->route('sites.show', $site)
            ->with('status', 'Public URL slug updated.');
    }
}
