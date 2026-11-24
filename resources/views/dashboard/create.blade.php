<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create new site') }}
            </h2>
            <a href="{{ route('sites.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                &larr; Back to My sites
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sm:p-8">
                <p class="text-sm text-slate-600 mb-6">
                    Share what you already have for your business. We only publish verified facts from your sources or answers you confirm.
                </p>

                <form
                    method="POST"
                    action="{{ route('sites.store') }}"
                    enctype="multipart/form-data"
                    class="space-y-6"
                    x-data="{
                        links: {{ Js::from(old('social_links', [''])) }},
                        addLink() {
                            if (this.links.length < 10) this.links.push('');
                        },
                        removeLink(index) {
                            this.links.splice(index, 1);
                            if (this.links.length === 0) this.links.push('');
                        }
                    }"
                >
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="business_name" :value="__('Business name *')" />
                            <x-text-input
                                id="business_name"
                                name="business_name"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('business_name')"
                                placeholder="e.g. Sunrise Bakery"
                                required
                                autofocus
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('business_name')" />
                        </div>

                        <div>
                            <x-input-label for="city" :value="__('City *')" />
                            <x-text-input
                                id="city"
                                name="city"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('city')"
                                placeholder="e.g. Pune"
                                required
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('city')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="google_business_url" :value="__('Google Business listing link (optional)')" />
                        <x-text-input
                            id="google_business_url"
                            name="google_business_url"
                            type="url"
                            class="mt-1 block w-full"
                            :value="old('google_business_url')"
                            placeholder="https://maps.google.com/..."
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('google_business_url')" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label :value="__('Social or website links (optional)')" />
                            <button
                                type="button"
                                @click="addLink()"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                + Add another link
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Facebook, Instagram, or an existing website URL.
                        </p>

                        <div class="mt-2 space-y-2">
                            <template x-for="(link, index) in links" :key="index">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="url"
                                        name="social_links[]"
                                        x-model="links[index]"
                                        placeholder="https://instagram.com/yourbusiness"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full text-sm"
                                    />
                                    <button
                                        type="button"
                                        @click="removeLink(index)"
                                        class="px-2.5 py-2 text-xs text-slate-500 hover:text-red-600 border border-slate-200 rounded-md"
                                        title="Remove link"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </template>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('social_links')" />
                        @foreach ($errors->get('social_links.*') as $messages)
                            <x-input-error class="mt-1" :messages="$messages" />
                        @endforeach
                    </div>

                    <div>
                        <x-input-label for="files" :value="__('Upload files (optional)')" />
                        <p class="mt-1 text-xs text-slate-500">
                            PDF brochures, CSV/XLSX price lists, or photos (JPG, PNG, WEBP). Max 10 MB per file.
                        </p>
                        <input
                            id="files"
                            name="files[]"
                            type="file"
                            multiple
                            accept=".pdf,.csv,.txt,.xlsx,.xls,.jpg,.jpeg,.png,.webp"
                            class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('files')" />
                        @foreach ($errors->get('files.*') as $messages)
                            <x-input-error class="mt-1" :messages="$messages" />
                        @endforeach
                    </div>

                    <div>
                        <x-input-label for="notes" :value="__('Anything else you want us to know (optional)')" />
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            placeholder="Opening hours, phone number, services, or anything else you want included..."
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        >{{ old('notes') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-2 border-t border-slate-100">
                        <a href="{{ route('sites.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                            Cancel
                        </a>
                        <x-primary-button>
                            {{ __('Create site') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
