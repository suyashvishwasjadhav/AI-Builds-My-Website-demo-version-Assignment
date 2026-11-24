<?php

namespace Tests\Feature;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Models\Question;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_makes_site_publicly_viewable_and_unpublish_returns_404(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'business_name' => 'Sunrise Bakery',
            'slug' => 'sunrise-bakery',
            'status' => SiteStatus::Ready,
        ]);
        Section::factory()->for($site)->create([
            'type' => SectionType::Hero,
            'content' => ['headline' => 'Sunrise Bakery', 'subheadline' => 'Pune'],
        ]);

        // Unpublished returns 404 to public
        $this->get('/s/sunrise-bakery')
            ->assertNotFound()
            ->assertSee('noindex', false);

        // Owner publishes
        $this->actingAs($user)
            ->post(route('sites.publish', $site))
            ->assertRedirect(route('sites.show', $site));

        $site->refresh();
        $this->assertSame(SiteStatus::Published, $site->status);
        $this->assertNotNull($site->published_at);

        // Public guest can view without login, and noindex is absent
        auth()->logout();
        $this->get('/s/sunrise-bakery')
            ->assertOk()
            ->assertSee('Sunrise Bakery')
            ->assertDontSee('noindex', false);

        // Owner unpublishes -> returns 404 again
        $this->actingAs($user)
            ->post(route('sites.unpublish', $site))
            ->assertRedirect(route('sites.show', $site));

        $this->assertSame(SiteStatus::Ready, $site->fresh()->status);
        $this->assertNull($site->fresh()->published_at);

        auth()->logout();
        $this->get('/s/sunrise-bakery')->assertNotFound();
    }

    public function test_publishing_is_blocked_when_conflict_is_open_or_no_sections_exist(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create(['status' => SiteStatus::Draft]);

        // No sections -> blocked
        $this->actingAs($user)
            ->post(route('sites.publish', $site))
            ->assertRedirect(route('sites.show', $site))
            ->assertSessionHas('error');
        $this->assertSame(SiteStatus::Draft, $site->fresh()->status);

        // Add section + open conflict -> still blocked
        Section::factory()->for($site)->create(['type' => SectionType::Hero]);
        Question::factory()->for($site)->create([
            'type' => QuestionType::ResolveConflict,
            'status' => QuestionStatus::Open,
        ]);

        $this->actingAs($user)
            ->post(route('sites.publish', $site))
            ->assertRedirect(route('sites.show', $site))
            ->assertSessionHas('error');
        $this->assertNotSame(SiteStatus::Published, $site->fresh()->status);
    }

    public function test_slug_rename_and_collision_handling(): void
    {
        $user = User::factory()->create();
        $otherSite = Site::factory()->create(['slug' => 'taken-slug']);
        $site = Site::factory()->for($user)->create(['slug' => 'my-site']);

        // Collision rejected
        $this->actingAs($user)
            ->patch(route('sites.slug.update', $site), ['slug' => 'taken-slug'])
            ->assertSessionHasErrors('slug');

        // Unsafe characters rejected
        $this->actingAs($user)
            ->patch(route('sites.slug.update', $site), ['slug' => 'Bad Slug!'])
            ->assertSessionHasErrors('slug');

        // Valid unique slug succeeds
        $this->actingAs($user)
            ->patch(route('sites.slug.update', $site), ['slug' => 'new-clean-slug'])
            ->assertRedirect(route('sites.show', $site));

        $this->assertSame('new-clean-slug', $site->fresh()->slug);
    }

    public function test_non_owner_cannot_publish_or_rename_slug(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();

        $this->actingAs($userA)->post(route('sites.publish', $siteB))->assertForbidden();
        $this->actingAs($userA)->patch(route('sites.slug.update', $siteB), ['slug' => 'hacked'])->assertForbidden();
    }
}
