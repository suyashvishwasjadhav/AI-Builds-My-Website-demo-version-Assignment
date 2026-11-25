<?php

namespace Tests\Feature;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Models\Fact;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapEmbedTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_section_renders_map_embed_and_directions_using_verified_address(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'business_name' => 'Oceanic Engineers',
            'city' => 'Mumbai',
            'slug' => 'oec-map',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => SectionType::Contact,
            'content' => ['heading' => 'Contact Us', 'intro' => 'Visit us', 'show_map' => true],
            'is_visible' => true,
        ]);

        Fact::create([
            'site_id' => $site->id,
            'key' => 'address',
            'value' => 'Dadar West, Mumbai 400028',
            'status' => FactStatus::Confirmed,
            'confidence' => FactConfidence::High,
        ]);

        $response = $this->get(route('public.site', $site->slug));

        $response->assertOk();
        $response->assertSee('https://maps.google.com/maps?q=Dadar%20West%2C%20Mumbai%20400028');
        $response->assertSee('https://www.google.com/maps/search/?api=1&query=Dadar%20West%2C%20Mumbai%20400028');
        $response->assertSee('Get Directions');
    }

    public function test_owner_can_hide_map_from_contact_section_editor(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'business_name' => 'Oceanic Engineers',
            'city' => 'Mumbai',
            'slug' => 'oec-no-map',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        $contact = Section::create([
            'site_id' => $site->id,
            'type' => SectionType::Contact,
            'content' => ['heading' => 'Contact Us', 'intro' => 'Visit us', 'show_map' => true],
            'is_visible' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('sites.sections.update', [$site, $contact]), [
                'heading' => 'Contact Us',
                'intro' => 'Visit us',
            ])
            ->assertRedirect();

        $this->get(route('public.site', $site->slug))
            ->assertOk()
            ->assertDontSee('Get Directions');
    }
}
