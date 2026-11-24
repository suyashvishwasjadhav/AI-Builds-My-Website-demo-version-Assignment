<?php

namespace App\Services;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SourceType;
use App\Models\Fact;
use App\Models\Question;
use App\Models\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Verifier
{
    public const SINGLE_VALUE_KEYS = [
        'phone',
        'email',
        'address',
        'hours',
        'business_name',
    ];

    public function verify(Site $site): void
    {
        $activeFacts = $site->facts()
            ->with('source')
            ->where('status', '!=', FactStatus::Rejected)
            ->get();

        $hasOpenPickBusiness = $site->questions()
            ->where('type', QuestionType::PickBusiness)
            ->where('status', QuestionStatus::Open)
            ->exists();

        $grouped = $activeFacts->groupBy('key');

        foreach (self::SINGLE_VALUE_KEYS as $key) {
            /** @var Collection<int, Fact> $factsForKey */
            $factsForKey = $grouped->get($key, collect());
            if ($factsForKey->isEmpty()) {
                continue;
            }

            $distinctByNorm = $factsForKey->groupBy(
                fn (Fact $fact) => $this->normalizeValue($key, $fact->value)
            );

            if ($distinctByNorm->count() > 1) {
                $this->handleConflict($site, $key, $factsForKey);
            } elseif (! $hasOpenPickBusiness) {
                foreach ($factsForKey as $fact) {
                    if ($fact->status === FactStatus::Candidate && $fact->source?->type !== SourceType::Search) {
                        $fact->update(['status' => FactStatus::Confirmed]);
                    }
                }
            }
        }

        // Confirm non-single-value facts from non-search sources when no pick_business is open
        if (! $hasOpenPickBusiness) {
            foreach ($activeFacts as $fact) {
                if (
                    ! in_array($fact->key, self::SINGLE_VALUE_KEYS, true)
                    && $fact->status === FactStatus::Candidate
                    && $fact->source?->type !== SourceType::Search
                ) {
                    $fact->update(['status' => FactStatus::Confirmed]);
                }
            }
        }

        $this->checkMissingInfo($site, $activeFacts);
    }

    public function normalizeValue(string $key, string $value): string
    {
        $trimmed = trim($value);

        if ($key === 'phone') {
            $digits = (string) preg_replace('/\D+/', '', $trimmed);
            if (strlen($digits) > 10) {
                if (str_starts_with($digits, '91') && strlen($digits) === 12) {
                    return substr($digits, 2);
                }
                if (str_starts_with($digits, '0') && strlen($digits) === 11) {
                    return substr($digits, 1);
                }
            }

            return $digits;
        }

        $normalized = strtolower((string) preg_replace('/\s+/u', ' ', $trimmed));

        return rtrim($normalized, '.,;');
    }

    public function answerQuestion(Question $question, ?int $selectedFactId = null, ?string $customAnswer = null, ?int $selectedSourceId = null): void
    {
        DB::transaction(function () use ($question, $selectedFactId, $customAnswer, $selectedSourceId): void {
            $site = $question->site;

            if ($question->type === QuestionType::ResolveConflict) {
                $key = (string) ($question->options['key'] ?? '');

                if ($customAnswer !== null && trim($customAnswer) !== '') {
                    $clean = trim($customAnswer);

                    $site->facts()
                        ->where('key', $key)
                        ->where('status', '!=', FactStatus::Rejected)
                        ->update(['status' => FactStatus::Rejected]);

                    $site->facts()->create([
                        'key' => $key,
                        'value' => $clean,
                        'status' => FactStatus::UserProvided,
                        'confidence' => FactConfidence::High,
                    ]);

                    $question->update([
                        'status' => QuestionStatus::Answered,
                        'answer' => $clean,
                    ]);

                    return;
                }

                if ($selectedFactId !== null) {
                    $chosen = $site->facts()->where('id', $selectedFactId)->firstOrFail();
                    $chosen->update(['status' => FactStatus::Confirmed]);

                    $site->facts()
                        ->where('key', $chosen->key)
                        ->where('id', '!=', $chosen->id)
                        ->update(['status' => FactStatus::Rejected]);

                    $question->update([
                        'status' => QuestionStatus::Answered,
                        'answer' => $chosen->value,
                    ]);
                }

                return;
            }

            if ($question->type === QuestionType::MissingInfo) {
                $clean = trim((string) $customAnswer);
                if ($clean === '') {
                    return;
                }

                $key = (string) ($question->options['key'] ?? 'other');

                $site->facts()->create([
                    'key' => $key,
                    'value' => $clean,
                    'status' => FactStatus::UserProvided,
                    'confidence' => FactConfidence::High,
                ]);

                $question->update([
                    'status' => QuestionStatus::Answered,
                    'answer' => $clean,
                ]);

                return;
            }

            if ($question->type === QuestionType::PickBusiness) {
                $candidateSourceIds = collect($question->options ?? [])
                    ->pluck('source_id')
                    ->filter()
                    ->values()
                    ->all();

                if ($selectedSourceId !== null && in_array($selectedSourceId, $candidateSourceIds, true)) {
                    $site->facts()
                        ->where('source_id', $selectedSourceId)
                        ->update(['status' => FactStatus::Confirmed]);

                    $otherIds = array_values(array_diff($candidateSourceIds, [$selectedSourceId]));
                    if ($otherIds !== []) {
                        $site->facts()
                            ->whereIn('source_id', $otherIds)
                            ->update(['status' => FactStatus::Rejected]);
                    }

                    $question->update([
                        'status' => QuestionStatus::Answered,
                        'answer' => (string) $selectedSourceId,
                    ]);
                } else {
                    if ($candidateSourceIds !== []) {
                        $site->facts()
                            ->whereIn('source_id', $candidateSourceIds)
                            ->update(['status' => FactStatus::Rejected]);
                    }

                    $question->update([
                        'status' => QuestionStatus::Answered,
                        'answer' => 'none',
                    ]);
                }
            }
        });
    }

    public function skipQuestion(Question $question): void
    {
        $question->update([
            'status' => QuestionStatus::Skipped,
        ]);
    }

    /**
     * @param Collection<int, Fact> $factsForKey
     */
    private function handleConflict(Site $site, string $key, Collection $factsForKey): void
    {
        Fact::query()
            ->whereIn('id', $factsForKey->pluck('id'))
            ->update(['status' => FactStatus::Conflict]);

        $alreadyOpen = $site->questions()
            ->where('type', QuestionType::ResolveConflict)
            ->where('status', QuestionStatus::Open)
            ->get()
            ->contains(fn (Question $q) => ($q->options['key'] ?? null) === $key);

        if ($alreadyOpen) {
            return;
        }

        $choices = $factsForKey
            ->unique(fn (Fact $f) => $this->normalizeValue($key, $f->value))
            ->map(fn (Fact $f) => [
                'fact_id' => $f->id,
                'value' => $f->value,
                'source' => $f->source?->label ?: 'Direct input',
            ])
            ->values()
            ->all();

        $label = str_replace('_', ' ', $key);

        Question::create([
            'site_id' => $site->id,
            'type' => QuestionType::ResolveConflict,
            'prompt' => "We found different values for {$label}. Which one is correct?",
            'options' => [
                'key' => $key,
                'choices' => $choices,
                'allow_custom' => true,
            ],
            'status' => QuestionStatus::Open,
        ]);
    }

    /**
     * @param Collection<int, Fact> $activeFacts
     */
    private function checkMissingInfo(Site $site, Collection $activeFacts): void
    {
        $keysPresent = $activeFacts->pluck('key')->unique()->all();

        $checks = [];
        if (! in_array('phone', $keysPresent, true) && ! in_array('email', $keysPresent, true)) {
            $checks['phone'] = 'We could not find a phone number or email address. Please enter your phone number (or skip to hide it).';
        }
        if (! in_array('address', $keysPresent, true)) {
            $checks['address'] = 'We could not find your business address. Please enter it (or skip to hide it).';
        }
        if (! in_array('category', $keysPresent, true)) {
            $checks['category'] = 'What type of business is this? (e.g. Bakery, Dental Clinic, Salon)';
        }

        $existingMissing = $site->questions()
            ->where('type', QuestionType::MissingInfo)
            ->whereIn('status', [QuestionStatus::Open, QuestionStatus::Skipped, QuestionStatus::Answered])
            ->get();

        // Auto-close missing info questions if facts have been extracted
        foreach ($existingMissing->where('status', QuestionStatus::Open) as $q) {
            $key = (string) ($q->options['key'] ?? '');
            if ($key === 'address' && in_array('address', $keysPresent, true)) {
                $q->update(['status' => QuestionStatus::Answered, 'answer' => 'Found in sources']);
            } elseif ($key === 'phone' && (in_array('phone', $keysPresent, true) || in_array('email', $keysPresent, true))) {
                $q->update(['status' => QuestionStatus::Answered, 'answer' => 'Found in sources']);
            } elseif ($key !== '' && $key !== 'address' && $key !== 'phone' && in_array($key, $keysPresent, true)) {
                $q->update(['status' => QuestionStatus::Answered, 'answer' => 'Found in sources']);
            }
        }

        foreach ($checks as $key => $prompt) {
            $alreadyExists = $existingMissing->contains(
                fn (Question $q) => ($q->options['key'] ?? null) === $key
            );

            if (! $alreadyExists) {
                Question::create([
                    'site_id' => $site->id,
                    'type' => QuestionType::MissingInfo,
                    'prompt' => $prompt,
                    'options' => ['key' => $key],
                    'status' => QuestionStatus::Open,
                ]);
            }
        }
    }
}
