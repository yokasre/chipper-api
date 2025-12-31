<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use App\Models\Post;
use App\Models\User;
use App\Models\Favorite;
use App\Notifications\PostCreated;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_system_sends_notification_to_users_who_have_favorited_the_post_author()
    {
        Notification::fake();

        $author = User::factory()->create();
        $favoriter1 = User::factory()->create();
        $favoriter2 = User::factory()->create();
        $nonFavoriter = User::factory()->create();

        Favorite::create([
            'user_id' => $favoriter1->id,
            'parent_type' => User::class,
            'parent_id' => $author->id,
        ]);

        Favorite::create([
            'user_id' => $favoriter2->id,
            'parent_type' => User::class,
            'parent_id' => $author->id,
        ]);

        $this->actingAs($author)
            ->postJson(route('posts.store'), [
                'title' => 'Test Post',
                'body' => 'Test Body',
            ])
            ->assertStatus(201);

        Notification::assertSentTo(
            [$favoriter1, $favoriter2],
            PostCreated::class,
            function ($notification, $channels, $notifiable) {
                return $notification->post->title === 'Test Post';
            }
        );

        Notification::assertNotSentTo($nonFavoriter, PostCreated::class);
        Notification::assertNotSentTo($author, PostCreated::class);
    }

    /** @test */
    public function test_system_does_not_send_notifications_when_author_has_no_favoriters()
    {
        Notification::fake();

        $author = User::factory()->create();

        $this->actingAs($author)
            ->postJson(route('posts.store'), [
                'title' => 'Test Post',
                'body' => 'Test Body',
            ])
            ->assertStatus(201);

        Notification::assertNothingSent();
    }

    /** @test */
    public function test_system_queues_notifications_for_async_delivery()
    {
        Queue::fake();

        $author = User::factory()->create();
        $favoriter = User::factory()->create();

        Favorite::create([
            'user_id' => $favoriter->id,
            'parent_type' => User::class,
            'parent_id' => $author->id,
        ]);

        $this->actingAs($author)
            ->postJson(route('posts.store'), [
                'title' => 'Test Post',
                'body' => 'Test Body',
            ])
            ->assertStatus(201);

        Queue::assertPushed(SendQueuedNotifications::class);
    }

    /** @test */
    public function test_notification_uses_mail_channel_only()
    {
        $post = Post::factory()->create();
        $notification = new PostCreated($post);
        $user = User::factory()->create();

        $channels = $notification->via($user);

        $this->assertEquals(['mail'], $channels);
    }
}
