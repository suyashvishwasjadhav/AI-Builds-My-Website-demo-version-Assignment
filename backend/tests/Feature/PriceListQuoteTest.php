<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PriceListQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_site_with_optional_price_list_and_render_inquire_buttons(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('sites.store'), [
                'business_name' => 'OEC Piping',
                'city' => 'Mumbai',
                'price_items' => [
                    ['name' => 'Piping Stress Analysis', 'price' => '₹25,000', 'unit' => 'per project'],
                    ['name' => '', 'price' => '', 'unit' => ''],
                ],
            ])
            ->assertRedirect();

        $site = Site::firstOrFail();
        $site->update(['status' => SiteStatus::Published, 'published_at' => now()]);

        $this->assertDatabaseHas('facts', [
            'site_id' => $site->id,
            'key' => 'price_item',
        ]);

        $response = $this->get(route('public.site', $site->slug));
        $response->assertOk();
        $response->assertSee('Services & Pricing');
        $response->assertSee('Piping Stress Analysis');
        $response->assertSee('₹25,000');
        $response->assertSee('Inquire');
    }

    public function test_site_without_price_items_omits_pricing_section(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('sites.store'), [
                'business_name' => 'Sunrise Bakery',
                'city' => 'Pune',
            ])
            ->assertRedirect();

        $site = Site::firstOrFail();
        $site->update(['status' => SiteStatus::Published, 'published_at' => now()]);

        $response = $this->get(route('public.site', $site->slug));
        $response->assertOk();
        $response->assertDontSee('Services & Pricing');
    }
}
