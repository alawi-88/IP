<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity; 


class NotificationMessage extends Model
{   
    use HasFactory, LogsActivity, HasActivityLog;

    protected $fillable = [ 'key', 'subject', 'body', 'type', 'is_default'];

    protected $casts = [
        'subject' => 'array',
        'body' => 'array',
        'type' => 'string',
        'is_default' => 'boolean',
    ];

    protected array $logFields = [
        'subject',
        'body', 
    ];

    protected string $moduleName = 'NotificationMessage';
    protected string $logName = 'notification_messages';
}
