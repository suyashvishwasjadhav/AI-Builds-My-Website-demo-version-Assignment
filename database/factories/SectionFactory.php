<?php

namespace Database\Factories;

use App\Enums\SectionType;
use App\Models\Section;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'type' => SectionType::Hero,
            'content' => ['headline' => fake()->sentence(4)],
            'is_visible' => true,
            'current_version_id' => null,
        ];
    }
}
