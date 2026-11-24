<?php

namespace Tests\Unit;

use App\Enums\SiteStatus;
use App\Models\AiRun;
use App\Models\Enquiry;
use App\Models\Fact;
use App\Models\Media;
use App\Models\Question;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Site;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_relationships_and_enum_casts_work(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        $source = Source::factory()->for($site)->create();
        $fact = Fact::factory()->for($site)->for($source)->create();
        $question = Question::factory()->for($site)->create();
        $section = Section::factory()->for($site)->create();
        $version = SectionVersion::factory()->for($section)->create();
        $section->update(['current_version_id' => $version->id]);
        $media = Media::factory()->for($site)->create();
        $enquiry = Enquiry::factory()->for($site)->create();
        $aiRun = AiRun::factory()->for($site)->create();

        $this->assertSame(SiteStatus::Draft, $site->status);
        $this->assertTrue($user->sites->contains($site));
        $this->assertTrue($site->sources->contains($source));
        $this->assertTrue($site->facts->contains($fact));
        $this->assertTrue($site->questions->contains($question));
        $this->assertTrue($site->sections->contains($section));
        $this->assertTrue($section->versions->contains($version));
        $this->assertEquals($version->id, $section->fresh()->currentVersion->id);
        $this->assertTrue($site->media->contains($media));
        $this->assertTrue($site->enquiries->contains($enquiry));
        $this->assertTrue($site->aiRuns->contains($aiRun));
    }
}
