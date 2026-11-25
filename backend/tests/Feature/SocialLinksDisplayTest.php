<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinksDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_site_renders_verified_social_media_links(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'slug' => 'social-biz',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => 'contact',
            'content' => ['heading' => 'Contact', 'intro' => 'Follow us'],
            'is_visible' => true,
        ]);

        $site->sources()->create([
            'type' => SourceType::Link,
            'label' => 'Instagram',
            'path_or_url' => 'https://instagram.com/oecmumbai',
            'status' => SourceStatus::Read,
        ]);

        $site->sources()->create([
            'type' => SourceType::Link,
            'label' => 'LinkedIn',
            'path_or_url' => 'https://linkedin.com/company/oec',
            'status' => SourceStatus::Read,
        ]);

        $response = $this->get(route('public.site', $site->slug));

        $response->assertOk();
        $response->assertSee('https://instagram.com/oecmumbai');
        $response->assertSee('https://linkedin.com/company/oec');
        $response->assertSee('Instagram');
        $response->assertSee('LinkedIn');
    }
}
