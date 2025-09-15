<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::with(['user.profile'])
            ->latest('published_at')
            ->paginate(20);
        return view('posts.index', compact('posts'));
    }

    public function create(Request $request): View
    {
        return view('posts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'visibility' => ['required', 'in:public,subscribers,paid'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'media.*' => ['nullable', 'file', 'max:51200'],
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'] ?? null,
            'visibility' => $validated['visibility'],
            'price' => $validated['visibility'] === 'paid' ? ($validated['price'] ?? 0) : null,
            'published_at' => now(),
        ]);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $upload) {
                $path = $upload->store('uploads/'.date('Y/m/d'), 'public');

                Media::create([
                    'post_id' => $post->id,
                    'user_id' => $request->user()->id,
                    'disk' => 'public',
                    'path' => $path,
                    'mime_type' => $upload->getClientMimeType(),
                    'size_bytes' => $upload->getSize(),
                    'type' => str_starts_with($upload->getClientMimeType(), 'video') ? 'video' : (str_starts_with($upload->getClientMimeType(), 'image') ? 'image' : 'file'),
                ]);
            }
        }

        return redirect()->route('posts.show', $post)->with('status', 'Post created');
    }

    public function show(Post $post): View
    {
        $post->load(['user.profile', 'media']);
        return view('posts.show', compact('post'));
    }
}
