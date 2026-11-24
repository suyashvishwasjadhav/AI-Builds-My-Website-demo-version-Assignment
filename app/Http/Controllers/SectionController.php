<?php

namespace App\Http\Controllers;

use App\Enums\SectionCreatedBy;
use App\Enums\SectionType;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Site;
use App\Services\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(Site $site): View
    {
        Gate::authorize('view', $site);

        $sections = $site->sections()
            ->with(['versions' => fn ($q) => $q->latest('id'), 'currentVersion'])
            ->get();

        return view('dashboard.sections', [
            'site' => $site,
            'sections' => $sections,
        ]);
    }

    public function update(Request $request, Site $site, Section $section): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($section->site_id === $site->id, 404);

        $content = $this->validatedContentForSection($request, $section);

        $version = SectionVersion::create([
            'section_id' => $section->id,
            'content' => $content,
            'created_by' => SectionCreatedBy::User,
            'note' => 'Edited by owner',
        ]);

        $section->update([
            'content' => $content,
            'current_version_id' => $version->id,
        ]);

        return redirect()
            ->route('sites.sections', $site)
            ->with('status', ucfirst($section->type->value).' section updated.');
    }

    public function regenerate(Request $request, Site $site, Section $section, Writer $writer): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($section->site_id === $site->id, 404);

        $validated = $request->validate([
            'instruction' => ['nullable', 'string', 'max:500'],
        ]);

        $ok = $writer->regenerate($site, $section, $validated['instruction'] ?? null);

        if (! $ok) {
            return redirect()
                ->route('sites.sections', $site)
                ->with('error', 'Could not regenerate this section right now. Your current text was kept.');
        }

        return redirect()
            ->route('sites.sections', $site)
            ->with('status', ucfirst($section->type->value).' section regenerated.');
    }

    public function toggle(Site $site, Section $section): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($section->site_id === $site->id, 404);

        $section->update(['is_visible' => ! $section->is_visible]);

        return redirect()
            ->route('sites.sections', $site)
            ->with('status', ucfirst($section->type->value).' visibility updated.');
    }

    public function restore(Site $site, Section $section, SectionVersion $version): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($section->site_id === $site->id && $version->section_id === $section->id, 404);

        $section->update([
            'content' => $version->content,
            'current_version_id' => $version->id,
        ]);

        return redirect()
            ->route('sites.sections', $site)
            ->with('status', 'Version restored.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedContentForSection(Request $request, Section $section): array
    {
        $existing = $section->content ?? [];

        if ($section->type === SectionType::Hero) {
            $data = $request->validate([
                'headline' => ['required', 'string', 'max:255'],
                'subheadline' => ['nullable', 'string', 'max:500'],
                'seo_title' => ['nullable', 'string', 'max:100'],
                'seo_description' => ['nullable', 'string', 'max:255'],
            ]);

            return [
                'headline' => $data['headline'],
                'subheadline' => (string) ($data['subheadline'] ?? ''),
                'seo_title' => (string) ($data['seo_title'] ?? $existing['seo_title'] ?? $data['headline']),
                'seo_description' => (string) ($data['seo_description'] ?? $existing['seo_description'] ?? ''),
            ];
        }

        if ($section->type === SectionType::About) {
            $data = $request->validate([
                'heading' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string', 'max:5000'],
            ]);

            return [
                'heading' => $data['heading'],
                'body' => $data['body'],
            ];
        }

        if ($section->type === SectionType::Services) {
            $data = $request->validate([
                'heading' => ['required', 'string', 'max:255'],
                'items_text' => ['nullable', 'string', 'max:5000'],
            ]);

            $items = [];
            foreach (preg_split('/\r\n|\r|\n/', (string) ($data['items_text'] ?? '')) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line, 2));
                $items[] = [
                    'name' => $parts[0],
                    'description' => $parts[1] ?? '',
                ];
            }

            return [
                'heading' => $data['heading'],
                'items' => $items,
            ];
        }

        $data = $request->validate([
            'heading' => ['required', 'string', 'max:255'],
            'intro' => ['required', 'string', 'max:2000'],
        ]);

        return [
            'heading' => $data['heading'],
            'intro' => $data['intro'],
        ];
    }
}
