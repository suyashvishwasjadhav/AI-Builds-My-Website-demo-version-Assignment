<?php

namespace Tests\Feature;

use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Models\Enquiry;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_enquiry_is_stored_and_shown_newest_first_in_dashboard(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'slug' => 'sunrise-bakery',
            'status' => SiteStatus::Published,
        ]);
        Section::factory()->for($site)->create([
            'type' => SectionType::Contact,
            'content' => ['heading' => 'Contact', 'intro' => 'Send us a note.'],
        ]);

        $this->post(route('public.enquiries.store', $site->slug), [
            'name' => 'Asha',
            'email' => 'asha@example.com',
            'phone' => '9820012345',
            'message' => 'Do you take custom cake orders?',
        ])->assertRedirect(route('public.site', $site->slug))
            ->assertSessionHas('enquiry_sent');

        $this->assertDatabaseHas('enquiries', [
            'site_id' => $site->id,
            'name' => 'Asha',
            'email' => 'asha@example.com',
        ]);

        $enquiry = Enquiry::firstOrFail();

        $this->actingAs($owner)
            ->get(route('sites.enquiries', $site))
            ->assertOk()
            ->assertSee('Asha')
            ->assertSee('Do you take custom cake orders?')
            ->assertSee('Mark as read');

        $this->actingAs($owner)
            ->post(route('sites.enquiries.read', [$site, $enquiry]))
            ->assertRedirect(route('sites.enquiries', $site));

        $this->assertNotNull($enquiry->fresh()->read_at);
    }

    public function test_honeypot_spam_is_dropped_silently(): void
    {
        $site = Site::factory()->create([
            'slug' => 'sunrise-bakery',
            'status' => SiteStatus::Published,
        ]);

        $this->post(route('public.enquiries.store', $site->slug), [
            'name' => 'Spam Bot',
            'email' => 'bot@spam.example',
            'message' => 'Buy cheap watches now!',
            'website_hp' => 'http://spam-link.example',
        ])->assertRedirect(route('public.site', $site->slug));

        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_non_owner_cannot_view_or_mark_another_users_enquiries(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $siteB = Site::factory()->for($userB)->create();
        $enquiry = Enquiry::factory()->for($siteB)->create();

        $this->actingAs($userA)->get(route('sites.enquiries', $siteB))->assertForbidden();
        $this->actingAs($userA)->post(route('sites.enquiries.read', [$siteB, $enquiry]))->assertForbidden();
    }
}
