<?php

namespace Tests\Unit;

use App\Enums\FactStatus;
use App\Enums\SectionCreatedBy;
use App\Enums\SectionType;
use App\Enums\SiteStatus;
use App\Models\Fact;
use App\Models\Site;
use App\Services\Writer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WriterTest extends TestCase
{
    use RefreshDatabase;

    private Writer $writer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->writer = new Writer();
    }

    public function test_success_saves_sections_versions_ai_run_and_sends_only_confirmed_facts(): void
    {
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        $site = Site::factory()->create([
            'business_name' => 'Sunrise Bakery',
            'city' => 'Pune',
            'status' => SiteStatus::Draft,
        ]);

        Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '9820012345',
            'status' => FactStatus::Confirmed,
        ]);
        Fact::factory()->for($site)->create([
            'key' => 'service',
            'value' => 'Sourdough Bread - Rs 150',
            'status' => FactStatus::UserProvided,
        ]);
        Fact::factory()->for($site)->create([
            'key' => 'other',
            'value' => 'SECRET_CANDIDATE_FACT',
            'status' => FactStatus::Candidate,
        ]);
        Fact::factory()->for($site)->create([
            'key' => 'other',
            'value' => 'SECRET_REJECTED_FACT',
            'status' => FactStatus::Rejected,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => function ($request) {
                $body = json_encode($request->data());
                $this->assertStringContainsString('9820012345', (string) $body);
                $this->assertStringContainsString('Sourdough Bread - Rs 150', (string) $body);
                $this->assertStringNotContainsString('SECRET_CANDIDATE_FACT', (string) $body);
                $this->assertStringNotContainsString('SECRET_REJECTED_FACT', (string) $body);

                return Http::response([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'seo' => [
                                    'title' => 'Sunrise Bakery - Pune',
                                    'description' => 'Fresh sourdough bread in Pune.',
                                ],
                                'hero' => [
                                    'headline' => 'Sunrise Bakery',
                                    'subheadline' => 'Pune',
                                ],
                                'about' => [
                                    'heading' => 'About Us',
                                    'body' => 'Serving Pune with fresh sourdough.',
                                ],
                                'services' => [
                                    'heading' => 'Our Menu',
                                    'items' => [
                                        ['name' => 'Sourdough Bread', 'description' => 'Rs 150'],
                                    ],
                                ],
                                'contact' => [
                                    'heading' => 'Contact Us',
                                    'intro' => 'Call us on 9820012345.',
                                ],
                            ]),
                        ]]],
                    ]],
                ], 200);
            },
        ]);

        $ok = $this->writer->generate($site);

        $this->assertTrue($ok);
        $this->assertSame(SiteStatus::Ready, $site->fresh()->status);
        $this->assertCount(4, $site->sections);
        $this->assertDatabaseHas('sections', [
            'site_id' => $site->id,
            'type' => SectionType::Hero->value,
        ]);
        $this->assertDatabaseHas('section_versions', [
            'created_by' => SectionCreatedBy::Ai->value,
        ]);
        $this->assertDatabaseHas('ai_runs', [
            'site_id' => $site->id,
            'purpose' => 'write_site',
            'status' => 'success',
        ]);
    }

    public function test_timeout_retries_twice_logs_failure_and_keeps_data(): void
    {
        config()->set('services.gemini.api_key', 'test-key');

        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('Timed out');
        });

        $site = Site::factory()->create();
        $ok = $this->writer->generate($site);

        $this->assertFalse($ok);
        $this->assertSame(3, $attempts);
        $this->assertDatabaseHas('ai_runs', [
            'site_id' => $site->id,
            'status' => 'failed',
        ]);
    }

    public function test_429_rate_limit_retries_and_logs_failure(): void
    {
        config()->set('services.gemini.api_key', 'test-key');

        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            return Http::response(['error' => 'Rate limited'], 429);
        });

        $site = Site::factory()->create();
        $ok = $this->writer->generate($site);

        $this->assertFalse($ok);
        $this->assertSame(3, $attempts);
        $this->assertDatabaseHas('ai_runs', [
            'site_id' => $site->id,
            'status' => 'failed',
        ]);
    }

    public function test_invalid_json_from_ai_is_rejected_and_logged(): void
    {
        config()->set('services.gemini.api_key', 'test-key');

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => '{"unexpected": "shape"}',
                    ]]],
                ]],
            ], 200),
        ]);

        $site = Site::factory()->create();
        $ok = $this->writer->generate($site);

        $this->assertFalse($ok);
        $this->assertCount(0, $site->sections);
        $this->assertDatabaseHas('ai_runs', [
            'site_id' => $site->id,
            'status' => 'failed',
        ]);
    }

    public function test_build_basic_fallback_creates_working_sections_without_ai(): void
    {
        Http::fake();

        $site = Site::factory()->create([
            'business_name' => 'Green Leaf Cafe',
            'city' => 'Bengaluru',
        ]);
        Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '080-12345678',
            'status' => FactStatus::Confirmed,
        ]);
        Fact::factory()->for($site)->create([
            'key' => 'service',
            'value' => 'Filter Coffee - Rs 40',
            'status' => FactStatus::Confirmed,
        ]);

        $this->writer->buildBasicFallback($site);

        Http::assertNothingSent();
        $this->assertCount(4, $site->sections);
        $this->assertSame(SiteStatus::Ready, $site->fresh()->status);
    }
}
