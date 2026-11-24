<?php

namespace Tests\Feature;

use App\Enums\FactStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SiteStatus;
use App\Jobs\GenerateSiteJob;
use App\Models\Fact;
use App\Models\Question;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_is_blocked_while_conflict_or_pick_business_question_is_open(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        Question::factory()->for($site)->create([
            'type' => QuestionType::ResolveConflict,
            'status' => QuestionStatus::Open,
        ]);

        $this->actingAs($user)
            ->post(route('sites.generate', $site))
            ->assertRedirect(route('sites.show', $site))
            ->assertSessionHas('error');

        Queue::assertNotPushed(GenerateSiteJob::class);
    }

    public function test_generation_is_allowed_when_questions_are_skipped(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        Question::factory()->for($site)->create([
            'type' => QuestionType::ResolveConflict,
            'status' => QuestionStatus::Skipped,
        ]);

        $this->actingAs($user)
            ->post(route('sites.generate', $site))
            ->assertRedirect(route('sites.show', $site))
            ->assertSessionHas('status');

        Queue::assertPushed(GenerateSiteJob::class, 1);
    }

    public function test_fallback_builds_basic_site_from_confirmed_facts(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'business_name' => 'Royal Tailor',
            'city' => 'Mumbai',
        ]);
        Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '9820011223',
            'status' => FactStatus::Confirmed,
        ]);

        $this->actingAs($user)
            ->post(route('sites.generate.fallback', $site))
            ->assertRedirect(route('sites.show', $site));

        $this->assertSame(SiteStatus::Ready, $site->fresh()->status);
        $this->assertCount(4, $site->sections);
    }

    public function test_non_owner_cannot_trigger_generation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();

        $this->actingAs($userA)
            ->post(route('sites.generate', $siteB))
            ->assertForbidden();
    }
}
