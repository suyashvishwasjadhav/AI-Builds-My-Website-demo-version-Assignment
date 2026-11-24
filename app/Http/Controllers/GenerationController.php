<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSiteJob;
use App\Models\Site;
use App\Services\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class GenerationController extends Controller
{
    public function generate(Site $site, Writer $writer): RedirectResponse
    {
        Gate::authorize('update', $site);

        if (! $writer->canGenerate($site)) {
            return redirect()
                ->route('sites.show', $site)
                ->with('error', 'Please resolve open business selection or conflict questions before generating your website.');
        }

        Cache::put(
            GenerateSiteJob::cacheKey($site->id),
            ['stage' => 'Reading…', 'state' => 'running', 'error' => null],
            now()->addMinutes(15)
        );

        GenerateSiteJob::dispatch($site);

        return redirect()
            ->route('sites.show', $site)
            ->with('status', 'Website generation started.');
    }

    public function fallback(Site $site, Writer $writer): RedirectResponse
    {
        Gate::authorize('update', $site);

        if (! $writer->canGenerate($site)) {
            return redirect()
                ->route('sites.show', $site)
                ->with('error', 'Please resolve open business selection or conflict questions first.');
        }

        $writer->buildBasicFallback($site);

        Cache::forget(GenerateSiteJob::cacheKey($site->id));

        return redirect()
            ->route('sites.show', $site)
            ->with('status', 'Basic website built from your confirmed facts.');
    }

    public function status(Site $site): JsonResponse
    {
        Gate::authorize('view', $site);

        $status = Cache::get(GenerateSiteJob::cacheKey($site->id), [
            'stage' => null,
            'state' => 'idle',
            'error' => null,
        ]);

        return response()->json($status);
    }
}
