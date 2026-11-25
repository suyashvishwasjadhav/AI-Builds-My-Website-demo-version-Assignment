<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Models\Media;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_images_and_set_header_background(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'slug' => 'media-test',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => 'hero',
            'content' => ['headline' => 'Media Hero'],
            'is_visible' => true,
        ]);

        $file1 = UploadedFile::fake()->image('hero.jpg', 1200, 600);
        $file2 = UploadedFile::fake()->image('gallery.png', 800, 600);

        $this->actingAs($owner)
            ->post(route('sites.media.store', $site), [
                'photos' => [$file1, $file2],
                'set_as_hero' => '1',
                'alt_text' => 'Workshop photo',
            ])
            ->assertRedirect(route('sites.media', $site));

        $this->assertSame(2, $site->media()->count());
        $first = $site->media()->orderBy('id')->firstOrFail();
        $second = $site->media()->orderByDesc('id')->firstOrFail();

        $this->assertTrue($first->is_hero);
        $this->assertFalse($second->is_hero);

        // Public site renders hero background and gallery photos
        $this->get(route('public.site', $site->slug))
            ->assertOk()
            ->assertSee($first->path)
            ->assertSee($second->path);

        // Switch header background to second image
        $this->actingAs($owner)
            ->patch(route('sites.media.hero', [$site, $second]))
            ->assertRedirect(route('sites.media', $site));

        $this->assertFalse($first->fresh()->is_hero);
        $this->assertTrue($second->fresh()->is_hero);

        // Delete first image
        $this->actingAs($owner)
            ->delete(route('sites.media.destroy', [$site, $first]))
            ->assertRedirect(route('sites.media', $site));

        $this->assertSame(1, $site->media()->count());
    }
}
