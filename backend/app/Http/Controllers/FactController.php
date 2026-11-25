<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FactController extends Controller
{
    public function index(Site $site): View
    {
        Gate::authorize('view', $site);

        $facts = $site->facts()
            ->with('source')
            ->orderBy('key')
            ->latest()
            ->get();

        return view('dashboard.facts', [
            'site' => $site,
            'facts' => $facts,
        ]);
    }
}
