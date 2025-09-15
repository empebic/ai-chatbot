<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Feed
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @forelse ($posts as $post)
                <div class="bg-white p-4 rounded shadow">
                    <a href="{{ route('posts.show', $post) }}" class="text-lg font-semibold">{{ $post->title ?? 'Untitled' }}</a>
                    <div class="text-sm text-gray-600">by {{ $post->user->profile->display_name ?? $post->user->name }}</div>
                    <div class="mt-2 text-gray-800">{{ Str::limit($post->body, 240) }}</div>
                </div>
            @empty
                <div class="text-gray-600">No posts yet.</div>
            @endforelse
            <div>
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

