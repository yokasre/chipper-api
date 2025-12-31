<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateFavoriteUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FavoriteUserController extends Controller
{
    public function store(CreateFavoriteUserRequest $request, User $user)
    {
        if ($request->user()->id == $user->id) {
            return response()->json([
                'message' => 'You cannot favorite yourself',
            ], 422);
        }

        $favorite = $request->user()->favorites()->create([]);
        $favorite->parent()->associate($user);
        $favorite->save();

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, User $user)
    {
        $favorite = $request->user()->favorites()
            ->where('parent_id', $user->id)
            ->where('parent_type', User::class)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }
}
