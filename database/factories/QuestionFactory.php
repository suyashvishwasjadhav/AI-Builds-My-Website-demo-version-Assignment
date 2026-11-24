<?php

namespace Database\Factories;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'type' => QuestionType::MissingInfo,
            'prompt' => fake()->sentence(),
            'options' => [],
            'status' => QuestionStatus::Open,
            'answer' => null,
        ];
    }
}
