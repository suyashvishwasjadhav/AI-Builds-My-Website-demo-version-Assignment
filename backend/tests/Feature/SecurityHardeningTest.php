<?php

namespace Tests\Feature;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Models\Enquiry;
use App\Models\Fact;
use App\Models\Media;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use App\Services\UrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_applied_to_public_and_landing_responses(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'slug' => 'sec-headers',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        foreach (['/', route('public.site', $site->slug)] as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
            $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            $response->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
        }
    }

    public function test_idor_cross_site_resource_access_is_blocked(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $siteA = Site::factory()->for($userA)->create();
        $siteB = Site::factory()->for($userB)->create();

        $sectionB = Section::create([
            'site_id' => $siteB->id,
            'type' => SectionType::Hero,
            'content' => ['headline' => 'Site B'],
            'is_visible' => true,
        ]);

        $mediaB = Media::create([
            'site_id' => $siteB->id,
            'path' => 'sites/2/media/test.jpg',
            'is_hero' => false,
            'is_placeholder' => false,
        ]);

        $enquiryB = Enquiry::create([
            'site_id' => $siteB->id,
            'name' => 'Lead B',
            'email' => 'b@example.com',
            'message' => 'Secret message',
        ]);

        // Even though userA owns siteA, passing siteB's child IDs under siteA must 404
        $this->actingAs($userA)
            ->patchJson(route('sites.sections.inline', [$siteA, $sectionB]), [
                'field' => 'headline',
                'value' => 'Tampered',
            ])
            ->assertNotFound();

        $this->actingAs($userA)
            ->delete(route('sites.media.destroy', [$siteA, $mediaB]))
            ->assertNotFound();

        $this->actingAs($userA)
            ->patch(route('sites.enquiries.status', [$siteA, $enquiryB]), ['status' => 'closed'])
            ->assertNotFound();
    }

    public function test_dangerous_svg_and_php_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $svg = UploadedFile::fake()->create('xss.svg', 10, 'image/svg+xml');
        $php = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $this->actingAs($owner)
            ->post(route('sites.media.store', $site), ['photos' => [$svg]])
            ->assertSessionHasErrors('photos.0');

        $this->actingAs($owner)
            ->post(route('sites.media.store', $site), ['photos' => [$php]])
            ->assertSessionHasErrors('photos.0');

        $this->assertSame(0, $site->media()->count());
    }

    public function test_xss_payloads_are_escaped_on_public_render(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'business_name' => '<script>alert("name")</script>',
            'slug' => 'xss-escape',
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => SectionType::Hero,
            'content' => ['headline' => '<img src=x onerror=alert(1)>'],
            'is_visible' => true,
        ]);

        Fact::create([
            'site_id' => $site->id,
            'key' => 'address',
            'value' => '<script>alert("addr")</script>',
            'status' => FactStatus::Confirmed,
            'confidence' => FactConfidence::High,
        ]);

        $response = $this->get(route('public.site', $site->slug));
        $response->assertOk();
        $response->assertDontSee('<script>alert("name")</script>', false);
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
        $response->assertDontSee('<script>alert("addr")</script>', false);
    }

    public function test_url_guard_blocks_ssrf_targets(): void
    {
        $guard = new UrlGuard();

        $this->assertFalse($guard->isAllowed('http://169.254.169.254/latest/meta-data/'));
        $this->assertFalse($guard->isAllowed('http://127.0.0.1:8000/admin'));
        $this->assertFalse($guard->isAllowed('http://localhost/secret'));
        $this->assertFalse($guard->isAllowed('file:///etc/passwd'));
        $this->assertFalse($guard->isAllowed('javascript:alert(1)'));
    }
}
