<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->userTasksQuery($request)->latest('created_at')->get()->map(
                fn (Task $task) => $this->transformTask($task)
            ),
        ]);
    }

    public function show(Request $request, int $task): JsonResponse
    {
        return response()->json([
            'data' => $this->transformTask($this->findUserTask($request, $task)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTask($request);
        $task = $request->user()->tasks()->make();
        $this->applyTaskPayload($task, $validated, $request, false);
        $task->save();

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $this->transformTask($task->fresh()),
        ], 201);
    }

    public function update(Request $request, int $task): JsonResponse
    {
        $taskModel = $this->findUserTask($request, $task);
        $validated = $this->validateTask($request, true);
        $this->applyTaskPayload($taskModel, $validated, $request, true);
        $taskModel->save();

        return response()->json([
            'message' => 'Task updated successfully',
            'task' => $this->transformTask($taskModel->fresh()),
        ]);
    }

    public function storeAttachments(Request $request, int $task): JsonResponse
    {
        $taskModel = $this->findUserTask($request, $task);
        $validated = $this->validateTask($request, true);
        $this->applyTaskPayload($taskModel, $validated, $request, true);
        $taskModel->save();

        return response()->json([
            'message' => 'Attachments saved successfully',
            'task' => $this->transformTask($taskModel->fresh()),
        ], 201);
    }

    public function storeComment(Request $request, int $task): JsonResponse
    {
        $taskModel = $this->findUserTask($request, $task);
        $validated = $request->validate([
            'comment' => 'sometimes',
            'text' => 'sometimes|nullable|string',
            'message' => 'sometimes|nullable|string',
            'author_name' => 'sometimes|nullable|string|max:255',
            'authorName' => 'sometimes|nullable|string|max:255',
            'created_at' => 'sometimes|nullable|date',
            'createdAt' => 'sometimes|nullable|date',
            'comments' => 'sometimes|array',
            'comments.*.id' => 'sometimes|nullable|string|max:255',
            'comments.*.text' => 'sometimes|nullable|string',
            'comments.*.comment' => 'sometimes|nullable|string',
            'comments.*.message' => 'sometimes|nullable|string',
            'comments.*.author_name' => 'sometimes|nullable|string|max:255',
            'comments.*.authorName' => 'sometimes|nullable|string|max:255',
            'comments.*.created_at' => 'sometimes|nullable|date',
            'comments.*.createdAt' => 'sometimes|nullable|date',
        ]);

        $incoming = array_key_exists('comments', $validated)
            ? $this->normalizeComments($validated['comments'])
            : array_values(array_filter([$this->normalizeComment($validated['comment'] ?? $validated)]));

        if ($incoming === []) {
            return response()->json(['message' => 'Comment text is required.'], 422);
        }

        $taskModel->comments = $this->mergeComments($this->normalizeComments($taskModel->comments ?? []), $incoming);
        $taskModel->save();

        return response()->json([
            'message' => 'Comment saved successfully',
            'data' => $incoming[0],
            'task' => $this->transformTask($taskModel->fresh()),
        ], 201);
    }

    public function destroy(Request $request, int $task): JsonResponse
    {
        $taskModel = $this->findUserTask($request, $task);
        $this->deleteAttachmentPaths($this->attachmentsFor($taskModel));
        $taskModel->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

    private function transformTask(Task $task): array
    {
        $status = strtolower((string) $task->status);
        $done = in_array($status, ['completed', 'done'], true);
        $dueDate = $task->due_date?->toDateString();
        $attachments = array_values(array_map(fn (array $item) => $this->formatAttachment($item), $this->attachmentsFor($task)));
        $images = array_values(array_map(
            fn (array $item) => (string) $item['url'],
            array_filter($attachments, fn (array $item) => (bool) ($item['isImage'] ?? false))
        ));
        $primary = $attachments[0] ?? null;

        return [
            'id' => $task->id,
            'user_id' => $task->user_id,
            'title' => (string) $task->title,
            'description' => (string) ($task->description ?? ''),
            'category' => (string) ($task->category ?? 'General'),
            'priority' => (string) ($task->priority ?? 'Medium'),
            'assignee' => $task->assignee,
            'project' => $task->project,
            'department' => $task->department,
            'client_name' => $task->client_name,
            'clientName' => $task->client_name,
            'location' => $task->location,
            'status_note' => $task->status_note,
            'statusNote' => $task->status_note,
            'lane' => $task->lane,
            'status' => $task->status,
            'done' => $done,
            'completed' => $done,
            'completed_at' => $task->completed_at?->toISOString(),
            'completedAt' => $task->completed_at?->toISOString(),
            'due_date' => $dueDate,
            'due_at' => $dueDate ? sprintf('%sT23:59:00', $dueDate) : null,
            'dueAt' => $dueDate ? sprintf('%sT23:59:00', $dueDate) : null,
            'estimated_hours' => (int) ($task->estimated_hours ?? 0),
            'estimated_minutes' => (int) ($task->estimated_minutes ?? 0),
            'estimated_duration' => $this->formatEstimatedDuration((int) ($task->estimated_hours ?? 0), (int) ($task->estimated_minutes ?? 0)),
            'checkpoints' => $this->normalizeTextList($task->checkpoints ?? []),
            'tags' => $this->normalizeTextList($task->tags ?? []),
            'comments' => $this->normalizeComments($task->comments ?? []),
            'attachments' => $attachments,
            'attachment' => $primary['url'] ?? null,
            'attachment_url' => $primary['url'] ?? null,
            'attachment_path' => $primary['path'] ?? null,
            'images' => $images,
            'image' => $images[0] ?? null,
            'created_at' => $task->created_at?->toISOString(),
            'updated_at' => $task->updated_at?->toISOString(),
        ];
    }

    private function validateTask(Request $request, bool $partial = false): array
    {
        $titleRule = $partial ? 'sometimes|required|string|max:255' : 'required|string|max:255';
        $validated = $request->validate([
            'title' => $titleRule,
            'description' => 'sometimes|nullable|string',
            'category' => 'sometimes|nullable|string|max:255',
            'priority' => ['sometimes', 'nullable', 'string', Rule::in(['Low', 'Medium', 'High'])],
            'assignee' => 'sometimes|nullable|string|max:255',
            'project' => 'sometimes|nullable|string|max:255',
            'department' => 'sometimes|nullable|string|max:255',
            'client_name' => 'sometimes|nullable|string|max:255',
            'clientName' => 'sometimes|nullable|string|max:255',
            'location' => 'sometimes|nullable|string|max:255',
            'status_note' => 'sometimes|nullable|string',
            'statusNote' => 'sometimes|nullable|string',
            'lane' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|nullable|string|max:50',
            'done' => 'sometimes|boolean',
            'completed' => 'sometimes|boolean',
            'is_done' => 'sometimes|boolean',
            'isDone' => 'sometimes|boolean',
            'is_completed' => 'sometimes|boolean',
            'isCompleted' => 'sometimes|boolean',
            'completed_at' => 'sometimes|nullable|date',
            'completedAt' => 'sometimes|nullable|date',
            'due_date' => 'sometimes|nullable|date',
            'due_at' => 'sometimes|nullable|date',
            'dueAt' => 'sometimes|nullable|date',
            'estimated_duration' => 'sometimes|nullable|string|max:50',
            'estimated_hours' => 'sometimes|nullable|integer|min:0|max:9999',
            'estimated_minutes' => 'sometimes|nullable|integer|min:0|max:59',
            'checkpoints' => 'sometimes|array',
            'checkpoints.*' => 'nullable|string|max:255',
            'tags' => 'sometimes|array',
            'tags.*' => 'nullable|string|max:255',
            'comments' => 'sometimes|array',
            'comments.*.id' => 'sometimes|nullable|string|max:255',
            'comments.*.text' => 'sometimes|nullable|string',
            'comments.*.comment' => 'sometimes|nullable|string',
            'comments.*.message' => 'sometimes|nullable|string',
            'comments.*.author_name' => 'sometimes|nullable|string|max:255',
            'comments.*.authorName' => 'sometimes|nullable|string|max:255',
            'comments.*.created_at' => 'sometimes|nullable|date',
            'comments.*.createdAt' => 'sometimes|nullable|date',
            'attachments' => 'sometimes',
            'remove_attachment' => 'sometimes|boolean',
        ]);

        foreach ($this->filesFromRequest($request) as $index => $file) {
            validator(["attachment_{$index}" => $file], ["attachment_{$index}" => 'file|max:10240'])->validate();
        }

        return $validated;
    }

    private function applyTaskPayload(Task $task, array $validated, Request $request, bool $partial): void
    {
        if (!$partial || array_key_exists('title', $validated)) $task->title = (string) ($validated['title'] ?? $task->title ?? '');
        if (!$partial || array_key_exists('description', $validated)) $task->description = $validated['description'] ?? null;
        if (!$partial || array_key_exists('category', $validated)) $task->category = $validated['category'] ?? 'General';
        if (!$partial || array_key_exists('priority', $validated)) $task->priority = $validated['priority'] ?? 'Medium';
        if (!$partial || array_key_exists('assignee', $validated)) $task->assignee = $validated['assignee'] ?? null;
        if (!$partial || array_key_exists('project', $validated)) $task->project = $validated['project'] ?? null;
        if (!$partial || array_key_exists('department', $validated)) $task->department = $validated['department'] ?? null;
        if (!$partial || array_key_exists('client_name', $validated) || array_key_exists('clientName', $validated)) $task->client_name = $validated['client_name'] ?? $validated['clientName'] ?? null;
        if (!$partial || array_key_exists('location', $validated)) $task->location = $validated['location'] ?? null;
        if (!$partial || array_key_exists('status_note', $validated) || array_key_exists('statusNote', $validated)) $task->status_note = $validated['status_note'] ?? $validated['statusNote'] ?? null;
        if (!$partial || array_key_exists('lane', $validated)) $task->lane = $validated['lane'] ?? null;
        if (!$partial || array_key_exists('due_date', $validated) || array_key_exists('due_at', $validated) || array_key_exists('dueAt', $validated)) {
            $value = $validated['due_date'] ?? $validated['due_at'] ?? $validated['dueAt'] ?? null;
            $task->due_date = $value ? date('Y-m-d', strtotime((string) $value)) : null;
        }
        if (array_key_exists('estimated_duration', $validated)) {
            [$hours, $minutes] = $this->parseEstimatedDuration($validated['estimated_duration'] ? (string) $validated['estimated_duration'] : null);
            $task->estimated_hours = $hours;
            $task->estimated_minutes = $minutes;
        } else {
            if (!$partial || array_key_exists('estimated_hours', $validated)) $task->estimated_hours = max(0, (int) ($validated['estimated_hours'] ?? 0));
            if (!$partial || array_key_exists('estimated_minutes', $validated)) $task->estimated_minutes = max(0, min(59, (int) ($validated['estimated_minutes'] ?? 0)));
        }
        if (!$partial || array_key_exists('checkpoints', $validated)) $task->checkpoints = $this->normalizeTextList($validated['checkpoints'] ?? []);
        if (!$partial || array_key_exists('tags', $validated)) $task->tags = $this->normalizeTextList($validated['tags'] ?? []);
        if (!$partial || $this->statusProvided($validated)) {
            $task->status = $this->resolveStatus($validated, $task);
            $task->completed_at = $this->resolveCompletedAt($validated, (string) $task->status, $task);
        }
        if (!$partial || array_key_exists('comments', $validated)) $task->comments = $this->normalizeComments($validated['comments'] ?? []);
        $this->syncAttachments($task, $validated, $request, $partial);
    }

    private function syncAttachments(Task $task, array $validated, Request $request, bool $partial): void
    {
        $current = $this->attachmentsFor($task);
        $hasAttachmentPayload = array_key_exists('attachments', $validated) && is_array($validated['attachments']);
        $next = $hasAttachmentPayload ? $this->normalizeAttachments($validated['attachments']) : ($partial ? $current : []);
        if ($request->boolean('remove_attachment') && !$hasAttachmentPayload) $next = [];
        $files = $this->filesFromRequest($request);
        if ($files !== []) $next = $this->mergeAttachments($next, $this->storeFiles($files));
        $this->deleteRemovedAttachmentPaths($current, $next);
        $task->attachments = $next === [] ? null : $next;
        $task->attachment = collect($next)->map(fn (array $item) => $item['path'] ?? null)->filter()->first();
    }

    private function attachmentsFor(Task $task): array
    {
        $items = [];
        foreach ((array) ($task->attachments ?? []) as $index => $raw) {
            $item = $this->normalizeAttachment($raw, $index);
            if ($item !== null) $items[] = $item;
        }
        if ($task->attachment) {
            $legacy = $this->normalizeAttachment(['path' => $task->attachment], count($items));
            if ($legacy !== null) $items[] = $legacy;
        }

        return $this->mergeAttachments([], $items);
    }

    private function normalizeAttachments(mixed $value): array
    {
        if (!is_array($value)) return [];
        $items = [];
        foreach ($value as $index => $raw) {
            $item = $this->normalizeAttachment($raw, $index);
            if ($item !== null) $items[] = $item;
        }

        return $this->mergeAttachments([], $items);
    }

    private function normalizeAttachment(mixed $value, int $index = 0): ?array
    {
        if (is_string($value)) $value = ['path' => $value];
        if (!is_array($value)) return null;

        $path = $this->extractPublicPath((string) (
            $value['path']
            ?? $value['attachment_path']
            ?? $value['attachmentPath']
            ?? $value['storage_path']
            ?? $value['storagePath']
            ?? $value['url']
            ?? ''
        ));
        $url = trim((string) ($value['url'] ?? $value['attachment_url'] ?? $value['attachmentUrl'] ?? ''));
        if ($path === null && $url === '') return null;

        $source = $path ?? $url;
        $type = trim((string) ($value['type'] ?? '')) ?: $this->guessMime($source);
        $name = trim((string) ($value['name'] ?? '')) ?: (basename(parse_url($source, PHP_URL_PATH) ?: $source) ?: 'attachment-'.($index + 1));

        return [
            'id' => trim((string) ($value['id'] ?? '')) ?: sha1($source.':'.$index),
            'name' => $name,
            'path' => $path,
            'url' => $path ? $this->attachmentUrl($path) : $url,
            'type' => $type,
            'size' => isset($value['size']) && is_numeric($value['size']) ? max(0, (int) $value['size']) : null,
            'isImage' => array_key_exists('isImage', $value)
                ? (bool) $value['isImage']
                : (array_key_exists('is_image', $value) ? (bool) $value['is_image'] : $this->isImage($type, $source)),
        ];
    }

    private function formatAttachment(array $item): array
    {
        $path = isset($item['path']) ? (string) $item['path'] : null;
        $url = $path ? $this->attachmentUrl($path) : (string) ($item['url'] ?? '');

        return array_filter([
            'id' => (string) ($item['id'] ?? sha1(($path ?? $url).':task')),
            'name' => (string) ($item['name'] ?? 'attachment'),
            'path' => $path,
            'url' => $url,
            'type' => (string) ($item['type'] ?? $this->guessMime($path ?? $url)),
            'size' => isset($item['size']) ? (int) $item['size'] : null,
            'isImage' => (bool) ($item['isImage'] ?? false),
        ], fn (mixed $part) => $part !== null && $part !== '');
    }

    private function mergeAttachments(array $existing, array $incoming): array
    {
        $cache = [];
        foreach (array_merge($existing, $incoming) as $item) {
            if (!is_array($item)) continue;
            $key = isset($item['path']) && is_string($item['path']) && $item['path'] !== ''
                ? 'path:'.$item['path']
                : (isset($item['url']) && is_string($item['url']) && $item['url'] !== '' ? 'url:'.$item['url'] : '');
            if ($key !== '') $cache[$key] = $item;
        }

        return array_values($cache);
    }

    private function storeFiles(array $files): array
    {
        $items = [];
        foreach ($files as $index => $file) {
            $path = $file->store('tasks', 'public');
            $type = $file->getMimeType() ?: $this->guessMime($file->getClientOriginalName());
            $items[] = [
                'id' => (string) Str::uuid(),
                'name' => $file->getClientOriginalName() ?: 'attachment-'.($index + 1),
                'path' => $path,
                'url' => $this->attachmentUrl($path),
                'type' => $type,
                'size' => $file->getSize(),
                'isImage' => $this->isImage($type, $path),
            ];
        }

        return $items;
    }

    private function filesFromRequest(Request $request): array
    {
        $files = [];
        foreach (['attachment', 'attachments'] as $key) {
            $candidate = $request->file($key);
            if ($candidate !== null) $files = [...$files, ...$this->flattenFiles($candidate)];
        }

        return array_values(array_filter($files, fn (mixed $file) => $file instanceof UploadedFile));
    }

    private function flattenFiles(mixed $value): array
    {
        if ($value instanceof UploadedFile) return [$value];
        if (!is_array($value)) return [];
        $files = [];
        foreach ($value as $item) $files = [...$files, ...$this->flattenFiles($item)];
        return $files;
    }

    private function deleteRemovedAttachmentPaths(array $current, array $next): void
    {
        $nextPaths = collect($next)->map(fn (array $item) => $item['path'] ?? null)->filter()->all();
        foreach ($current as $item) {
            $path = $item['path'] ?? null;
            if (is_string($path) && $path !== '' && !in_array($path, $nextPaths, true)) {
                $this->deletePublicPath($path);
            }
        }
    }

    private function deleteAttachmentPaths(array $attachments): void
    {
        foreach ($attachments as $item) {
            $path = $item['path'] ?? null;
            if (is_string($path) && $path !== '') {
                $this->deletePublicPath($path);
            }
        }
    }

    private function deletePublicPath(string $path): void
    {
        $disk = Storage::disk('public');
        $disk->delete($path);

        if (!$disk->exists($path)) {
            return;
        }

        $absolutePath = $disk->path($path);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function normalizeComments(mixed $value): array
    {
        if (!is_array($value)) return [];
        $items = array_values(array_filter(array_map(fn (mixed $item) => $this->normalizeComment($item), $value)));
        return $this->mergeComments([], $items);
    }

    private function normalizeComment(mixed $value): ?array
    {
        if (is_string($value)) $value = ['text' => $value];
        if (!is_array($value)) return null;
        $text = trim((string) ($value['text'] ?? $value['comment'] ?? $value['message'] ?? ''));
        if ($text === '') return null;

        return [
            'id' => trim((string) ($value['id'] ?? '')) ?: (string) Str::uuid(),
            'authorName' => trim((string) ($value['authorName'] ?? $value['author_name'] ?? 'Workspace')) ?: 'Workspace',
            'text' => $text,
            'createdAt' => ($value['createdAt'] ?? $value['created_at'] ?? null)
                ? date(DATE_ATOM, strtotime((string) ($value['createdAt'] ?? $value['created_at'])))
                : now()->toISOString(),
        ];
    }

    private function mergeComments(array $existing, array $incoming): array
    {
        $cache = [];
        foreach (array_merge($existing, $incoming) as $item) {
            if (!is_array($item)) continue;
            $normalized = $this->normalizeComment($item);
            if ($normalized !== null) $cache[$normalized['id']] = $normalized;
        }
        $items = array_values($cache);
        usort($items, fn (array $a, array $b) => strcmp((string) $b['createdAt'], (string) $a['createdAt']));
        return $items;
    }

    private function normalizeTextList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : (preg_split('/[\n,]+/', $value) ?: []);
        }
        if (!is_array($value)) return [];

        return array_values(array_filter(array_map(
            fn (mixed $item) => is_string($item) ? trim($item) : '',
            $value
        )));
    }

    private function statusProvided(array $validated): bool
    {
        foreach (['status', 'done', 'completed', 'is_done', 'isDone', 'is_completed', 'isCompleted'] as $key) {
            if (array_key_exists($key, $validated)) return true;
        }

        return false;
    }

    private function resolveStatus(array $validated, ?Task $task = null): string
    {
        foreach (['done', 'completed', 'is_done', 'isDone', 'is_completed', 'isCompleted'] as $key) {
            if (array_key_exists($key, $validated)) return (bool) $validated[$key] ? 'completed' : 'pending';
        }

        return array_key_exists('status', $validated) ? (string) ($validated['status'] ?? 'pending') : (string) ($task?->status ?? 'pending');
    }

    private function resolveCompletedAt(array $validated, string $status, ?Task $task = null): mixed
    {
        if (!in_array(strtolower($status), ['completed', 'done'], true)) return null;
        $explicit = $validated['completed_at'] ?? $validated['completedAt'] ?? null;
        if ($explicit) return date('Y-m-d H:i:s', strtotime((string) $explicit));
        return $task?->completed_at ?: now();
    }

    private function extractPublicPath(?string $value): ?string
    {
        $normalized = str_replace('\\', '/', trim((string) $value));
        if ($normalized === '') return null;
        if (preg_match('#/storage/(.+)$#i', $normalized, $match) === 1) return ltrim($match[1], '/');
        if (str_starts_with($normalized, 'storage/')) return ltrim(substr($normalized, 8), '/');
        if (preg_match('#^[a-z0-9_\-/]+\.[a-z0-9]+$#i', $normalized) === 1) return ltrim($normalized, '/');
        return null;
    }

    private function attachmentUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    private function isImage(?string $type, ?string $pathOrUrl): bool
    {
        $mime = strtolower((string) $type);
        if ($mime !== '' && str_starts_with($mime, 'image/')) return true;
        return preg_match('/\.(avif|bmp|gif|heic|jpe?g|png|svg|webp)(\?|#|$)/i', (string) $pathOrUrl) === 1;
    }

    private function guessMime(string $pathOrUrl): string
    {
        $value = strtolower($pathOrUrl);

        return match (true) {
            preg_match('/\.jpe?g$/i', $value) === 1 => 'image/jpeg',
            preg_match('/\.png$/i', $value) === 1 => 'image/png',
            preg_match('/\.gif$/i', $value) === 1 => 'image/gif',
            preg_match('/\.svg$/i', $value) === 1 => 'image/svg+xml',
            preg_match('/\.webp$/i', $value) === 1 => 'image/webp',
            preg_match('/\.pdf$/i', $value) === 1 => 'application/pdf',
            preg_match('/\.(docx?|rtf)$/i', $value) === 1 => 'application/msword',
            preg_match('/\.(xlsx?|csv)$/i', $value) === 1 => 'application/vnd.ms-excel',
            preg_match('/\.pptx?$/i', $value) === 1 => 'application/vnd.ms-powerpoint',
            preg_match('/\.(zip|rar|7z)$/i', $value) === 1 => 'application/zip',
            preg_match('/\.(txt|md)$/i', $value) === 1 => 'text/plain',
            default => 'application/octet-stream',
        };
    }

    private function userTasksQuery(Request $request): Builder
    {
        return Task::query()->whereBelongsTo($request->user());
    }

    private function findUserTask(Request $request, int $taskId): Task
    {
        return $this->userTasksQuery($request)->findOrFail($taskId);
    }

    private function parseEstimatedDuration(?string $duration): array
    {
        if (!$duration) return [0, 0];
        $hours = preg_match('/(\d+)\s*h/i', $duration, $hourMatch) === 1 ? (int) $hourMatch[1] : 0;
        $minutes = preg_match('/(\d+)\s*m/i', $duration, $minuteMatch) === 1 ? (int) $minuteMatch[1] : 0;
        return [max(0, $hours), max(0, min(59, $minutes))];
    }

    private function formatEstimatedDuration(int $hours, int $minutes): ?string
    {
        $parts = [];
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        return $parts === [] ? null : implode(' ', $parts);
    }
}
