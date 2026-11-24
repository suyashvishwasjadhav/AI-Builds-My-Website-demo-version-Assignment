<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SitePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_access_their_own_site(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();

        $this->assertTrue(Gate::forUser($userB)->allows('view', $siteB));
        $this->assertTrue(Gate::forUser($userB)->allows('update', $siteB));
        $this->assertTrue(Gate::forUser($userB)->allows('delete', $siteB));

        $this->assertFalse(Gate::forUser($userA)->allows('view', $siteB));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $siteB));
        $this->assertFalse(Gate::forUser($userA)->allows('delete', $siteB));
    }

    public function test_user_a_gets_403_when_opening_user_bs_site_route(): void
    {
        Route::middleware(['web', 'auth', 'can:view,site'])
            ->get('/_test/sites/{site}', fn (Site $site) => response()->json(['id' => $site->id]));

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();

        $this->actingAs($userA)
            ->get('/_test/sites/'.$siteB->id)
            ->assertForbidden();

        $this->actingAs($userB)
            ->get('/_test/sites/'.$siteB->id)
            ->assertOk();
    }
}
