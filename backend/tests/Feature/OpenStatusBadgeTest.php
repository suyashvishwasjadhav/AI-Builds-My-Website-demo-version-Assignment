<?php

namespace Tests\Feature;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Enums\SiteStatus;
use App\Models\Fact;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenStatusBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_renders_open_status_badge_when_hours_fact_confirmed(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'slug' => 'open-bakery',
            'status' => SiteStatus::Published,
            'published_at' => now(),
            'timezone' => 'Asia/Kolkata',
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => 'hero',
            'content' => ['headline' => 'Fresh Bread Daily', 'subheadline' => 'Pune'],
            'is_visible' => true,
        ]);

        Fact::create([
            'site_id' => $site->id,
            'key' => 'hours',
            'value' => 'Open 24 hours',
            'status' => FactStatus::Confirmed,
            'confidence' => FactConfidence::High,
        ]);

        $response = $this->get(route('public.site', $site->slug));

        $response->assertOk();
        $response->assertSee('Open 24 Hours');
    }

    public function test_public_site_omits_open_status_badge_without_hours(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'slug' => 'no-hours-biz',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => 'hero',
            'content' => ['headline' => 'No Hours Site', 'subheadline' => 'Mumbai'],
            'is_visible' => true,
        ]);

        $response = $this->get(route('public.site', $site->slug));

        $response->assertOk();
        $response->assertDontSee('Open Now');
        $response->assertDontSee('Open 24 Hours');
    }
}
