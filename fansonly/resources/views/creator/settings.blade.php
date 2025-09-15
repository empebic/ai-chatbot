<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Creator Settings
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 text-green-600">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('creator.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Enable Creator</label>
                            <input type="hidden" name="is_creator" value="0">
                            <label class="inline-flex items-center mt-2">
                                <input type="checkbox" name="is_creator" value="1" class="rounded" {{ optional($profile)->is_creator ? 'checked' : '' }}>
                                <span class="ml-2">Enable creator profile</span>
                            </label>
                            @error('is_creator')
                                <div class="text-red-600 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Monthly Price</label>
                            <input type="number" step="0.01" name="monthly_price" class="mt-1 block w-full border rounded p-2" value="{{ old('monthly_price', optional($profile)->monthly_price) }}" />
                            @error('monthly_price')
                                <div class="text-red-600 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Currency</label>
                            <input type="text" name="currency" maxlength="3" class="mt-1 block w-24 border rounded p-2" value="{{ old('currency', optional($profile)->currency ?? 'USD') }}" />
                            @error('currency')
                                <div class="text-red-600 text-sm">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-primary-button>Save Settings</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

