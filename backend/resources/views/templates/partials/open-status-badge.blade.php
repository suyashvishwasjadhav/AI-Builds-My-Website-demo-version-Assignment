@if (! empty($openStatus))
    <div class="inline-flex items-center gap-2 rounded-lg px-3 py-1 text-xs font-medium {{ $openStatus['is_open'] ? 'bg-emerald-500/15 text-emerald-700 ring-1 ring-emerald-500/30' : 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-400/30' }}">
        <span class="h-2 w-2 rounded-full {{ $openStatus['is_open'] ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}" aria-hidden="true"></span>
        <span>{{ $openStatus['label'] }}</span>
    </div>
@endif
