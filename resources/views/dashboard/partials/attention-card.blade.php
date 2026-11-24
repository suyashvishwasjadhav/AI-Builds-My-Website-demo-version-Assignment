<section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-slate-900">Needs your attention</h3>
        @if ($openQuestions->isNotEmpty())
            <span class="text-xs font-medium bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full">
                {{ $openQuestions->count() }} open
            </span>
        @endif
    </div>

    @if ($openQuestions->isEmpty())
        <p class="mt-2 text-sm text-slate-500">
            No open questions right now. Any conflicts, missing details, or flagged items will appear here.
        </p>
    @else
        <div class="mt-4 space-y-4">
            @foreach ($openQuestions as $question)
                <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-4 space-y-3">
                    <p class="text-sm font-medium text-amber-950">
                        {{ $question->prompt }}
                    </p>

                    @if ($question->type->value === 'resolve_conflict')
                        <form method="POST" action="{{ route('sites.questions.answer', [$site, $question]) }}" class="space-y-2.5" x-data="{ choice: '' }">
                            @csrf
                            @foreach ($question->options['choices'] ?? [] as $choice)
                                <label class="flex items-start gap-2 text-sm text-slate-800 cursor-pointer">
                                    <input type="radio" name="choice" value="{{ $choice['fact_id'] }}" x-model="choice" class="mt-1 text-indigo-600 focus:ring-indigo-500" required>
                                    <span>
                                        <span class="font-medium">{{ $choice['value'] }}</span>
                                        <span class="text-xs text-slate-500">({{ $choice['source'] }})</span>
                                    </span>
                                </label>
                            @endforeach

                            <label class="flex items-start gap-2 text-sm text-slate-800 cursor-pointer">
                                <input type="radio" name="choice" value="custom" x-model="choice" class="mt-1 text-indigo-600 focus:ring-indigo-500" required>
                                <span>Neither / type the correct one</span>
                            </label>

                            <div x-show="choice === 'custom'" style="display: none;">
                                <input
                                    type="text"
                                    name="custom_answer"
                                    placeholder="Enter the correct value"
                                    class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">
                                    Save answer
                                </button>
                            </div>
                        </form>
                    @elseif ($question->type->value === 'pick_business')
                        <form method="POST" action="{{ route('sites.questions.answer', [$site, $question]) }}" class="space-y-2.5">
                            @csrf
                            @foreach ($question->options ?? [] as $option)
                                <label class="flex items-start gap-2 text-sm text-slate-800 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="selected_source_id"
                                        value="{{ $option['source_id'] ?? 'none' }}"
                                        class="mt-1 text-indigo-600 focus:ring-indigo-500"
                                        required
                                    >
                                    <span>
                                        <span class="font-medium">{{ $option['name'] }}</span>
                                        @if (! empty($option['address']))
                                            <span class="block text-xs text-slate-500">{{ $option['address'] }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                            <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">
                                Confirm selection
                            </button>
                        </form>
                    @elseif ($question->type->value === 'missing_info')
                        <form method="POST" action="{{ route('sites.questions.answer', [$site, $question]) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <input
                                type="text"
                                name="custom_answer"
                                placeholder="Enter value..."
                                required
                                class="flex-1 min-w-[180px] rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <button type="submit" class="px-3 py-1.5 rounded-md text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">
                                Save
                            </button>
                        </form>
                    @elseif ($question->type->value === 'flagged_content')
                        <div class="space-y-1.5">
                            @foreach ($question->options['snippets'] ?? [] as $snippet)
                                <div class="text-xs text-red-800 bg-red-50 border border-red-200 rounded px-2.5 py-1.5 font-mono">
                                    {{ $snippet }}
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('sites.questions.skip', [$site, $question]) }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:text-slate-700 underline">
                            Skip (hide this slot)
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</section>
