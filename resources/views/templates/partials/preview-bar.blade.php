@if ($isPreview)
    <div class="sticky top-0 z-50 bg-slate-900 text-white px-4 py-3 border-b border-slate-700">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('sites.show', $site) }}" class="text-xs font-medium text-slate-300 hover:text-white">
                    &larr; Back to dashboard
                </a>
                <span class="text-xs px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 font-medium">
                    Preview mode
                </span>
            </div>

            <form method="POST" action="{{ route('sites.template.update', $site) }}" class="flex items-center gap-2">
                @csrf
                @method('PATCH')
                <label for="template" class="text-xs text-slate-300">Theme:</label>
                <select
                    id="template"
                    name="template"
                    onchange="this.form.submit()"
                    class="rounded-md bg-slate-800 border-slate-600 text-xs text-white py-1 px-2.5 focus:border-indigo-400 focus:ring-indigo-400"
                >
                    <option value="clean" @selected($template === 'clean')>Clean</option>
                    <option value="bold" @selected($template === 'bold')>Bold</option>
                    <option value="warm" @selected($template === 'warm')>Warm</option>
                </select>
            </form>
        </div>
    </div>
@endif
