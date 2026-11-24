<?php

namespace Database\Factories;

use App\Models\AiRun;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRun>
 */
class AiRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'purpose' => 'write_site',
            'status' => 'success',
            'error' => null,
            'duration_ms' => fake()->numberBetween(100, 2000),
        ];
    }
}
