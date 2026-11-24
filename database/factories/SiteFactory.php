<?php

namespace Database\Factories;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id' => User::factory(),
            'business_name' => $name,
            'city' => fake()->city(),
            'slug' => Str::slug($name).'-'.fake()->unique()->lexify('????'),
            'status' => SiteStatus::Draft,
            'template' => 'clean',
            'language' => 'en',
            'published_at' => null,
        ];
    }
}
