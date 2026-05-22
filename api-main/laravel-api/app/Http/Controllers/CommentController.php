<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Comment::query()
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'comment' => 'required_without:message|nullable|string',
            'message' => 'required_without:comment|nullable|string',
        ]);

        $comment = Comment::create([
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'comment' => (string) ($validated['comment'] ?? $validated['message']),
        ]);

        $this->appendCommentToTask($request, $comment);

        return response()->json([
            'message' => 'Comment saved successfully',
            'data' => $comment,
        ], 201);
    }

    private function appendCommentToTask(Request $request, Comment $comment): void
    {
        $user = $request->user();
        if ($user === null) {
            return;
        }

        $body = (string) $comment->comment;
        $taskId = $this->extractTaskId($request, $body);
        if ($taskId === null) {
            return;
        }

        $task = Task::query()->whereBelongsTo($user)->find($taskId);
        if (!$task) {
            return;
        }

        $text = $this->stripTaskMetadata($body);
        if ($text === '') {
            return;
        }

        $existing = is_array($task->comments) ? $task->comments : [];
        $existing[] = [
            'id' => (string) $comment->id ?: (string) Str::uuid(),
            'authorName' => $comment->name ?: 'Workspace',
            'text' => $text,
            'createdAt' => $comment->created_at?->toISOString() ?? now()->toISOString(),
        ];

        $task->comments = $existing;
        $task->save();
    }

    private function extractTaskId(Request $request, string $body): ?int
    {
        foreach (['task_id', 'taskId', 'todo_id', 'todoId'] as $key) {
            $value = $request->input($key);
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        if (preg_match('/^Task ID:\s*(\d+)/mi', $body, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function stripTaskMetadata(string $body): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $body) ?: [];
        $content = array_values(array_filter(array_map(function (string $line): ?string {
            $trimmed = trim($line);
            if ($trimmed === '') {
                return null;
            }

            if (preg_match('/^(Task|Task ID|Author):/i', $trimmed) === 1) {
                return null;
            }

            return $trimmed;
        }, $lines)));

        return implode("\n", $content);
    }
}
