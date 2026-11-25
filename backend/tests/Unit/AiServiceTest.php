<?php

namespace Tests\Unit;

use App\Services\AiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    public function test_cascades_to_openrouter_when_groq_hits_rate_limit_429(): void
    {
        config()->set('services.groq.api_key', 'groq-key');
        config()->set('services.openrouter.api_key', 'openrouter-key');

        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'Rate limit exceeded'], 429),
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['status' => 'success_from_openrouter']),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $ai = new AiService();
        $result = $ai->generateJson('Test prompt');

        $this->assertIsArray($result);
        $this->assertSame('success_from_openrouter', $result['status']);
        $this->assertSame('openrouter', $ai->getLastProviderUsed());
    }

    public function test_cascades_through_multiple_providers_until_one_succeeds(): void
    {
        config()->set('services.groq.api_key', 'groq-key');
        config()->set('services.openrouter.api_key', 'openrouter-key');
        config()->set('services.gemini.api_key', 'gemini-key');

        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'Rate limit'], 429),
            'openrouter.ai/*' => Http::response(['error' => 'Unavailable'], 503),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode(['provider' => 'gemini_rescued'])],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $ai = new AiService();
        $result = $ai->generateJson('Test prompt');

        $this->assertIsArray($result);
        $this->assertSame('gemini_rescued', $result['provider']);
        $this->assertSame('gemini', $ai->getLastProviderUsed());
    }

    public function test_parses_json_wrapped_in_markdown_fences(): void
    {
        $ai = new AiService();
        $raw = "```json\n{\n  \"headline\": \"Hello World\"\n}\n```";

        $parsed = $ai->parseJson($raw);
        $this->assertIsArray($parsed);
        $this->assertSame('Hello World', $parsed['headline']);
    }
}
