<?php

namespace Tests\Feature;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_intake_creates_site_pending_sources_and_stores_files_with_random_names(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $pdf = UploadedFile::fake()->create('menu.pdf', 200, 'application/pdf');
        $csv = UploadedFile::fake()->create('prices.csv', 50, 'text/csv');
        $image = UploadedFile::fake()->image('storefront.png');

        $response = $this->actingAs($user)->post('/sites', [
            'business_name' => 'Green Leaf Cafe',
            'city' => 'Bengaluru',
            'google_business_url' => 'https://maps.google.com/?cid=12345',
            'social_links' => [
                'https://instagram.com/greenleaf',
                'https://facebook.com/greenleaf',
                '',
            ],
            'files' => [$pdf, $csv, $image],
            'notes' => 'Open 8am to 9pm daily.',
        ]);

        $site = Site::where('business_name', 'Green Leaf Cafe')->firstOrFail();
        $response->assertRedirect(route('sites.show', $site));

        $this->assertSame('green-leaf-cafe', $site->slug);
        $this->assertCount(7, $site->sources);

        $linkSources = $site->sources()->where('type', SourceType::Link)->get();
        $this->assertCount(3, $linkSources);
        $this->assertTrue($linkSources->every(fn ($s) => $s->status === SourceStatus::Pending));

        $fileSources = $site->sources()->where('type', SourceType::File)->get();
        $this->assertCount(3, $fileSources);
        foreach ($fileSources as $fileSource) {
            $this->assertSame(SourceStatus::Pending, $fileSource->status);
            Storage::disk('local')->assertExists($fileSource->path_or_url);
            $this->assertStringStartsWith("uploads/{$site->id}/", $fileSource->path_or_url);
            $this->assertNotEquals($fileSource->label, basename($fileSource->path_or_url));
        }

        $userSource = $site->sources()->where('type', SourceType::User)->firstOrFail();
        $this->assertSame('Open 8am to 9pm daily.', $userSource->raw_text);
        $this->assertSame(SourceStatus::Read, $userSource->status);
    }

    public function test_intake_rejects_bad_file_types_and_bad_urls(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $badFile = UploadedFile::fake()->create('payload.php', 10, 'application/x-php');

        $response = $this->actingAs($user)->post('/sites', [
            'business_name' => 'Test Shop',
            'city' => 'Delhi',
            'google_business_url' => 'javascript:alert(1)',
            'social_links' => ['ftp://example.com/file', 'not-a-url'],
            'files' => [$badFile],
        ]);

        $response->assertSessionHasErrors([
            'google_business_url',
            'social_links.0',
            'social_links.1',
            'files.0',
        ]);

        $this->assertDatabaseCount('sites', 0);
    }

    public function test_two_sites_with_same_business_name_get_different_slugs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/sites', [
            'business_name' => 'Royal Tailor',
            'city' => 'Mumbai',
        ])->assertRedirect();

        $this->actingAs($user)->post('/sites', [
            'business_name' => 'Royal Tailor',
            'city' => 'Pune',
        ])->assertRedirect();

        $slugs = Site::pluck('slug')->all();
        $this->assertCount(2, $slugs);
        $this->assertCount(2, array_unique($slugs));
        $this->assertSame('royal-tailor', $slugs[0]);
        $this->assertStringStartsWith('royal-tailor-', $slugs[1]);
    }
}
