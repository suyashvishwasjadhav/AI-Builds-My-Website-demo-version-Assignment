<?php

namespace Database\Factories;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use App\Models\Fact;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fact>
 */
class FactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'source_id' => null,
            'key' => fake()->randomElement(['phone', 'address', 'hours', 'service']),
            'value' => fake()->sentence(3),
            'status' => FactStatus::Candidate,
            'confidence' => FactConfidence::Medium,
        ];
    }
}
