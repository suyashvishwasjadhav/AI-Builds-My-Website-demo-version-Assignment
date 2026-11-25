<?php

namespace App\Services;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Enums\SiteStatus;
use App\Models\Site;
use DomainException;

class Publisher
{
    public function canPublish(Site $site): bool
    {
        return $this->publishBlockReason($site) === null;
    }

    public function publishBlockReason(Site $site): ?string
    {
        $hasOpenBlockingQuestions = $site->questions()
            ->where('status', QuestionStatus::Open)
            ->whereIn('type', [QuestionType::PickBusiness, QuestionType::ResolveConflict])
            ->exists();

        if ($hasOpenBlockingQuestions) {
            return 'Resolve open business selection or conflict questions before publishing.';
        }

        if (! $site->sections()->exists()) {
            return 'Generate website sections before publishing.';
        }

        return null;
    }

    public function publish(Site $site): Site
    {
        if ($reason = $this->publishBlockReason($site)) {
            throw new DomainException($reason);
        }

        $site->update([
            'status' => SiteStatus::Published,
            'published_at' => now(),
        ]);

        return $site->fresh();
    }

    public function unpublish(Site $site): Site
    {
        $site->update([
            'status' => $site->sections()->exists() ? SiteStatus::Ready : SiteStatus::Draft,
            'published_at' => null,
        ]);

        return $site->fresh();
    }

    public function updateSlug(Site $site, string $slug): Site
    {
        $clean = strtolower(trim($slug));

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $clean)) {
            throw new DomainException('Slug may only contain lowercase letters, numbers, and hyphens.');
        }

        $exists = Site::where('slug', $clean)
            ->where('id', '!=', $site->id)
            ->exists();

        if ($exists) {
            throw new DomainException('This public URL slug is already taken.');
        }

        $site->update(['slug' => $clean]);

        return $site->fresh();
    }
}
