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
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('sites.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="business_name" :value="__('Business name')" />
                        <x-text-input
                            id="business_name"
                            name="business_name"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('business_name')"
                            required
                            autofocus
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('business_name')" />
                    </div>

                    <div>
                        <x-input-label for="city" :value="__('City')" />
                        <x-text-input
                            id="city"
                            name="city"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('city')"
                            required
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('city')" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
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
