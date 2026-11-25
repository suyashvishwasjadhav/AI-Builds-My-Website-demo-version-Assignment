<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Models\Enquiry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phases27To31Test extends TestCase
{
    use RefreshDatabase;

    public function test_phase_27_brand_palette_and_font_pairing_update(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'slug' => 'brand-site',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patch(route('sites.theme_config.update', $site), [
                'accent_color' => '#059669',
                'font_pairing' => 'classic',
            ])
            ->assertRedirect(route('sites.studio', $site));

        $this->get(route('public.site', $site->slug))
            ->assertOk()
            ->assertSee('--brand-accent: #059669', false)
            ->assertSee('Georgia, Cambria', false);
    }

    public function test_phase_28_dynamic_og_image_and_favicon_svg(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'business_name' => 'Oceanic Engineers',
            'city' => 'Mumbai',
            'slug' => 'oec-cards',
        ]);

        $og = $this->get(route('public.site.og', $site->slug));
        $og->assertOk();
        $this->assertStringContainsString('image/svg+xml', (string) $og->headers->get('Content-Type'));
        $this->assertStringContainsString('Oceanic Engineers', $og->getContent());

        $fav = $this->get(route('public.site.favicon', $site->slug));
        $fav->assertOk();
        $this->assertStringContainsString('image/svg+xml', (string) $fav->headers->get('Content-Type'));
        $this->assertStringContainsString('>O</text>', $fav->getContent());
    }

    public function test_phase_29_lead_inbox_status_pipeline_and_csv_export(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create(['slug' => 'leads-site']);
        $enquiry = Enquiry::create([
            'site_id' => $site->id,
            'name' => 'Rahul Sharma',
            'email' => 'rahul@example.com',
            'phone' => '9820012345',
            'message' => 'Need piping quote',
            'status' => 'new',
        ]);

        $this->actingAs($owner)
            ->patch(route('sites.enquiries.status', [$site, $enquiry]), ['status' => 'contacted'])
            ->assertRedirect();

        $this->assertSame('contacted', $enquiry->fresh()->status);

        $csv = $this->actingAs($owner)->get(route('sites.enquiries.export', $site));
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Rahul Sharma', $content);
        $this->assertStringContainsString('contacted', $content);
    }

    public function test_phase_30_sitemap_and_robots_for_published_and_draft_sites(): void
    {
        $owner = User::factory()->create();
        $published = Site::factory()->for($owner)->create([
            'slug' => 'live-seo',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);
        $draft = Site::factory()->for($owner)->create([
            'slug' => 'draft-seo',
            'status' => SiteStatus::Draft,
        ]);

        $this->get(route('public.site.sitemap', $published->slug))
            ->assertOk()
            ->assertSee('/s/live-seo');
        $this->get(route('public.site.robots', $published->slug))
            ->assertOk()
            ->assertSee('Allow: /');

        $this->get(route('public.site.sitemap', $draft->slug))->assertNotFound();
        $this->get(route('public.site.robots', $draft->slug))
            ->assertOk()
            ->assertSee('Disallow: /');
    }

    public function test_phase_31_custom_domain_binding_and_validation(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $this->actingAs($owner)
            ->patch(route('sites.domain.update', $site), [
                'custom_domain' => 'https://WWW.OceanicEngineers.com/',
            ])
            ->assertRedirect();

        $this->assertSame('www.oceanicengineers.com', $site->fresh()->custom_domain);
    }
}
