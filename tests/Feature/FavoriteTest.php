<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Post;
use App\Models\User;
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

    public function test_favorites_index_returns_grouped_posts_and_users()
    {
        $user = User::factory()->create();

        $post1 = Post::factory()->create(['user_id' => $user->id, 'title' => 'Post One']);
        $post2 = Post::factory()->create(['user_id' => $user->id, 'title' => 'Post Two']);
        $otherUser1 = User::factory()->create(['name' => 'Alice']);
        $otherUser2 = User::factory()->create(['name' => 'Bob']);

        $user->favorites()->create(['parent_id' => $post1->id, 'parent_type' => Post::class]);
        $user->favorites()->create(['parent_id' => $post2->id, 'parent_type' => Post::class]);
        $user->favorites()->create(['parent_id' => $otherUser1->id, 'parent_type' => User::class]);
        $user->favorites()->create(['parent_id' => $otherUser2->id, 'parent_type' => User::class]);

        // Act as the user and hit the endpoint
        $response = $this->actingAs($user)->getJson(route('favorites.index'));

        // Assert response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'posts' => [
                        '*' => [
                            'id',
                            'title',
                            'body',
                            'user' => [
                                'id',
                                'name',
                            ],
                        ],
                    ],
                    'users' => [
                        '*' => ['id', 'name'],
                    ],
                ],
            ]);
    }
}
