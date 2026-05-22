<?php

namespace Tests\Feature;

use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_store_comment(): void
    {
        $response = $this->postJson('/api/comments', [
            'name' => 'Rahul',
            'email' => 'rahul@example.com',
            'comment' => 'Nice work',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.comment', 'Nice work');

        $this->assertDatabaseHas('comments', [
            'name' => 'Rahul',
            'email' => 'rahul@example.com',
            'comment' => 'Nice work',
        ]);
    }

    public function test_message_field_is_also_saved_as_comment(): void
    {
        $response = $this->postJson('/api/comments', [
            'message' => 'Frontend se aaya hua comment',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.comment', 'Frontend se aaya hua comment');

        $this->assertDatabaseHas('comments', [
            'comment' => 'Frontend se aaya hua comment',
        ]);
    }

    public function test_comments_can_be_listed(): void
    {
        Comment::query()->create([
            'name' => 'Anita',
            'email' => 'anita@example.com',
            'comment' => 'First comment',
        ]);

        $response = $this->getJson('/api/comments');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.comment', 'First comment');
    }
}
