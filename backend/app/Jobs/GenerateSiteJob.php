<?php

namespace App\Jobs;

use App\Enums\SourceStatus;
use App\Models\Site;
use App\Services\FactExtractor;
use App\Services\Reader;
use App\Services\Verifier;
use App\Services\Writer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateSiteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Site $site)
    {
    }

    public static function cacheKey(int $siteId): string
    {
        return "site_generation_status_{$siteId}";
    }

    public function handle(Reader $reader, FactExtractor $extractor, Verifier $verifier, Writer $writer): void
    {
        $site = $this->site->fresh();
        $key = self::cacheKey($site->id);

        try {
            Cache::put($key, ['stage' => 'Reading…', 'state' => 'running', 'error' => null], now()->addMinutes(15));
            foreach ($site->sources()->where('status', SourceStatus::Pending)->get() as $source) {
                $reader->read($source);
            }

            Cache::put($key, ['stage' => 'Checking…', 'state' => 'running', 'error' => null], now()->addMinutes(15));
            $extractor->extractForSite($site);
            $verifier->verify($site);

            if (! $writer->canGenerate($site)) {
                Cache::put($key, [
                    'stage' => 'Needs your attention',
                    'state' => 'blocked',
                    'error' => 'Please resolve the open questions before generating your website.',
                ], now()->addMinutes(15));

                return;
            }

            Cache::put($key, ['stage' => 'Writing…', 'state' => 'running', 'error' => null], now()->addMinutes(15));
            $ok = $writer->generate($site);

            if ($ok) {
                Cache::put($key, ['stage' => 'Done', 'state' => 'done', 'error' => null], now()->addMinutes(15));
            } else {
                Cache::put($key, [
                    'stage' => 'Failed',
                    'state' => 'failed',
                    'error' => 'The AI writer is temporarily unavailable. You can retry or build a basic site without AI.',
                ], now()->addMinutes(15));
            }
        } catch (Throwable $e) {
            Cache::put($key, [
                'stage' => 'Failed',
                'state' => 'failed',
                'error' => 'Something went wrong while generating your site. You can retry or build a basic site without AI.',
            ], now()->addMinutes(15));
        }
    }
}
