<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            New Post
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded shadow">
                <form method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="title" class="mt-1 block w-full border rounded p-2" value="{{ old('title') }}" />
                        @error('title') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Body</label>
                        <textarea name="body" rows="5" class="mt-1 block w-full border rounded p-2">{{ old('body') }}</textarea>
                        @error('body') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Visibility</label>
                        <select name="visibility" class="mt-1 block w-full border rounded p-2">
                            <option value="public" {{ old('visibility')==='public' ? 'selected' : '' }}>Public</option>
                            <option value="subscribers" {{ old('visibility','subscribers')==='subscribers' ? 'selected' : '' }}>Subscribers</option>
                            <option value="paid" {{ old('visibility')==='paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                        @error('visibility') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4" x-data="{ v: '{{ old('visibility','subscribers') }}' }">
                        <label class="block text-sm font-medium text-gray-700">Price (if paid)</label>
                        <input type="number" step="0.01" name="price" class="mt-1 block w-40 border rounded p-2" value="{{ old('price') }}" />
                        @error('price') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Media</label>
                        <input type="file" name="media[]" multiple class="mt-1 block w-full" />
                        <div class="text-xs text-gray-500 mt-1">Up to 50MB per file.</div>
                        @error('media.*') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
                    </div>

                    <x-primary-button>Create</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

