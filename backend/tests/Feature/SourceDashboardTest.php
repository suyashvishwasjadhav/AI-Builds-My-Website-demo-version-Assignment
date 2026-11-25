<?php

namespace Tests\Feature;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Jobs\ReadSourceJob;
use App\Models\Site;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SourceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_intake_dispatches_read_source_job_for_uploaded_files(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $csv = UploadedFile::fake()->create('prices.csv', 10, 'text/csv');

        $this->actingAs($user)->post('/sites', [
            'business_name' => 'Test Studio',
            'city' => 'Pune',
            'files' => [$csv],
        ])->assertRedirect();

        Queue::assertPushed(ReadSourceJob::class, 1);
    }

    public function test_owner_can_view_sources_and_retry_failed_source(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        $source = Source::factory()->for($site)->create([
            'type' => SourceType::File,
            'label' => 'menu.pdf',
            'status' => SourceStatus::Failed,
            'error_message' => 'We could not read this PDF file.',
        ]);

        $this->actingAs($user)
            ->get(route('sites.sources', $site))
            ->assertOk()
            ->assertSee('menu.pdf')
            ->assertSee('failed')
            ->assertSee('We could not read this PDF file.')
            ->assertSee('Retry');

        $this->actingAs($user)
            ->post(route('sites.sources.retry', [$site, $source]))
            ->assertRedirect(route('sites.sources', $site));

        $source->refresh();
        $this->assertSame(SourceStatus::Pending, $source->status);
        $this->assertNull($source->error_message);
        Queue::assertPushed(ReadSourceJob::class, 1);
    }

    public function test_non_owner_cannot_retry_another_users_source(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();
        $sourceB = Source::factory()->for($siteB)->create([
            'status' => SourceStatus::Failed,
        ]);

        $this->actingAs($userA)
            ->post(route('sites.sources.retry', [$siteB, $sourceB]))
            ->assertForbidden();
    }
}
