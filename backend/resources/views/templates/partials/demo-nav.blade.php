@if (! empty($isTemplateDemo))
    <div class="bg-slate-900 text-white border-b border-slate-800 px-4 py-1.5 text-[11px] font-medium">
        <div class="max-w-5xl mx-auto flex flex-wrap items-center justify-between gap-2">
            <a href="{{ url('/') }}" class="text-slate-300 hover:text-white transition">
                &larr; Back to Home
            </a>
            <div class="inline-flex items-center gap-1">
                <span class="text-slate-400 mr-1.5">Template Demo:</span>
                @foreach (['clean' => 'Clean', 'bold' => 'Bold', 'warm' => 'Warm'] as $key => $label)
                    <a
                        href="{{ route('templates.demo', $key) }}"
                        class="px-2.5 py-0.5 rounded-md transition {{ $template === $key ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-800' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
