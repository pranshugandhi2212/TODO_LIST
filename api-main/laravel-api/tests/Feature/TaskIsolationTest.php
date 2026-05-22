<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_task_routes(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
        $this->postJson('/api/tasks', [
            'title' => 'Blocked task',
        ])->assertUnauthorized();
    }

    public function test_authenticated_users_only_receive_their_own_tasks(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownerTask = $owner->tasks()->create($this->taskPayload('Owner task'));
        $otherUser->tasks()->create($this->taskPayload('Other user task'));

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/tasks');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownerTask->id)
            ->assertJsonPath('data.0.user_id', $owner->id);
    }

    public function test_stored_tasks_are_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'title' => 'My private task',
            'description' => 'Visible only to me',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('task.user_id', $user->id);

        $this->assertDatabaseHas('tasks', [
            'title' => 'My private task',
            'user_id' => $user->id,
        ]);
    }

    public function test_users_cannot_view_update_or_delete_tasks_belonging_to_other_users(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTask = $otherUser->tasks()->create($this->taskPayload('Other user task'));

        Sanctum::actingAs($owner);

        $this->getJson("/api/tasks/{$otherTask->id}")->assertNotFound();
        $this->patchJson("/api/tasks/{$otherTask->id}", [
            'title' => 'Hacked title',
        ])->assertNotFound();
        $this->deleteJson("/api/tasks/{$otherTask->id}")->assertNotFound();

        $this->assertDatabaseHas('tasks', [
            'id' => $otherTask->id,
            'title' => 'Other user task',
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_users_can_update_their_own_tasks(): void
    {
        $user = User::factory()->create();
        $task = $user->tasks()->create($this->taskPayload('Original title'));

        Sanctum::actingAs($user);

        $this->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Updated title',
            'status' => 'completed',
        ])
            ->assertOk()
            ->assertJsonPath('task.title', 'Updated title')
            ->assertJsonPath('task.status', 'completed');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
            'user_id' => $user->id,
        ]);
    }

    private function taskPayload(string $title): array
    {
        return [
            'title' => $title,
            'description' => 'Task description',
            'category' => 'General',
            'priority' => 'Medium',
            'status' => 'pending',
            'estimated_hours' => 1,
            'estimated_minutes' => 15,
        ];
    }
}
