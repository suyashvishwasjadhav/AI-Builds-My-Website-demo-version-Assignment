<?php

namespace Tests\Feature;

use App\Enums\SectionCreatedBy;
use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InlineEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_inline_update_section_and_record_version(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'slug' => 'inline-edit-site',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        $hero = Section::create([
            'site_id' => $site->id,
            'type' => SectionType::Hero,
            'content' => [
                'headline' => 'Old Headline',
                'subheadline' => 'Old Subheadline',
            ],
            'is_visible' => true,
        ]);

        // Preview includes inline editor attributes
        $this->actingAs($owner)
            ->get(route('sites.preview', $site))
            ->assertOk()
            ->assertSee('data-inline-field="headline"', false);

        // Public site does NOT include inline editor attributes
        $this->get(route('public.site', $site->slug))
            ->assertOk()
            ->assertDontSee('data-inline-field', false);

        $response = $this->actingAs($owner)
            ->patchJson(route('sites.sections.inline', [$site, $hero]), [
                'field' => 'headline',
                'value' => 'Updated Inline Headline',
            ]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'field' => 'headline',
        ]);

        $hero->refresh();
        $this->assertSame('Updated Inline Headline', $hero->content['headline']);
        $this->assertSame('Old Subheadline', $hero->content['subheadline']);
        $this->assertDatabaseHas('section_versions', [
            'section_id' => $hero->id,
            'created_by' => SectionCreatedBy::User->value,
            'note' => 'Inline edited by owner',
        ]);
    }

    public function test_non_owner_cannot_inline_update_section(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $site = Site::factory()->for($owner)->create();
        $hero = Section::create([
            'site_id' => $site->id,
            'type' => SectionType::Hero,
            'content' => ['headline' => 'Original'],
            'is_visible' => true,
        ]);

        $this->actingAs($other)
            ->patchJson(route('sites.sections.inline', [$site, $hero]), [
                'field' => 'headline',
                'value' => 'Hacked',
            ])
            ->assertForbidden();
    }
}
