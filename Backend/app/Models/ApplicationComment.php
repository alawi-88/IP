<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicationComment extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'application_id',
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

    protected array $logFields = [
        'application_id',
        'user_id',
        'comment',
        'attachments',
        'is_read',
        'author_id',
        'author_type',
    ];

    protected string $moduleName = 'Application Comment';
    protected string $logName = 'application_comment';

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProgramApplication::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }
}
