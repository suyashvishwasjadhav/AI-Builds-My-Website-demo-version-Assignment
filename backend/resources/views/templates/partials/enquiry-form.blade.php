@if (! $isPreview)
    @php
        $isDark = ($template ?? 'clean') === 'bold';
        $isWarm = ($template ?? 'clean') === 'warm';

        $cardClasses = $isDark
            ? 'bg-slate-950/80 border-slate-800 text-slate-100'
            : ($isWarm
                ? 'bg-amber-50/60 border-amber-200 text-stone-900'
                : 'bg-white border-slate-200 text-slate-900');

        $inputClasses = $isDark
            ? 'border-slate-700 bg-slate-900 text-white placeholder-slate-500 focus:border-indigo-400 focus:ring-indigo-400/30'
            : ($isWarm
                ? 'border-amber-300 bg-white text-stone-900 placeholder-stone-400 focus:border-amber-600 focus:ring-amber-500/25'
                : 'border-slate-300 bg-white text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-indigo-500/25');

        $labelClasses = $isDark ? 'text-slate-300' : ($isWarm ? 'text-stone-700' : 'text-slate-700');
    @endphp

    <div class="mt-8 rounded-2xl border p-6 sm:p-8 shadow-sm {{ $cardClasses }}">
        <div class="mb-5">
            <h3 class="text-lg font-semibold">Send us a message</h3>
            <p class="mt-1 text-xs opacity-75">
                Have a question or need a quote? Fill out the form below and we will get back to you shortly.
            </p>
        </div>

        @if (session('enquiry_sent'))
            <div role="status" class="mb-5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                {{ session('enquiry_sent') }}
            </div>
        @endif

        <form
            method="POST"
            action="{{ ! empty($isTemplateDemo) ? '#contact' : route('public.enquiries.store', $site->slug) }}"
            @if (! empty($isTemplateDemo)) onsubmit="event.preventDefault(); document.getElementById('demo-enquiry-note')?.classList.remove('hidden');" @endif
            class="space-y-5"
        >
            @csrf

            <!-- Honeypot field (hidden from real users) -->
            <div class="hidden" aria-hidden="true">
                <label for="website_hp">Leave this blank</label>
                <input id="website_hp" type="text" name="website_hp" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="enquiry_name" class="block text-xs font-medium {{ $labelClasses }}">
                        Your name <span aria-hidden="true" class="text-rose-500">*</span>
                    </label>
                    <input
                        id="enquiry_name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        placeholder="e.g. Rahul Sharma"
                        required
                        class="mt-1.5 block w-full min-h-[44px] rounded-xl border px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 transition {{ $inputClasses }}"
                    >
                </div>

                <div>
                    <label for="enquiry_email" class="block text-xs font-medium {{ $labelClasses }}">
                        Email address <span aria-hidden="true" class="text-rose-500">*</span>
                    </label>
                    <input
                        id="enquiry_email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        autocorrect="off"
                        autocapitalize="none"
                        spellcheck="false"
                        placeholder="you@example.com"
                        required
                        class="mt-1.5 block w-full min-h-[44px] rounded-xl border px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 transition {{ $inputClasses }}"
                    >
                </div>
            </div>

            <div>
                <label for="enquiry_phone" class="block text-xs font-medium {{ $labelClasses }}">
                    Phone number <span class="opacity-60">(optional)</span>
                </label>
                <input
                    id="enquiry_phone"
                    type="tel"
                    name="phone"
                    value="{{ old('phone') }}"
                    autocomplete="tel"
                    placeholder="+91 98200 12345"
                    class="mt-1.5 block w-full min-h-[44px] rounded-xl border px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 transition {{ $inputClasses }}"
                >
            </div>

            <div>
                <label for="enquiry_message" class="block text-xs font-medium {{ $labelClasses }}">
                    Message <span aria-hidden="true" class="text-rose-500">*</span>
                </label>
                <textarea
                    id="enquiry_message"
                    name="message"
                    rows="4"
                    placeholder="Tell us what you are looking for..."
                    required
                    class="mt-1.5 block w-full rounded-xl border px-3.5 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 transition {{ $inputClasses }}"
                >{{ old('message') }}</textarea>
            </div>

            <div id="demo-enquiry-note" role="status" class="hidden rounded-xl bg-emerald-500/15 border border-emerald-500/30 px-4 py-2.5 text-xs font-medium text-emerald-500">
                Demo preview: On a live published site, this message is saved directly to your Enquiries Inbox and emailed to the owner.
            </div>

            <div class="pt-1 flex items-center justify-between gap-4">
                <button
                    type="submit"
                    class="tpl-btn inline-flex items-center justify-center px-6 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm cursor-pointer"
                    style="background-color: var(--brand-accent);"
                >
                    Send message
                </button>
                <span class="text-[11px] opacity-60">Protected by spam filtering</span>
            </div>
        </form>
    </div>
@endif
