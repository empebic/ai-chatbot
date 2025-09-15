<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $creatorIds = $user ? $user->subscriptions()->pluck('creator_id') : collect();
        $posts = Post::with(['user.profile', 'media'])
            ->where(function ($q) use ($user, $creatorIds) {
                $q->where('visibility', 'public')
                  ->when($user, function ($q) use ($creatorIds) {
                      $q->orWhere(function ($q) use ($creatorIds) {
                          $q->where('visibility', 'subscribers')
                            ->whereIn('user_id', $creatorIds);
                      });
                  });
            })
            ->latest('published_at')
            ->paginate(20);

        return view('feed.index', compact('posts'));
    }
}
