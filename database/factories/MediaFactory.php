<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'path' => 'uploads/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(3),
            'is_hero' => false,
            'is_placeholder' => false,
        ];
    }
}
