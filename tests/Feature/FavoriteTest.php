<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_favorite_a_post()
    {
        $post = Post::factory()->create();

        $this->postJson(route('favorites.store', ['post' => $post]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'parent_id' => $post->id,
            'parent_type' => Post::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_remove_a_post_from_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'parent_id' => $post->id,
            'parent_type' => Post::class,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'parent_id' => $post->id,
            'parent_type' => Post::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNotFound();
    }

    public function test_a_user_can_favorite_another_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.for_users.store', ['user' => $otherUser]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'parent_id' => $otherUser->id,
            'parent_type' => User::class,
        ]);
    }

    public function test_a_user_can_unfavorite_another_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Pre-create the favorite
        Favorite::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $otherUser->id,
            'parent_type' => User::class,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.for_users.destroy', ['user' => $otherUser]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'parent_id' => $otherUser->id,
            'parent_type' => User::class,
        ]);
    }

    public function test_a_user_cannot_favorite_himself()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.for_users.store', ['user' => $user]))
            ->assertUnprocessable();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_cannot_unfavorite_a_user_that_is_not_favorited()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.for_users.destroy', ['user' => $otherUser]))
            ->assertNotFound();
    }
}
