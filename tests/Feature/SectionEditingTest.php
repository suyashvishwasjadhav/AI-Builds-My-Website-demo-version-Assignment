<?php

namespace Tests\Feature;

use App\Enums\FactStatus;
use App\Enums\SectionCreatedBy;
use App\Enums\SectionType;
use App\Models\Fact;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SectionEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_section_saves_user_version_and_updates_content(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        $section = Section::factory()->for($site)->create([
            'type' => SectionType::About,
            'content' => ['heading' => 'About Us', 'body' => 'Old body'],
        ]);

        $this->actingAs($user)
            ->patch(route('sites.sections.update', [$site, $section]), [
                'heading' => 'Our Story',
                'body' => 'Hand-edited story.',
            ])
            ->assertRedirect(route('sites.sections', $site));

        $section->refresh();
        $this->assertSame('Our Story', $section->content['heading']);
        $this->assertSame('Hand-edited story.', $section->content['body']);
        $this->assertSame(SectionCreatedBy::User, $section->currentVersion->created_by);
    }

    public function test_regenerating_about_changes_only_about_section(): void
    {
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        Fact::factory()->for($site)->create([
            'key' => 'city',
            'value' => 'Pune',
            'status' => FactStatus::Confirmed,
        ]);

        $hero = Section::factory()->for($site)->create([
            'type' => SectionType::Hero,
            'content' => ['headline' => 'Original Hero', 'subheadline' => 'Original Sub'],
        ]);
        $about = Section::factory()->for($site)->create([
            'type' => SectionType::About,
            'content' => ['heading' => 'About Us', 'body' => 'Long original about text.'],
        ]);

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'heading' => 'About Us',
                            'body' => 'Short new about text.',
                        ]),
                    ]]],
                ]],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('sites.sections.regenerate', [$site, $about]), [
                'instruction' => 'shorter',
            ])
            ->assertRedirect(route('sites.sections', $site))
            ->assertSessionHas('status');

        $this->assertSame('Short new about text.', $about->fresh()->content['body']);
        $this->assertSame('Original Hero', $hero->fresh()->content['headline']);
        $this->assertSame(SectionCreatedBy::Ai, $about->fresh()->currentVersion->created_by);
    }

    public function test_ai_failure_during_regenerate_keeps_current_version_intact(): void
    {
        config()->set('services.gemini.api_key', 'test-key');

        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        $about = Section::factory()->for($site)->create([
            'type' => SectionType::About,
            'content' => ['heading' => 'About Us', 'body' => 'Keep this safe.'],
        ]);
        $v1 = SectionVersion::factory()->for($about)->create([
            'content' => ['heading' => 'About Us', 'body' => 'Keep this safe.'],
            'created_by' => SectionCreatedBy::Ai,
        ]);
        $about->update(['current_version_id' => $v1->id]);

        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 503),
        ]);

        $this->actingAs($user)
            ->post(route('sites.sections.regenerate', [$site, $about]))
            ->assertRedirect(route('sites.sections', $site))
            ->assertSessionHas('error');

        $about->refresh();
        $this->assertSame('Keep this safe.', $about->content['body']);
        $this->assertSame($v1->id, $about->current_version_id);
    }

    public function test_restore_brings_old_version_back_and_toggle_hides_section(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        $about = Section::factory()->for($site)->create([
            'type' => SectionType::About,
            'content' => ['heading' => 'About Us', 'body' => 'Version 2'],
            'is_visible' => true,
        ]);
        $v1 = SectionVersion::factory()->for($about)->create([
            'content' => ['heading' => 'About Us', 'body' => 'Version 1'],
            'created_by' => SectionCreatedBy::Ai,
        ]);
        $v2 = SectionVersion::factory()->for($about)->create([
            'content' => ['heading' => 'About Us', 'body' => 'Version 2'],
            'created_by' => SectionCreatedBy::User,
        ]);
        $about->update(['current_version_id' => $v2->id]);

        $this->actingAs($user)
            ->post(route('sites.sections.restore', [$site, $about, $v1]))
            ->assertRedirect(route('sites.sections', $site));

        $this->assertSame('Version 1', $about->fresh()->content['body']);
        $this->assertSame($v1->id, $about->fresh()->current_version_id);

        $this->actingAs($user)
            ->post(route('sites.sections.toggle', [$site, $about]))
            ->assertRedirect(route('sites.sections', $site));

        $this->assertFalse($about->fresh()->is_visible);
    }
}
