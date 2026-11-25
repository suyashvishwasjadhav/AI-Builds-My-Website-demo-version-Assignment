<?php

namespace Tests\Feature;

use App\Enums\SectionType;
use App\Models\Section;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class StaticExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_static_website_zip(): void
    {
        $owner = User::factory()->create();
        $site = Site::factory()->for($owner)->create([
            'business_name' => 'OEC Static Export',
            'slug' => 'oec-static',
        ]);

        Section::create([
            'site_id' => $site->id,
            'type' => SectionType::Hero,
            'content' => [
                'headline' => 'Static Export Works',
                'subheadline' => 'Ready for Netlify',
            ],
            'is_visible' => true,
        ]);

        $response = $this->actingAs($owner)->get(route('sites.export.zip', $site));

        $response->assertOk();
        $file = $response->getFile();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($file->getPathname()) === true);

        $indexHtml = $zip->getFromName('index.html');
        $this->assertIsString($indexHtml);
        $this->assertStringContainsString('Static Export Works', $indexHtml);
        $this->assertNotFalse($zip->getFromName('README.txt'));

        $zip->close();
    }

    public function test_non_owner_cannot_export_site_zip(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $this->actingAs($other)
            ->get(route('sites.export.zip', $site))
            ->assertForbidden();
    }
}
