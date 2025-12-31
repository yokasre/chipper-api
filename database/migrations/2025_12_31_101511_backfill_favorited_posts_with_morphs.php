<?php

use App\Models\Favorite;
use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Get available post favorites and backfill them with the correct morphs.
         */
        $postFavorites = Favorite::whereNotNull('post_id')->get();
        foreach ($postFavorites as $postFavorite) {
            $post = Post::find($postFavorite->post_id);
            if ($post) {
                $postFavorite->parent()->associate($post);
                $postFavorite->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
