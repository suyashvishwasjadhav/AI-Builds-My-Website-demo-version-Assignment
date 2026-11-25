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

class WhatsappWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_site_with_confirmed_phone_renders_whatsapp_widget(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'business_name' => 'Oceanic Engineers',
            'slug' => 'oceanic-engineers',
            'status' => SiteStatus::Published,
            'published_at' => now(),
            'whatsapp_enabled' => true,
        ]);

        Fact::create([
            'site_id' => $site->id,
            'key' => 'phone',
            'value' => '+91 8454 098748',
            'status' => FactStatus::Confirmed,
            'confidence' => FactConfidence::High,
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => 'services',
            'content' => ['heading' => 'Services', 'items' => [['name' => 'Piping Design', 'description' => 'Industrial piping']]],
            'is_visible' => true,
        ]);

        $response = $this->get(route('public.site', $site->slug));

        $response->assertOk();
        $response->assertSee('https://wa.me/918454098748?text=', false);
        $response->assertSee('Chat on WhatsApp');
    }

    public function test_published_site_without_phone_hides_whatsapp_widget(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'slug' => 'no-phone-biz',
            'status' => SiteStatus::Published,
            'published_at' => now(),
            'whatsapp_enabled' => true,
        ]);

        $response = $this->get(route('public.site', $site->slug));

        $response->assertOk();
        $response->assertDontSee('https://wa.me/', false);
    }

    public function test_owner_can_update_whatsapp_settings_and_disable_widget(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'slug' => 'custom-wa',
            'status' => SiteStatus::Published,
            'published_at' => now(),
            'whatsapp_enabled' => true,
        ]);

        Fact::create([
            'site_id' => $site->id,
            'key' => 'phone',
            'value' => '+91 98765 43210',
            'status' => FactStatus::Confirmed,
            'confidence' => FactConfidence::High,
        ]);

        $this->actingAs($user)
            ->patch(route('sites.whatsapp.update', $site), [
                'whatsapp_enabled' => '1',
                'whatsapp_greeting' => 'Hello OEC team!',
            ])
            ->assertRedirect();

        $this->get(route('public.site', $site->slug))
            ->assertOk()
            ->assertSee('https://wa.me/919876543210?text=Hello%20OEC%20team%21', false);

        $this->actingAs($user)
            ->patch(route('sites.whatsapp.update', $site), [
                'whatsapp_greeting' => 'Hello OEC team!',
            ])
            ->assertRedirect();

        $this->get(route('public.site', $site->slug))
            ->assertOk()
            ->assertDontSee('https://wa.me/', false);
    }
}
