<?php

namespace Tests\Unit;

use App\Services\AiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    public function test_cascades_to_groq_when_gemini_hits_rate_limit_429(): void
    {
        config()->set('services.gemini.api_key', 'gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.groq.api_key', 'groq-key');
        config()->set('services.groq.model', 'llama-3.3-70b-versatile');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'Rate limit exceeded'], 429),
            'api.groq.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['status' => 'success_from_groq']),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $ai = new AiService();
        $result = $ai->generateJson('Test prompt');

        $this->assertIsArray($result);
        $this->assertSame('success_from_groq', $result['status']);
        $this->assertSame('groq', $ai->getLastProviderUsed());
    }

    public function test_cascades_through_multiple_providers_until_one_succeeds(): void
    {
        config()->set('services.gemini.api_key', 'gemini-key');
        config()->set('services.groq.api_key', 'groq-key');
        config()->set('services.mistral.api_key', 'mistral-key');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'Unavailable'], 503),
            'api.groq.com/*' => Http::response(['error' => 'Rate limit'], 429),
            'api.mistral.ai/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode(['provider' => 'mistral_rescued']),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $ai = new AiService();
        $result = $ai->generateJson('Test prompt');

        $this->assertIsArray($result);
        $this->assertSame('mistral_rescued', $result['provider']);
        $this->assertSame('mistral', $ai->getLastProviderUsed());
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
