<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\SitePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PreviewController extends Controller
{
    public function show(Site $site, SitePresenter $presenter): View
    {
        Gate::authorize('view', $site);

        $data = $presenter->buildViewData($site, isPreview: true);

        return view('templates.'.$data['template'], $data);
    }

    public function updateTemplate(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'template' => ['required', 'string', Rule::in(SitePresenter::TEMPLATES)],
        ]);

        $site->update(['template' => $validated['template']]);

        return redirect()->route('sites.preview', $site);
    }
}
