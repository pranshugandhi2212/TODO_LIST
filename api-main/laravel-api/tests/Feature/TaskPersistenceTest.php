<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_done_flag_updates_status_and_completion_timestamp(): void
    {
        $user = User::factory()->create();
        $task = $user->tasks()->create([
            'title' => 'Ship release',
            'description' => 'Finish deployment checklist',
            'category' => 'General',
            'priority' => 'Medium',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/tasks/{$task->id}", [
            'done' => true,
        ])
            ->assertOk()
            ->assertJsonPath('task.status', 'completed')
            ->assertJsonPath('task.done', true);

        $task->refresh();

        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_attachment_urls_are_returned_and_removed_files_are_deleted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $createResponse = $this->post('/api/tasks', [
            'title' => 'Upload proof',
            'description' => 'Keep a screenshot on this task',
            'attachment' => UploadedFile::fake()->create('proof.png', 25, 'image/png'),
        ], [
            'Accept' => 'application/json',
        ]);

        $createResponse->assertCreated();

        $taskId = (int) $createResponse->json('task.id');
        $storedPath = (string) $createResponse->json('task.attachments.0.path');
        $storedUrl = (string) $createResponse->json('task.attachments.0.url');

        $this->assertNotSame('', $storedPath);
        $this->assertStringContainsString('/storage/tasks/', $storedUrl);
        Storage::disk('public')->assertExists($storedPath);

        $this->patchJson("/api/tasks/{$taskId}", [
            'attachments' => [],
        ])
            ->assertOk()
            ->assertJsonCount(0, 'task.attachments');

        if (PHP_OS_FAMILY !== 'Windows') {
            Storage::disk('public')->assertMissing($storedPath);
        }
    }

    public function test_comment_endpoint_persists_comment_into_the_related_task(): void
    {
        $user = User::factory()->create();
        $task = $user->tasks()->create([
            'title' => 'Review copy',
            'description' => 'Need one final note',
            'category' => 'General',
            'priority' => 'Medium',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/comments', [
            'name' => 'Rahul',
            'comment' => "Task: {$task->title}\nTask ID: {$task->id}\nAuthor: Rahul\n\nNeed follow up before refresh.",
        ])
            ->assertCreated()
            ->assertJsonPath('data.comment', "Task: {$task->title}\nTask ID: {$task->id}\nAuthor: Rahul\n\nNeed follow up before refresh.");

        $task->refresh();

        $this->assertIsArray($task->comments);
        $this->assertCount(1, $task->comments);
        $this->assertSame('Rahul', $task->comments[0]['authorName']);
        $this->assertSame('Need follow up before refresh.', $task->comments[0]['text']);
    }
}
