@extends('dashboard.layout', ['site' => $site, 'activeTab' => 'media'])

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Media &amp; Header Background</h2>
            <p class="mt-1 text-sm text-slate-600">
                Upload photos for your header background and gallery cards. Choose any photo as the header background with 1 click.
            </p>
        </div>
        <a href="{{ route('sites.studio', $site) }}" class="inline-flex items-center px-3.5 py-2 rounded-md text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800">
            Open Split-Screen Studio
        </a>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Upload Card -->
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h3 class="text-base font-semibold text-slate-900">Upload Images</h3>
        <form method="POST" action="{{ route('sites.media.store', $site) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="photos" class="block text-xs font-medium text-slate-700">Select Images (JPG, PNG, WEBP, up to 10 MB each)</label>
                    <input
                        id="photos"
                        name="photos[]"
                        type="file"
                        multiple
                        required
                        accept=".jpg,.jpeg,.png,.webp,image/*"
                        class="mt-1.5 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                    >
                </div>
                <div>
                    <label for="alt_text" class="block text-xs font-medium text-slate-700">Caption / Alt Text (optional)</label>
                    <input
                        id="alt_text"
                        name="alt_text"
                        type="text"
                        placeholder="e.g. Our workshop in Mumbai"
                        class="mt-1.5 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" name="set_as_hero" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>Set first uploaded photo as Header Background</span>
            </label>

            <div>
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    Upload Images
                </button>
            </div>
        </form>
    </section>

    <!-- Gallery Cards Grid -->
    @if ($media->isEmpty())
        <div class="bg-white rounded-xl border border-dashed border-slate-300 p-10 text-center">
            <p class="text-sm text-slate-500">No images uploaded yet. Upload photos above to set a header background and gallery cards.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($media as $item)
                <article class="bg-white rounded-xl border {{ $item->is_hero ? 'border-indigo-400 ring-2 ring-indigo-500/20' : 'border-slate-200' }} shadow-sm overflow-hidden flex flex-col">
                    <div class="relative h-48 bg-slate-100">
                        <img src="{{ asset('storage/' . $item->path) }}" alt="{{ $item->alt_text ?: $site->business_name }}" class="w-full h-full object-cover">
                        @if ($item->is_hero)
                            <span class="absolute top-2.5 left-2.5 rounded-full bg-indigo-600 px-2.5 py-0.5 text-[11px] font-semibold text-white shadow">
                                Header Background
                            </span>
                        @endif
                    </div>

                    <div class="p-4 flex-1 flex flex-col justify-between gap-3">
                        <p class="text-xs text-slate-600 truncate">{{ $item->alt_text ?: 'Uploaded photo' }}</p>

                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100">
                            @if (! $item->is_hero)
                                <form method="POST" action="{{ route('sites.media.hero', [$site, $item]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-medium">
                                        Set as Header BG
                                    </button>
                                </form>
                            @else
                                <span class="text-xs font-medium text-indigo-600">Active Header BG</span>
                            @endif

                            <form method="POST" action="{{ route('sites.media.destroy', [$site, $item]) }}" onsubmit="return confirm('Delete this image?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-medium text-rose-600 hover:bg-rose-50">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
