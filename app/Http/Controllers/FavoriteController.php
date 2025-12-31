<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFavoriteRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()
            ->favorites()
            ->with('parent')
            ->get();

        $posts = $favorites->where('parent_type', Post::class)->values()->map(fn ($favorite) => $favorite->parent);
        $users = $favorites->where('parent_type', User::class)->values()->map(fn ($favorite) => $favorite->parent);

        return [
            'data' => [
                'posts' => PostResource::collection($posts),
                'users' => UserResource::collection($users),
            ],
        ];
    }

    public function store(CreateFavoriteRequest $request, Post $post)
    {
        $favorite = $request->user()->favorites()->create([]);
        $favorite->parent()->associate($post);
        $favorite->save();

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Post $post)
    {
        $favorite = $request->user()->favorites()
            ->where('parent_id', $post->id)
            ->where('parent_type', Post::class)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }
}
