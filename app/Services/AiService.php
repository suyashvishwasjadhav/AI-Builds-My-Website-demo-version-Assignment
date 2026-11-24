<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiService
{
    protected ?string $lastProviderUsed = null;
    protected ?string $lastError = null;

    /**
     * Priority order for AI providers when falling back.
     *
     * @var list<string>
     */
    protected array $providerOrder = [
        'gemini',
        'groq',
        'mistral',
        'openrouter',
        'nvidia',
    ];

    public function getLastProviderUsed(): ?string
    {
        return $this->lastProviderUsed;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Check if at least one AI provider has an API key configured.
     */
    public function isAnyConfigured(): bool
    {
        foreach ($this->providerOrder as $provider) {
            if ($this->getApiKey($provider) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate JSON output with automatic provider cascade on rate limits (429), errors, or timeouts.
     *
     * @return array<string, mixed>|null
     */
    public function generateJson(string $prompt, int $timeoutSeconds = 30): ?array
    {
        $this->lastProviderUsed = null;
        $this->lastError = null;
        $attemptedErrors = [];

        foreach ($this->providerOrder as $provider) {
            $apiKey = $this->getApiKey($provider);
            if ($apiKey === '') {
                continue;
            }

            $maxAttempts = 3; // initial try + 2 retries per AGENTS.md
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $rawJson = $this->callProvider($provider, $apiKey, $prompt, $timeoutSeconds);

                    if ($rawJson !== null) {
                        $decoded = $this->parseJson($rawJson);
                        if ($decoded !== null) {
                            $this->lastProviderUsed = $provider;

                            return $decoded;
                        }
                        $attemptedErrors[$provider] = 'Provider returned malformed JSON.';
                    }
                } catch (Throwable $e) {
                    $attemptedErrors[$provider] = $e->getMessage();
                    Log::warning("AI provider [{$provider}] attempt {$attempt} failed.", [
                        'provider' => $provider,
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                }

                if ($attempt < $maxAttempts) {
                    usleep(10_000 * $attempt);
                }
            }

            Log::warning("AI provider [{$provider}] exhausted retries. Cascading to next available provider...");
        }

        $this->lastError = ! empty($attemptedErrors)
            ? implode('; ', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($attemptedErrors), $attemptedErrors))
            : 'No AI providers configured.';

        return null;
    }

    /**
     * Transcribe image text using multimodal models with automatic fallback.
     */
    public function transcribeImage(string $prompt, string $fullPath, string $mimeType, int $timeoutSeconds = 35): ?string
    {
        $bytes = @file_get_contents($fullPath);
        if ($bytes === false || $bytes === '') {
            $this->lastError = 'File is unreadable or empty.';

            return null;
        }

        $base64 = base64_encode($bytes);

        // Multimodal provider priority: Gemini -> OpenRouter
        $visionProviders = ['gemini', 'openrouter'];

        foreach ($visionProviders as $provider) {
            $apiKey = $this->getApiKey($provider);
            if ($apiKey === '') {
                continue;
            }

            try {
                $text = match ($provider) {
                    'gemini' => $this->callGeminiVision($apiKey, $prompt, $base64, $mimeType, $timeoutSeconds),
                    'openrouter' => $this->callOpenRouterVision($apiKey, $prompt, $base64, $mimeType, $timeoutSeconds),
                    default => null,
                };

                if ($text !== null && trim($text) !== '') {
                    $this->lastProviderUsed = $provider;

                    return trim($text);
                }
            } catch (Throwable $e) {
                Log::warning("AI vision provider [{$provider}] failed. Cascading to next vision provider...", [
                    'provider' => $provider,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    protected function callProvider(string $provider, string $apiKey, string $prompt, int $timeoutSeconds): ?string
    {
        return match ($provider) {
            'gemini' => $this->callGemini($apiKey, $prompt, $timeoutSeconds),
            'groq' => $this->callGroq($apiKey, $prompt, $timeoutSeconds),
            'mistral' => $this->callMistral($apiKey, $prompt, $timeoutSeconds),
            'openrouter' => $this->callOpenRouter($apiKey, $prompt, $timeoutSeconds),
            'nvidia' => $this->callNvidia($apiKey, $prompt, $timeoutSeconds),
            default => null,
        };
    }

    protected function callGemini(string $apiKey, string $prompt, int $timeoutSeconds): ?string
    {
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout($timeoutSeconds)->post($endpoint, [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Gemini HTTP {$response->status()}: " . $response->body());
        }

        return (string) $response->json('candidates.0.content.parts.0.text', '');
    }

    protected function callGroq(string $apiKey, string $prompt, int $timeoutSeconds): ?string
    {
        $model = (string) config('services.groq.model', 'llama-3.3-70b-versatile');
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

        $response = Http::timeout($timeoutSeconds)
            ->withToken($apiKey)
            ->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Groq HTTP {$response->status()}: " . $response->body());
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    protected function callMistral(string $apiKey, string $prompt, int $timeoutSeconds): ?string
    {
        $model = (string) config('services.mistral.model', 'mistral-small-latest');
        $endpoint = 'https://api.mistral.ai/v1/chat/completions';

        $response = Http::timeout($timeoutSeconds)
            ->withToken($apiKey)
            ->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Mistral HTTP {$response->status()}: " . $response->body());
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    protected function callOpenRouter(string $apiKey, string $prompt, int $timeoutSeconds): ?string
    {
        $model = (string) config('services.openrouter.model', 'google/gemini-2.5-flash');
        $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

        $response = Http::timeout($timeoutSeconds)
            ->withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => 'http://127.0.0.1:8000',
                'X-Title' => 'AI Website Builder',
            ])
            ->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("OpenRouter HTTP {$response->status()}: " . $response->body());
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    protected function callNvidia(string $apiKey, string $prompt, int $timeoutSeconds): ?string
    {
        $model = (string) config('services.nvidia.model', 'meta/llama-3.3-70b-instruct');
        $endpoint = 'https://integrate.api.nvidia.com/v1/chat/completions';

        $response = Http::timeout($timeoutSeconds)
            ->withToken($apiKey)
            ->post($endpoint, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant. Output ONLY valid raw JSON with no extra commentary.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Nvidia HTTP {$response->status()}: " . $response->body());
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    protected function callGeminiVision(string $apiKey, string $prompt, string $base64, string $mimeType, int $timeoutSeconds): ?string
    {
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout($timeoutSeconds)->post($endpoint, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Gemini Vision HTTP {$response->status()}: " . $response->body());
        }

        return (string) $response->json('candidates.0.content.parts.0.text', '');
    }

    protected function callOpenRouterVision(string $apiKey, string $prompt, string $base64, string $mimeType, int $timeoutSeconds): ?string
    {
        $model = (string) config('services.openrouter.model', 'google/gemini-2.5-flash');
        $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

        $dataUri = "data:{$mimeType};base64,{$base64}";

        $response = Http::timeout($timeoutSeconds)
            ->withToken($apiKey)
            ->withHeaders([
                'HTTP-Referer' => 'http://127.0.0.1:8000',
                'X-Title' => 'AI Website Builder',
            ])
            ->post($endpoint, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $dataUri]],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("OpenRouter Vision HTTP {$response->status()}: " . $response->body());
        }

        return (string) $response->json('choices.0.message.content', '');
    }

    /**
     * Robust JSON parsing: strips Markdown code fences if present.
     *
     * @return array<string, mixed>|null
     */
    public function parseJson(string $raw): ?array
    {
        $trimmed = trim($raw);

        // Remove ```json ... ``` wrapper if present
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $trimmed, $matches)) {
            $trimmed = trim($matches[1]);
        }

        // Try direct decode first
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback: extract substring between first { and last }
        $firstBrace = strpos($trimmed, '{');
        $lastBrace = strrpos($trimmed, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $sub = substr($trimmed, $firstBrace, $lastBrace - $firstBrace + 1);
            $decoded = json_decode($sub, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function getApiKey(string $provider): string
    {
        return trim((string) config("services.{$provider}.api_key", ''));
    }
}
