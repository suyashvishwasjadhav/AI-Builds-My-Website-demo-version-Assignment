<?php

namespace App\Services;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Source;

class SafetyGuard
{
    /**
     * Extensible list of regex patterns for hidden or instruction-like content.
     *
     * @var list<string>
     */
    public const PATTERNS = [
        '/\bignore\s+(?:all\s+|any\s+)?(?:previous|prior|above|your)?\s*(?:instructions|rules|prompts?)\b/i',
        '/\byou\s+are\s+now\b/i',
        '/\bsystem\s+prompt\b/i',
        '/\b(?:reveal|show|print|output|leak)\s+(?:your\s+)?(?:api\s*key|key|secret|prompt|instructions)\b/i',
        '/\b(?:write|say|claim|state|pretend)\s+(?:that\s+)?(?:we\s+are|this\s+business\s+is)\s+(?:the\s+)?(?:number\s+one|#1|best\s+in\b)/i',
        '/\b(?:override|bypass|disregard|forget)\s+(?:all\s+)?(?:rules|constraints|guidelines|instructions)\b/i',
        '/\b(?:change|switch)\s+(?:your\s+)?tone\s+to\b/i',
        '/<!--[\s\S]*?(?:ignore|instruction|system|prompt|override)[\s\S]*?-->/i',
    ];

    /**
     * Scan raw text for suspicious instruction-like content, flag it in questions,
     * and return sanitized text with flagged snippets stripped.
     */
    public function sanitize(Source $source): string
    {
        $rawText = (string) $source->raw_text;
        if (trim($rawText) === '') {
            return '';
        }

        // Strip zero-width characters often used to hide text
        $cleaned = (string) preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $rawText);

        $flaggedSnippets = [];
        $keptLines = [];

        foreach (preg_split('/\r\n|\r|\n/', $cleaned) as $line) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $line) ?: [$line];
            $keptSentences = [];

            foreach ($sentences as $sentence) {
                if ($this->isSuspicious($sentence)) {
                    $trimmed = trim($sentence);
                    if ($trimmed !== '') {
                        $flaggedSnippets[] = $trimmed;
                    }
                } else {
                    $keptSentences[] = $sentence;
                }
            }

            $rebuiltLine = trim(implode(' ', $keptSentences));
            if ($rebuiltLine !== '') {
                $keptLines[] = $rebuiltLine;
            }
        }

        if ($flaggedSnippets !== []) {
            $this->recordFlag($source, $flaggedSnippets);
        }

        return implode("\n", $keptLines);
    }

    public function isSuspicious(string $text): bool
    {
        foreach (self::PATTERNS as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $snippets
     */
    private function recordFlag(Source $source, array $snippets): void
    {
        $label = $source->label ?: "Source #{$source->id}";

        $alreadyOpen = Question::query()
            ->where('site_id', $source->site_id)
            ->where('type', QuestionType::FlaggedContent)
            ->where('status', QuestionStatus::Open)
            ->get()
            ->contains(fn (Question $q) => ($q->options['source_id'] ?? null) === $source->id);

        if ($alreadyOpen) {
            return;
        }

        Question::create([
            'site_id' => $source->site_id,
            'type' => QuestionType::FlaggedContent,
            'prompt' => "Suspicious instruction-like text was found in \"{$label}\" and was stripped before AI processing.",
            'options' => [
                'source_id' => $source->id,
                'snippets' => array_values(array_unique($snippets)),
            ],
            'status' => QuestionStatus::Open,
        ]);
    }
}
