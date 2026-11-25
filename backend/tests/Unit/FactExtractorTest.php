<?php

namespace Tests\Unit;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Site;
use App\Models\Source;
use App\Services\FactExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FactExtractorTest extends TestCase
{
    use RefreshDatabase;

    private FactExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new FactExtractor();
    }

    public function test_extracts_facts_from_pdf_and_link_and_drops_hallucinated_fact(): void
    {
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        $site = Site::factory()->create();

        $pdfSource = Source::factory()->for($site)->create([
            'type' => SourceType::File,
            'label' => 'brochure.pdf',
            'status' => SourceStatus::Read,
            'raw_text' => 'Sunrise Bakery in Pune. Call +91 98200 12345. Open 7am to 9pm daily.',
        ]);

        $linkSource = Source::factory()->for($site)->create([
            'type' => SourceType::Link,
            'label' => 'sunrisebakery.example',
            'status' => SourceStatus::Read,
            'raw_text' => 'Email us at hello@sunrisebakery.example. Founded in 2015.',
        ]);

        Http::fakeSequence()
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'facts' => [
                                [
                                    'key' => 'phone',
                                    'value' => '+91 98200 12345',
                                    'snippet' => 'Call +91 98200 12345',
                                    'confidence' => 'high',
                                ],
                                [
                                    'key' => 'hours',
                                    'value' => '7am to 9pm daily',
                                    'snippet' => 'Open 7am to 9pm daily',
                                    'confidence' => 'high',
                                ],
                                // Hallucinated fact whose snippet does not exist in raw_text:
                                [
                                    'key' => 'other',
                                    'value' => 'Best Bakery Award 2024',
                                    'snippet' => 'Winner of Best Bakery Award 2024',
                                    'confidence' => 'high',
                                ],
                            ],
                        ]),
                    ]]],
                ]],
            ], 200)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'facts' => [
                                [
                                    'key' => 'email',
                                    'value' => 'hello@sunrisebakery.example',
                                    'snippet' => 'hello@sunrisebakery.example',
                                    'confidence' => 'high',
                                ],
                                [
                                    'key' => 'year_founded',
                                    'value' => '2015',
                                    'snippet' => 'Founded in 2015',
                                    'confidence' => 'medium',
                                ],
                            ],
                        ]),
                    ]]],
                ]],
            ], 200);

        $count = $this->extractor->extractForSite($site);

        $this->assertSame(4, $count);
        $this->assertDatabaseMissing('facts', [
            'site_id' => $site->id,
            'value' => 'Best Bakery Award 2024',
        ]);

        $this->assertDatabaseHas('facts', [
            'site_id' => $site->id,
            'source_id' => $pdfSource->id,
            'key' => 'phone',
            'value' => '+91 98200 12345',
            'status' => FactStatus::Candidate->value,
        ]);

        $this->assertDatabaseHas('facts', [
            'site_id' => $site->id,
            'source_id' => $linkSource->id,
            'key' => 'email',
            'value' => 'hello@sunrisebakery.example',
        ]);
    }

    public function test_parses_clean_price_table_without_calling_gemini(): void
    {
        Http::fake();

        $site = Site::factory()->create();
        $csvSource = Source::factory()->for($site)->create([
            'type' => SourceType::File,
            'label' => 'prices.csv',
            'status' => SourceStatus::Read,
            'raw_text' => "Service | Price\nHaircut | Rs 300\nBeard Trim | Rs 150",
        ]);

        $count = $this->extractor->extractForSite($site);

        Http::assertNothingSent();
        $this->assertSame(2, $count);

        $this->assertDatabaseHas('facts', [
            'site_id' => $site->id,
            'source_id' => $csvSource->id,
            'key' => 'service',
            'value' => 'Haircut - Rs 300',
            'snippet' => 'Haircut | Rs 300',
            'confidence' => FactConfidence::High->value,
        ]);
    }

    public function test_deduplicates_identical_facts_across_sources(): void
    {
        config()->set('services.gemini.api_key', 'test-key');

        $site = Site::factory()->create();

        Source::factory()->for($site)->create([
            'status' => SourceStatus::Read,
            'raw_text' => 'Call +91 98200 12345',
        ]);
        Source::factory()->for($site)->create([
            'status' => SourceStatus::Read,
            'raw_text' => 'Phone: +91 98200 12345',
        ]);

        $payload = [
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'facts' => [[
                            'key' => 'phone',
                            'value' => '+91 98200 12345',
                            'snippet' => '+91 98200 12345',
                            'confidence' => 'high',
                        ]],
                    ]),
                ]]],
            ]],
        ];

        Http::fake([
            '*' => Http::response($payload, 200),
        ]);

        $count = $this->extractor->extractForSite($site);

        $this->assertSame(1, $count);
        $this->assertSame(1, $site->facts()->where('key', 'phone')->count());
    }
}
