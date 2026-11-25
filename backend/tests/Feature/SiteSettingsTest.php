<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_and_update_site_settings_and_delete_site(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'business_name' => 'Old Name',
            'city' => 'Pune',
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->actingAs($owner)
            ->get(route('sites.tab', [$site, 'settings']))
            ->assertOk()
            ->assertSee('Site Settings')
            ->assertSee('General & Timezone');

        $this->actingAs($owner)
            ->patch(route('sites.settings.update', $site), [
                'business_name' => 'New Name',
                'city' => 'Mumbai',
                'timezone' => 'Asia/Dubai',
            ])
            ->assertRedirect(route('sites.tab', [$site, 'settings']));

        $site->refresh();
        $this->assertSame('New Name', $site->business_name);
        $this->assertSame('Mumbai', $site->city);
        $this->assertSame('Asia/Dubai', $site->timezone);

        $this->actingAs($owner)
            ->delete(route('sites.destroy', $site))
            ->assertRedirect(route('sites.index'));

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    }

    public function test_non_owner_cannot_update_or_delete_site_settings(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $this->actingAs($other)
            ->patch(route('sites.settings.update', $site), [
                'business_name' => 'Hacked',
                'city' => 'Delhi',
                'timezone' => 'UTC',
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('sites.destroy', $site))
            ->assertForbidden();
    }
}
