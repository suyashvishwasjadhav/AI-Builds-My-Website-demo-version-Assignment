<?php

namespace Tests\Feature;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Models\Fact;
use App\Models\Site;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_facts_with_source_snippet_and_confidence(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        $source = Source::factory()->for($site)->create([
            'label' => 'menu.pdf',
        ]);
        Fact::factory()->for($site)->for($source)->create([
            'key' => 'phone',
            'value' => '+91 98200 12345',
            'snippet' => 'Call +91 98200 12345',
            'confidence' => FactConfidence::High,
            'status' => FactStatus::Candidate,
        ]);

        $this->actingAs($user)
            ->get(route('sites.facts', $site))
            ->assertOk()
            ->assertSee('+91 98200 12345')
            ->assertSee('menu.pdf')
            ->assertSee('Call +91 98200 12345')
            ->assertSee('high');
    }

    public function test_non_owner_cannot_view_another_users_facts(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();

        $this->actingAs($userA)
            ->get(route('sites.facts', $siteB))
            ->assertForbidden();
    }
}
