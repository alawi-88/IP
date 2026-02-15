<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;

class NotificationManagement extends Model
{
    use HasFactory, LogsActivity, HasActivityLog;

    protected $fillable = [
        'title',
        'body',
        'user_type',
        'competition_id',
        'user_ids',
        'recipient_count',
        'admin_id',
        'send_email'
    ];

    protected $casts = [
        'user_ids' => 'array',
        'send_email' => 'boolean',
    ];

    protected array $logFields = [
        'title',
        'body',
        'user_type',
        'user_ids',
        'recipient_count',
        'send_email',
        'admin.name',
        'competition.title',
        'competition_id'
    ];

    protected string $moduleName = 'Notification Management';
    protected string $logName = 'notification_management';

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }


    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function users()
    {
        return User::whereIn('id', $this->user_ids ?? []);
    }
}

