<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'priority',
        'assignee',
        'project',
        'department',
        'client_name',
        'location',
        'status_note',
        'lane',
        'due_date',
        'estimated_hours',
        'estimated_minutes',
        'attachment',
        'attachments',
        'checkpoints',
        'tags',
        'comments',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'completed_at' => 'datetime',
        'estimated_hours' => 'integer',
        'estimated_minutes' => 'integer',
        'attachments' => 'array',
        'checkpoints' => 'array',
        'tags' => 'array',
        'comments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
