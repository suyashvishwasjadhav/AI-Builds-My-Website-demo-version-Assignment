<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\Translator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TranslationController extends Controller
{
    public function store(Request $request, Site $site, Translator $translator): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(Translator::SUPPORTED_LOCALES))],
        ]);

        $locale = $validated['locale'];
        $result = $translator->translate($site, $locale);

        if ($result === null) {
            return back()->with('error', 'Could not translate the site right now. Make sure sections are generated and AI is available.');
        }

        $label = Translator::SUPPORTED_LOCALES[$locale];

        return back()->with('status', "Website translated into {$label}!");
    }
}
