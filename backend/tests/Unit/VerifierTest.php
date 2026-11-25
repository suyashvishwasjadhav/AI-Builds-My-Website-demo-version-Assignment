<?php

namespace Tests\Unit;

use App\Enums\FactStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Fact;
use App\Models\Site;
use App\Models\Source;
use App\Services\Verifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifierTest extends TestCase
{
    use RefreshDatabase;

    private Verifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new Verifier();
    }

    public function test_two_different_phone_numbers_produce_one_conflict_question(): void
    {
        $site = Site::factory()->create();
        $source1 = Source::factory()->for($site)->create(['label' => 'menu.pdf']);
        $source2 = Source::factory()->for($site)->create(['label' => 'website.com']);

        $fact1 = Fact::factory()->for($site)->for($source1)->create([
            'key' => 'phone',
            'value' => '9820012345',
            'status' => FactStatus::Candidate,
        ]);
        $fact2 = Fact::factory()->for($site)->for($source2)->create([
            'key' => 'phone',
            'value' => '9820099999',
            'status' => FactStatus::Candidate,
        ]);

        // Also provide address & category so only phone conflict is tested cleanly
        Fact::factory()->for($site)->create(['key' => 'address', 'value' => '12 MG Road, Pune']);
        Fact::factory()->for($site)->create(['key' => 'category', 'value' => 'Bakery']);

        $this->verifier->verify($site);

        $this->assertSame(FactStatus::Conflict, $fact1->fresh()->status);
        $this->assertSame(FactStatus::Conflict, $fact2->fresh()->status);

        $conflictQuestions = $site->questions()
            ->where('type', QuestionType::ResolveConflict)
            ->get();

        $this->assertCount(1, $conflictQuestions);
        $this->assertSame('phone', $conflictQuestions->first()->options['key']);
        $this->assertCount(2, $conflictQuestions->first()->options['choices']);
    }

    public function test_same_phone_number_in_two_formats_does_not_produce_conflict(): void
    {
        $site = Site::factory()->create();

        Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '+91 98200 12345',
            'status' => FactStatus::Candidate,
        ]);
        Fact::factory()->for($site)->create([
            'key' => 'phone',
            'value' => '9820012345',
            'status' => FactStatus::Candidate,
        ]);
        Fact::factory()->for($site)->create(['key' => 'address', 'value' => '12 MG Road, Pune']);
        Fact::factory()->for($site)->create(['key' => 'category', 'value' => 'Bakery']);

        $this->verifier->verify($site);

        $this->assertSame(
            0,
            $site->questions()->where('type', QuestionType::ResolveConflict)->count()
        );
        $this->assertSame(
            2,
            $site->facts()->where('key', 'phone')->where('status', FactStatus::Confirmed)->count()
        );
    }

    public function test_missing_required_template_keys_create_missing_info_questions(): void
    {
        $site = Site::factory()->create();

        $this->verifier->verify($site);

        $missing = $site->questions()
            ->where('type', QuestionType::MissingInfo)
            ->get();

        $keys = $missing->map(fn ($q) => $q->options['key'] ?? null)->all();
        $this->assertContains('phone', $keys);
        $this->assertContains('address', $keys);
        $this->assertContains('category', $keys);
    }
}
