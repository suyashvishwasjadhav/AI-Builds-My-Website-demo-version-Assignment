<?php

namespace Tests\Unit;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Site;
use App\Models\Source;
use App\Services\Reader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReaderLinkTest extends TestCase
{
    use RefreshDatabase;

    private Reader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reader = new Reader();
    }

    public function test_reads_normal_website_successfully(): void
    {
        $html = <<<'HTML'
<!doctype html>
<html>
<head>
    <title>Sunrise Bakery - Pune</title>
    <meta name="description" content="Fresh sourdough bread and pastries in Pune.">
    <script>console.log('ignored script');</script>
    <style>.hidden { display: none; }</style>
</head>
<body>
    <h1>Sunrise Bakery</h1>
    <p>Visit us at 42 MG Road, Pune. Call +91 98200 12345 or email hello@sunrisebakery.example.</p>
</body>
</html>
HTML;

        Http::fake([
            '93.184.216.34/*' => Http::response($html, 200),
        ]);

        $source = Source::factory()->create([
            'site_id' => Site::factory(),
            'type' => SourceType::Link,
            'path_or_url' => 'https://93.184.216.34/about',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Read, $updated->status);
        $this->assertStringContainsString('Title: Sunrise Bakery - Pune', (string) $updated->raw_text);
        $this->assertStringContainsString('Description: Fresh sourdough bread and pastries in Pune.', (string) $updated->raw_text);
        $this->assertStringContainsString('+91 98200 12345', (string) $updated->raw_text);
        $this->assertStringNotContainsString('ignored script', (string) $updated->raw_text);
    }

    public function test_timeout_marks_source_failed_gracefully(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $source = Source::factory()->create([
            'site_id' => Site::factory(),
            'type' => SourceType::Link,
            'path_or_url' => 'https://93.184.216.34/slow',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Failed, $updated->status);
        $this->assertStringContainsString('timed out', strtolower((string) $updated->error_message));
    }

    public function test_403_marks_source_blocked_and_creates_missing_info_question(): void
    {
        Http::fake([
            '*' => Http::response('Forbidden', 403),
        ]);

        $site = Site::factory()->create();
        $source = Source::factory()->for($site)->create([
            'type' => SourceType::Link,
            'path_or_url' => 'https://www.instagram.com/sunrisebakery',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Blocked, $updated->status);
        $this->assertSame(
            "We couldn't read this link. Please paste the details or upload a screenshot.",
            $updated->error_message
        );

        $question = $site->questions()->firstOrFail();
        $this->assertSame(QuestionType::MissingInfo, $question->type);
        $this->assertSame(QuestionStatus::Open, $question->status);
        $this->assertStringContainsString("We couldn't read this link", $question->prompt);
    }

    public function test_login_wall_on_facebook_marks_source_blocked_and_creates_question(): void
    {
        $loginHtml = '<html><head><title>Log in to Facebook</title></head><body><p>You must log in to continue.</p></body></html>';

        Http::fake([
            '*' => Http::response($loginHtml, 200),
        ]);

        $site = Site::factory()->create();
        $source = Source::factory()->for($site)->create([
            'type' => SourceType::Link,
            'path_or_url' => 'https://www.facebook.com/sunrisebakery',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        $this->assertSame(SourceStatus::Blocked, $updated->status);
        $this->assertDatabaseHas('questions', [
            'site_id' => $site->id,
            'type' => QuestionType::MissingInfo->value,
            'status' => QuestionStatus::Open->value,
        ]);
    }

    public function test_private_ip_url_is_refused_without_making_http_request(): void
    {
        Http::fake();

        $source = Source::factory()->create([
            'site_id' => Site::factory(),
            'type' => SourceType::Link,
            'path_or_url' => 'http://127.0.0.1/admin',
            'status' => SourceStatus::Pending,
        ]);

        $updated = $this->reader->read($source);

        Http::assertNothingSent();
        $this->assertSame(SourceStatus::Failed, $updated->status);
        $this->assertNotEmpty($updated->error_message);
    }
}
