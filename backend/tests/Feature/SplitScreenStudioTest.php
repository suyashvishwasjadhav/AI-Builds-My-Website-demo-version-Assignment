<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitScreenStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_split_screen_studio_with_device_toggles(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'business_name' => 'OEC Studio',
            'template' => 'clean',
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => 'hero',
            'content' => ['headline' => 'OEC Hero'],
            'is_visible' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('sites.studio', $site));

        $response->assertOk();
        $response->assertSee('Split-Screen Studio');
        $response->assertSee('Desktop');
        $response->assertSee('Tablet (768px)');
        $response->assertSee('Mobile (375px)');
        $response->assertSee('embed=1', false);

        $this->actingAs($owner)
            ->patch(route('sites.template.update', $site), [
                'template' => 'bold',
                'redirect_to' => 'studio',
            ])
            ->assertRedirect(route('sites.studio', $site));

        $this->assertSame('bold', $site->fresh()->template);
    }

    public function test_other_user_cannot_access_split_screen_studio(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $this->actingAs($other)->get(route('sites.studio', $site))->assertForbidden();
    }
}
