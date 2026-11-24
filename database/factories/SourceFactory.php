<?php

namespace Database\Factories;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Site;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'type' => SourceType::Link,
            'label' => fake()->domainWord(),
            'path_or_url' => fake()->url(),
            'status' => SourceStatus::Pending,
            'raw_text' => null,
            'error_message' => null,
        ];
    }
}
