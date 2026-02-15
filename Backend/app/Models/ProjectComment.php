<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ProjectComment extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'project_id',
        'user_id',
        'comment',
        'attachments',
        'is_read',
        'author_id',
        'author_type',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
    ];

    protected $appends = ['is_comment'];

    protected array $logFields = [
        'project_id',
        'user_id',
        'comment',
        'attachments',
        'is_read',
        'author_id',
        'author_type',
    ];

    protected string $moduleName = 'Project Comment';
    protected string $logName = 'project_comment';


    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function getIsCommentAttribute(): bool
    {
        return !$this->is_read && $this->author_type === \App\Models\Participant::class;
    }
}
