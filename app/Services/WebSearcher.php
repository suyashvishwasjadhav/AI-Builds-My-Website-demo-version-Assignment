<?php

namespace App\Services;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Question;
use App\Models\Site;
use App\Services\Contracts\SearchDriver;

class WebSearcher
{
    public function __construct(
        private readonly SearchDriver $driver,
    ) {
    }

    /**
     * Search the web for candidate businesses, store candidate facts (never confirmed),
     * and create a pick_business question for owner confirmation.
     *
     * @return array{skipped: bool, message: string, candidates: array}
     */
    public function searchForSite(Site $site): array
    {
        if (! $this->driver->isConfigured()) {
            return [
                'skipped' => true,
                'message' => 'Web search was skipped because no search driver is configured.',
                'candidates' => [],
            ];
        }

        $candidates = $this->driver->search($site->business_name, $site->city);

        if ($candidates === []) {
            return [
                'skipped' => false,
                'message' => 'No matching businesses were found on the web.',
                'candidates' => [],
            ];
        }

        $options = [];

        foreach ($candidates as $candidate) {
            $summaryParts = array_filter([
                "Name: {$candidate['name']}",
                ! empty($candidate['address']) ? "Address: {$candidate['address']}" : null,
                ! empty($candidate['phone']) ? "Phone: {$candidate['phone']}" : null,
                ! empty($candidate['website']) ? "Website: {$candidate['website']}" : null,
                "Source: {$candidate['source_url']}",
            ]);

            $source = $site->sources()->create([
                'type' => SourceType::Search,
                'label' => $candidate['name'],
                'path_or_url' => $candidate['source_url'],
                'status' => SourceStatus::Read,
                'raw_text' => implode("\n", $summaryParts),
            ]);

            $factPairs = array_filter([
                'business_name' => $candidate['name'],
                'address' => $candidate['address'] ?? null,
                'phone' => $candidate['phone'] ?? null,
                'website' => $candidate['website'] ?? null,
            ]);

            foreach ($factPairs as $key => $value) {
                $site->facts()->create([
                    'source_id' => $source->id,
                    'key' => $key,
                    'value' => $value,
                    'status' => FactStatus::Candidate,
                    'confidence' => FactConfidence::Medium,
                ]);
            }

            $options[] = [
                'source_id' => $source->id,
                'name' => $candidate['name'],
                'address' => $candidate['address'],
                'phone' => $candidate['phone'],
                'website' => $candidate['website'],
                'source_url' => $candidate['source_url'],
            ];
        }

        $options[] = [
            'source_id' => null,
            'name' => "None of these / it's a different business",
            'address' => null,
            'phone' => null,
            'website' => null,
            'source_url' => null,
        ];

        $prompt = count($candidates) > 1
            ? "We found multiple businesses matching \"{$site->business_name}\" in {$site->city}. Which one is yours?"
            : "We found a business matching \"{$site->business_name}\" in {$site->city}. Please confirm if this is yours.";

        Question::create([
            'site_id' => $site->id,
            'type' => QuestionType::PickBusiness,
            'prompt' => $prompt,
            'options' => $options,
            'status' => QuestionStatus::Open,
        ]);

        return [
            'skipped' => false,
            'message' => count($candidates).' candidate(s) found.',
            'candidates' => $candidates,
        ];
    }
}
