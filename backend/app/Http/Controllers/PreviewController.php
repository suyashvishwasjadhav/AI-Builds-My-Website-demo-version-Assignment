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

        $data = $presenter->buildViewData($site, isPreview: true, locale: request()->query('lang'));

        return view('templates.'.$data['template'], $data);
    }

    public function studio(Site $site): View
    {
        Gate::authorize('view', $site);

        $site->load(['sections', 'translations']);
        $order = ['hero' => 1, 'about' => 2, 'services' => 3, 'contact' => 4];
        $site->setRelation(
            'sections',
            $site->sections->sortBy(fn ($s) => $order[$s->type->value] ?? 99)->values()
        );

        return view('dashboard.preview', [
            'site' => $site,
            'templates' => SitePresenter::TEMPLATES,
        ]);
    }

    public function updateTemplate(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'template' => ['required', 'string', Rule::in(SitePresenter::TEMPLATES)],
        ]);

        $site->update(['template' => $validated['template']]);

        if ($request->input('redirect_to') === 'studio') {
            return redirect()->route('sites.studio', $site);
        }

        return redirect()->route('sites.preview', $site);
    }

    public function updateThemeConfig(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'accent_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_pairing' => ['required', 'string', Rule::in(['modern', 'classic', 'warm'])],
        ]);

        $site->update([
            'theme_config' => [
                'accent_color' => $validated['accent_color'],
                'font_pairing' => $validated['font_pairing'],
            ],
        ]);

        return redirect()->route('sites.studio', $site)->with('status', 'Brand palette & typography updated.');
    }
}
