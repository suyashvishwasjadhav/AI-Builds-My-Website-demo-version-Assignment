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
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_translate_site_and_visitors_can_switch_language(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'business_name' => 'OEC Engineering',
            'slug' => 'oec-translate',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => SectionType::Hero,
            'content' => [
                'headline' => 'Engineering Solutions',
                'subheadline' => 'Mumbai piping experts',
            ],
            'is_visible' => true,
        ]);

        Fact::create([
            'site_id' => $site->id,
            'key' => 'phone',
            'value' => '+91 8454 098748',
            'status' => FactStatus::Confirmed,
            'confidence' => FactConfidence::High,
        ]);

        $mockAi = Mockery::mock(AiService::class);
        $mockAi->shouldReceive('isAnyConfigured')->andReturn(true);
        $mockAi->shouldReceive('generateJson')->once()->andReturn([
            'hero' => [
                'headline' => 'इंजीनियरिंग समाधान',
                'subheadline' => 'मुंबई पाइपिंग विशेषज्ञ',
            ],
        ]);
        $this->app->instance(AiService::class, $mockAi);

        $this->actingAs($owner)
            ->post(route('sites.translate', $site), ['locale' => 'hi'])
            ->assertRedirect();

        $this->assertDatabaseHas('site_translations', [
            'site_id' => $site->id,
            'locale' => 'hi',
        ]);

        $response = $this->get(route('public.site', $site->slug) . '?lang=hi');
        $response->assertOk();
        $response->assertSee('इंजीनियरिंग समाधान');
        $response->assertSee('+91 8454 098748');
        $response->assertSee('हिन्दी');
    }

    public function test_non_owner_cannot_translate_site(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $this->actingAs($other)
            ->post(route('sites.translate', $site), ['locale' => 'hi'])
            ->assertForbidden();
    }
}
