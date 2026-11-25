<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_svg_qr_code_and_view_printable_poster(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'business_name' => 'OEC Mumbai',
            'city' => 'Mumbai',
            'slug' => 'oec-mumbai',
        ]);

        $download = $this->actingAs($owner)->get(route('sites.qr.download', $site));
        $download->assertOk();
        $this->assertStringContainsString('image/svg+xml', (string) $download->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $download->getContent());

        $poster = $this->actingAs($owner)->get(route('sites.qr.poster', $site));
        $poster->assertOk();
        $poster->assertSee('OEC Mumbai');
        $poster->assertSee('/s/oec-mumbai');
    }

    public function test_other_user_cannot_access_qr_code_endpoints(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $this->actingAs($other)->get(route('sites.qr.download', $site))->assertForbidden();
        $this->actingAs($other)->get(route('sites.qr.poster', $site))->assertForbidden();
    }
}
