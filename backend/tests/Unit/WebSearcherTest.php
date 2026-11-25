<?php

namespace Tests\Unit;

use App\Enums\FactStatus;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Site;
use App\Models\User;
use App\Services\WebSearcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebSearcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_driver_skips_search_gracefully(): void
    {
        config()->set('services.search.driver', 'null');

        $site = Site::factory()->create([
            'business_name' => 'Sunrise Bakery',
            'city' => 'Pune',
        ]);

        $result = app(WebSearcher::class)->searchForSite($site);

        $this->assertTrue($result['skipped']);
        $this->assertStringContainsString('skipped', strtolower($result['message']));
        $this->assertSame([], $result['candidates']);
        $this->assertDatabaseCount('facts', 0);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_two_businesses_with_same_name_create_candidate_facts_and_pick_business_question(): void
    {
        config()->set('services.search.driver', 'google_cse');
        config()->set('services.search.api_key', 'test-key');
        config()->set('services.search.engine_id', 'test-cx');

        Http::fake([
            'www.googleapis.com/customsearch/v1*' => Http::response([
                'items' => [
                    [
                        'title' => 'Sunrise Bakery - Koregaon Park',
                        'link' => 'https://sunrise-kp.example',
                        'snippet' => 'Fresh bread in Pune. Call +91 98200 11111.',
                        'pagemap' => [
                            'postaladdress' => [
                                ['streetaddress' => '12 Lane 5, Koregaon Park, Pune'],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Sunrise Bakery - Kothrud',
                        'link' => 'https://sunrise-kothrud.example',
                        'snippet' => 'Bakery in Kothrud, Pune. Phone 020-25431234.',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create([
            'business_name' => 'Sunrise Bakery',
            'city' => 'Pune',
        ]);

        $result = app(WebSearcher::class)->searchForSite($site);

        $this->assertFalse($result['skipped']);
        $this->assertCount(2, $result['candidates']);

        // Every stored fact must be candidate, never confirmed
        $this->assertGreaterThan(0, $site->facts()->count());
        $this->assertSame(
            0,
            $site->facts()->where('status', FactStatus::Confirmed)->count()
        );
        $this->assertTrue(
            $site->facts()->get()->every(fn ($f) => $f->status === FactStatus::Candidate && $f->source_id !== null)
        );

        // Pick-business question created with both candidates + "None of these"
        $question = $site->questions()->firstOrFail();
        $this->assertSame(QuestionType::PickBusiness, $question->type);
        $this->assertSame(QuestionStatus::Open, $question->status);
        $this->assertCount(3, $question->options);
        $this->assertSame("None of these / it's a different business", $question->options[2]['name']);

        // Dashboard overview shows the pick-one question
        $this->actingAs($user)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee('Which one is yours?');
    }

    public function test_single_strong_match_still_creates_pick_business_question_for_confirmation(): void
    {
        config()->set('services.search.driver', 'places');
        config()->set('services.search.api_key', 'places-key');

        Http::fake([
            'places.googleapis.com/v1/places:searchText' => Http::response([
                'places' => [
                    [
                        'displayName' => ['text' => 'Green Leaf Cafe'],
                        'formattedAddress' => '10 Church Street, Bengaluru',
                        'nationalPhoneNumber' => '080 1234 5678',
                        'websiteUri' => 'https://greenleaf.example',
                        'googleMapsUri' => 'https://maps.google.com/?cid=999',
                    ],
                ],
            ], 200),
        ]);

        $site = Site::factory()->create([
            'business_name' => 'Green Leaf Cafe',
            'city' => 'Bengaluru',
        ]);

        $result = app(WebSearcher::class)->searchForSite($site);

        $this->assertCount(1, $result['candidates']);
        $this->assertDatabaseHas('questions', [
            'site_id' => $site->id,
            'type' => QuestionType::PickBusiness->value,
            'status' => QuestionStatus::Open->value,
        ]);
        $this->assertSame(0, $site->facts()->where('status', FactStatus::Confirmed)->count());
    }

    public function test_quota_limit_429_is_handled_gracefully(): void
    {
        config()->set('services.search.driver', 'google_cse');
        config()->set('services.search.api_key', 'test-key');
        config()->set('services.search.engine_id', 'test-cx');

        Http::fake([
            '*' => Http::response(['error' => 'Rate Limit Exceeded'], 429),
        ]);

        $site = Site::factory()->create([
            'business_name' => 'Test Biz',
            'city' => 'Mumbai',
        ]);

        $result = app(WebSearcher::class)->searchForSite($site);

        $this->assertFalse($result['skipped']);
        $this->assertSame([], $result['candidates']);
        $this->assertDatabaseCount('facts', 0);
        $this->assertDatabaseCount('questions', 0);
    }
}
