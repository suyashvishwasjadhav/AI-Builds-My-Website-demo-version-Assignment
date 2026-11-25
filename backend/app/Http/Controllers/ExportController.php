<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\StaticExporter;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function zip(Site $site, StaticExporter $exporter): BinaryFileResponse
    {
        Gate::authorize('view', $site);

        $zipPath = $exporter->exportZip($site);

        return response()
            ->download($zipPath, "{$site->slug}-website.zip")
            ->deleteFileAfterSend(true);
    }
}
