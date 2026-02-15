<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class EmailTemplate extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = ['key', 'subject', 'body', 'is_default'];

    protected $casts = [
        'subject' => 'array',
        'body' => 'array',
        'is_default' => 'boolean',
    ];

    protected array $logFields = [
        'subject',
        'body',
    ];

    protected string $moduleName = 'EmailTemplate';
    protected string $logName = 'email_templates';
}
