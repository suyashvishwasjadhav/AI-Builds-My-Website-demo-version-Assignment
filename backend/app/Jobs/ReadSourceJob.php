<?php

namespace App\Jobs;

use App\Models\Source;
use App\Services\Reader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReadSourceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public Source $source)
    {
    }

    public function handle(Reader $reader): void
    {
        $reader->read($this->source);
    }
}
