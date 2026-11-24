<?php

namespace App\Http\Controllers;

use App\Enums\SourceStatus;
use App\Jobs\ReadSourceJob;
use App\Models\Site;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SourceController extends Controller
{
    public function index(Site $site): View
    {
        Gate::authorize('view', $site);

        $sources = $site->sources()->latest()->get();

        return view('dashboard.sources', [
            'site' => $site,
            'sources' => $sources,
        ]);
    }

    public function retry(Site $site, Source $source): RedirectResponse
    {
        Gate::authorize('update', $site);

        abort_unless($source->site_id === $site->id, 404);

        $source->update([
            'status' => SourceStatus::Pending,
            'error_message' => null,
        ]);

        ReadSourceJob::dispatch($source);

        return redirect()
            ->route('sites.sources', $site)
            ->with('status', 'Source queued to be read again.');
    }
}
