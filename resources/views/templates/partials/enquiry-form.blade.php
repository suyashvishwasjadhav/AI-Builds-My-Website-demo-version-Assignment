@if (! $isPreview)
    <div class="mt-6 pt-6 border-t border-current/10">
        @if (session('enquiry_sent'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                {{ session('enquiry_sent') }}
            </div>
        @endif

        <form method="POST" action="{{ route('public.enquiries.store', $site->slug) }}" class="space-y-4">
            @csrf

            <!-- Honeypot field (hidden from real users) -->
            <div class="hidden" aria-hidden="true">
                <label for="website_hp">Leave this blank</label>
                <input id="website_hp" type="text" name="website_hp" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="enquiry_name" class="block text-xs font-medium opacity-80">Your name *</label>
                    <input
                        id="enquiry_name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm"
                    >
                </div>

                <div>
                    <label for="enquiry_email" class="block text-xs font-medium opacity-80">Email *</label>
                    <input
                        id="enquiry_email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm"
                    >
                </div>
            </div>

            <div>
                <label for="enquiry_phone" class="block text-xs font-medium opacity-80">Phone (optional)</label>
                <input
                    id="enquiry_phone"
                    type="text"
                    name="phone"
                    value="{{ old('phone') }}"
                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm"
                >
            </div>

            <div>
                <label for="enquiry_message" class="block text-xs font-medium opacity-80">Message *</label>
                <textarea
                    id="enquiry_message"
                    name="message"
                    rows="3"
                    required
                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm"
                >{{ old('message') }}</textarea>
            </div>

            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition"
            >
                Send message
            </button>
        </form>
    </div>
@endif
