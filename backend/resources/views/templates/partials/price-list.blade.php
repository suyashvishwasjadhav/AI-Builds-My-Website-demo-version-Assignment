@if (! empty($priceItems))
    <section id="pricing" class="space-y-6">
        <h2 class="text-2xl font-semibold">Services &amp; Pricing</h2>
        <div class="overflow-hidden rounded-2xl border border-current/15">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-current/15 bg-current/5 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Service / Product</th>
                        <th class="px-5 py-3.5 font-semibold">Price</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Quote</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-current/10">
                    @foreach ($priceItems as $item)
                        @php
                            $quoteText = 'Hi, I would like to request a quote for: ' . $item['name'] . ' (' . $item['price'] . (! empty($item['unit']) ? ' / ' . $item['unit'] : '') . ').';
                        @endphp
                        <tr>
                            <td class="px-5 py-4 font-medium">{{ $item['name'] }}</td>
                            <td class="px-5 py-4">
                                <span class="font-semibold">{{ $item['price'] }}</span>
                                @if (! empty($item['unit']))
                                    <span class="opacity-70">/ {{ $item['unit'] }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button
                                    type="button"
                                    onclick="(function(){ var msg = document.getElementById('enquiry_message'); if (msg) { msg.value = @js($quoteText); } var contact = document.getElementById('contact'); if (contact) { contact.scrollIntoView({ behavior: 'smooth' }); } })()"
                                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition cursor-pointer"
                                >
                                    Inquire
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
