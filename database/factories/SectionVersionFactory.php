<?php

namespace Database\Factories;

use App\Enums\SectionCreatedBy;
use App\Models\Section;
use App\Models\SectionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionVersion>
 */
class SectionVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'content' => ['headline' => fake()->sentence(4)],
            'created_by' => SectionCreatedBy::Ai,
            'note' => null,
        ];
    }
}
