<?php

namespace Tests\Feature;

use App\Enums\FactStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Fact;
use App\Models\Question;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_answering_conflict_confirms_chosen_fact_and_rejects_other(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();

        $fact1 = Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '9820012345',
            'status' => FactStatus::Conflict,
        ]);
        $fact2 = Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '9820099999',
            'status' => FactStatus::Conflict,
        ]);

        $question = Question::factory()->for($site)->create([
            'type' => QuestionType::ResolveConflict,
            'prompt' => 'Which phone is correct?',
            'options' => [
                'key' => 'phone',
                'choices' => [
                    ['fact_id' => $fact1->id, 'value' => $fact1->value, 'source' => 'PDF'],
                    ['fact_id' => $fact2->id, 'value' => $fact2->value, 'source' => 'Web'],
                ],
                'allow_custom' => true,
            ],
            'status' => QuestionStatus::Open,
        ]);

        $this->actingAs($user)
            ->post(route('sites.questions.answer', [$site, $question]), [
                'choice' => (string) $fact1->id,
            ])
            ->assertRedirect(route('sites.show', $site));

        $this->assertSame(FactStatus::Confirmed, $fact1->fresh()->status);
        $this->assertSame(FactStatus::Rejected, $fact2->fresh()->status);
        $this->assertSame(QuestionStatus::Answered, $question->fresh()->status);
        $this->assertSame('9820012345', $question->fresh()->answer);
    }

    public function test_answering_conflict_with_custom_value_rejects_candidates_and_creates_user_provided(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();

        $fact1 = Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '1111111111',
            'status' => FactStatus::Conflict,
        ]);
        $fact2 = Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '2222222222',
            'status' => FactStatus::Conflict,
        ]);

        $question = Question::factory()->for($site)->create([
            'type' => QuestionType::ResolveConflict,
            'options' => ['key' => 'phone', 'choices' => []],
            'status' => QuestionStatus::Open,
        ]);

        $this->actingAs($user)
            ->post(route('sites.questions.answer', [$site, $question]), [
                'choice' => 'custom',
                'custom_answer' => '9820055555',
            ])
            ->assertRedirect(route('sites.show', $site));

        $this->assertSame(FactStatus::Rejected, $fact1->fresh()->status);
        $this->assertSame(FactStatus::Rejected, $fact2->fresh()->status);
        $this->assertDatabaseHas('facts', [
            'site_id' => $site->id,
            'key' => 'phone',
            'value' => '9820055555',
            'status' => FactStatus::UserProvided->value,
        ]);
    }

    public function test_owner_can_skip_question_and_non_owner_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $site = Site::factory()->for($owner)->create();
        $question = Question::factory()->for($site)->create([
            'type' => QuestionType::MissingInfo,
            'status' => QuestionStatus::Open,
        ]);

        $this->actingAs($other)
            ->post(route('sites.questions.skip', [$site, $question]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('sites.questions.skip', [$site, $question]))
            ->assertRedirect(route('sites.show', $site));

        $this->assertSame(QuestionStatus::Skipped, $question->fresh()->status);
    }
}
