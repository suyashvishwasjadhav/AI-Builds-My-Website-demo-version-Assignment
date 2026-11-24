<?php

namespace Tests\Unit;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Site;
use App\Models\Source;
use App\Services\Reader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReaderTest extends TestCase
{
    use RefreshDatabase;

    private Reader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->reader = new Reader();
    }

    public function test_reads_text_pdf(): void
    {
        $pdfContent = $this->buildSimplePdf('Sunrise Bakery Menu Croissant 120');
        Storage::disk('local')->put('uploads/1/menu.pdf', $pdfContent);

        $source = Source::factory()->create([
            'site_id' => Site::factory(),
            'type' => SourceType::File,
            'path_or_url' => 'uploads/1/menu.pdf',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Read, $updated->status);
        $this->assertStringContainsString('Sunrise Bakery Menu Croissant 120', (string) $updated->raw_text);
        $this->assertNull($updated->error_message);
    }

    public function test_corrupted_pdf_marks_source_failed_without_crashing(): void
    {
        Storage::disk('local')->put('uploads/1/broken.pdf', '%PDF-1.4\n%%%corrupted-binary-data');

        $source = Source::factory()->create([
            'site_id' => Site::factory(),
            'type' => SourceType::File,
            'path_or_url' => 'uploads/1/broken.pdf',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Failed, $updated->status);
        $this->assertNotEmpty($updated->error_message);
    }

    public function test_reads_price_list_csv(): void
    {
        $csv = "Service,Price\nHaircut,300\nBeard Trim,150\n";
        Storage::disk('local')->put('uploads/1/prices.csv', $csv);

        $source = Source::factory()->create([
            'site_id' => Site::factory(),
            'type' => SourceType::File,
            'path_or_url' => 'uploads/1/prices.csv',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Read, $updated->status);
        $this->assertStringContainsString('Service | Price', (string) $updated->raw_text);
        $this->assertStringContainsString('Haircut | 300', (string) $updated->raw_text);
        $this->assertStringContainsString('Beard Trim | 150', (string) $updated->raw_text);
    }

    public function test_transcribes_image_with_gemini(): void
    {
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "Masala Chai - Rs 30\nSamosa - Rs 25"],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Storage::disk('local')->put('uploads/1/menu.png', 'fake-png-bytes');

        $source = Source::factory()->create([
            'site_id' => Site::factory(),
            'type' => SourceType::File,
            'path_or_url' => 'uploads/1/menu.png',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Read, $updated->status);
        $this->assertSame("Masala Chai - Rs 30\nSamosa - Rs 25", $updated->raw_text);
    }

    public function test_unreadable_image_marks_source_failed(): void
    {
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'UNREADABLE'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Storage::disk('local')->put('uploads/1/blurry.jpg', 'fake-jpg-bytes');

        $source = Source::factory()->create([
            'site_id' => Site::factory(),
            'type' => SourceType::File,
            'path_or_url' => 'uploads/1/blurry.jpg',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Failed, $updated->status);
        $this->assertNotEmpty($updated->error_message);
    }

    private function buildSimplePdf(string $text): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT\n/F1 12 Tf\n72 720 Td\n({$escaped}) Tj\nET";
        $len = strlen($stream);

        $objects = [
            1 => "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            2 => "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            3 => "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n",
            4 => "4 0 obj\n<< /Length {$len} >>\nstream\n{$stream}\nendstream\nendobj\n",
            5 => "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
