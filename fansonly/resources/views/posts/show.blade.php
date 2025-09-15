<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $post->title ?? 'Post' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded shadow">
                <div class="text-sm text-gray-600 mb-2">by {{ $post->user->profile->display_name ?? $post->user->name }}</div>
                <div class="prose max-w-none">{!! nl2br(e($post->body)) !!}</div>

                @if ($post->media->count())
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        @foreach ($post->media as $media)
                            @if (str_starts_with($media->mime_type, 'image'))
                                <img src="{{ Storage::disk($media->disk)->url($media->path) }}" class="rounded" />
                            @elseif (str_starts_with($media->mime_type, 'video'))
                                <video controls class="rounded">
                                    <source src="{{ Storage::disk($media->disk)->url($media->path) }}" type="{{ $media->mime_type }}">
                                </video>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

