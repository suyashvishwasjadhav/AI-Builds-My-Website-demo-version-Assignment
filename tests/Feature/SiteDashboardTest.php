<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $site = Site::factory()->create();

        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/sites')->assertRedirect('/login');
        $this->get('/sites/create')->assertRedirect('/login');
        $this->get('/sites/'.$site->id)->assertRedirect('/login');
    }

    public function test_owner_can_view_my_sites_and_create_a_site(): void
    {
        $user = User::factory()->create();
        $otherSite = Site::factory()->create(['business_name' => 'Other Hidden Business']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('My sites')
            ->assertSee('Create new site')
            ->assertDontSee('Other Hidden Business');

        $this->actingAs($user)
            ->get('/sites/create')
            ->assertOk()
            ->assertSee('Business name');

        $response = $this->actingAs($user)
            ->post('/sites', [
                'business_name' => 'Sunrise Bakery',
                'city' => 'Pune',
            ]);

        $site = Site::where('business_name', 'Sunrise Bakery')->firstOrFail();
        $response->assertRedirect(route('sites.show', $site));

        $this->actingAs($user)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('Sunrise Bakery')
            ->assertSee('draft')
            ->assertSee('Overview')
            ->assertSee('Sources')
            ->assertSee('Facts')
            ->assertSee('Sections')
            ->assertSee('Media')
            ->assertSee('Enquiries')
            ->assertSee('Settings')
            ->assertSee('Needs your attention')
            ->assertSee('Publish');
    }

    public function test_non_owner_cannot_view_another_users_site_overview_or_tabs(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();

        $this->actingAs($userA)
            ->get(route('sites.show', $siteB))
            ->assertForbidden();

        $this->actingAs($userA)
            ->get(route('sites.tab', [$siteB, 'sources']))
            ->assertForbidden();
    }
}
