<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Site;
use App\Services\Verifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function answer(Request $request, Site $site, Question $question, Verifier $verifier): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($question->site_id === $site->id, 404);

        if ($question->type === QuestionType::ResolveConflict) {
            $validated = $request->validate([
                'choice' => ['required', 'string'],
                'custom_answer' => ['nullable', 'string', 'max:1000', 'required_if:choice,custom'],
            ]);

            if ($validated['choice'] === 'custom') {
                $verifier->answerQuestion($question, customAnswer: $validated['custom_answer']);
            } else {
                $verifier->answerQuestion($question, selectedFactId: (int) $validated['choice']);
            }
        } elseif ($question->type === QuestionType::MissingInfo) {
            $validated = $request->validate([
                'custom_answer' => ['required', 'string', 'max:1000'],
            ]);

            $verifier->answerQuestion($question, customAnswer: $validated['custom_answer']);
        } elseif ($question->type === QuestionType::PickBusiness) {
            $validated = $request->validate([
                'selected_source_id' => ['nullable', 'string'],
            ]);

            $sourceId = ($validated['selected_source_id'] ?? '') !== '' && ($validated['selected_source_id'] ?? '') !== 'none'
                ? (int) $validated['selected_source_id']
                : null;

            $verifier->answerQuestion($question, selectedSourceId: $sourceId);
        }

        return redirect()
            ->route('sites.show', $site)
            ->with('status', 'Answer saved.');
    }

    public function skip(Site $site, Question $question, Verifier $verifier): RedirectResponse
    {
        Gate::authorize('update', $site);
        abort_unless($question->site_id === $site->id, 404);

        $verifier->skipQuestion($question);

        return redirect()
            ->route('sites.show', $site)
            ->with('status', 'Question skipped.');
    }
}
