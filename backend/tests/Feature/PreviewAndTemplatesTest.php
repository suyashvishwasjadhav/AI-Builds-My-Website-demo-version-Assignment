<?php

namespace Tests\Feature;

use App\Enums\FactStatus;
use App\Enums\SectionType;
use App\Models\Fact;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use App\Services\SitePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewAndTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_theme_changes_template_without_regenerating_text(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'business_name' => 'Sunrise Bakery',
            'city' => 'Pune',
            'template' => 'clean',
        ]);

        Section::factory()->for($site)->create([
            'type' => SectionType::Hero,
            'content' => [
                'headline' => 'Fresh Sourdough Every Morning',
                'subheadline' => 'Baked daily in Pune',
                'seo_title' => 'Sunrise Bakery - Pune',
                'seo_description' => 'Fresh bread in Pune.',
            ],
        ]);
        Section::factory()->for($site)->create([
            'type' => SectionType::About,
            'content' => ['heading' => 'About Us', 'body' => 'Family bakery since 2015.'],
        ]);
        Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '9820012345',
            'status' => FactStatus::Confirmed,
        ]);

        // Preview clean
        $this->actingAs($user)
            ->get(route('sites.preview', $site))
            ->assertOk()
            ->assertSee('Fresh Sourdough Every Morning')
            ->assertSee('Family bakery since 2015.')
            ->assertSee('9820012345')
            ->assertSee('noindex', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('LocalBusiness', false);

        // Switch theme to bold
        $this->actingAs($user)
            ->patch(route('sites.template.update', $site), ['template' => 'bold'])
            ->assertRedirect(route('sites.preview', $site));

        $this->assertSame('bold', $site->fresh()->template);

        // Preview bold keeps exact same text
        $this->actingAs($user)
            ->get(route('sites.preview', $site))
            ->assertOk()
            ->assertSee('Fresh Sourdough Every Morning')
            ->assertSee('Family bakery since 2015.')
            ->assertSee('bg-slate-950');

        // Switch theme to warm
        $this->actingAs($user)
            ->patch(route('sites.template.update', $site), ['template' => 'warm'])
            ->assertRedirect(route('sites.preview', $site));

        $this->actingAs($user)
            ->get(route('sites.preview', $site))
            ->assertOk()
            ->assertSee('Fresh Sourdough Every Morning')
            ->assertSee('bg-amber-50/40');
    }

    public function test_site_with_no_photos_and_no_hours_shows_no_empty_boxes_on_public_render(): void
    {
        $site = Site::factory()->create([
            'business_name' => 'Minimal Biz',
            'city' => 'Delhi',
            'template' => 'clean',
        ]);
        Section::factory()->for($site)->create([
            'type' => SectionType::Hero,
            'content' => ['headline' => 'Minimal Biz', 'subheadline' => 'Delhi'],
        ]);
        Section::factory()->for($site)->create([
            'type' => SectionType::Contact,
            'content' => ['heading' => 'Contact', 'intro' => 'Reach out anytime.'],
        ]);

        $data = app(SitePresenter::class)->buildViewData($site, isPreview: false);
        $html = view('templates.clean', $data)->render();

        $this->assertStringNotContainsString('Opening Hours', $html);
        $this->assertStringNotContainsString('id="gallery"', $html);
        $this->assertStringNotContainsString('Placeholder', $html);
        $this->assertStringNotContainsString('id="services"', $html);
    }

    public function test_non_owner_cannot_preview_or_change_template(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();

        $this->actingAs($userA)->get(route('sites.preview', $siteB))->assertForbidden();
        $this->actingAs($userA)->patch(route('sites.template.update', $siteB), ['template' => 'bold'])->assertForbidden();
    }
}
