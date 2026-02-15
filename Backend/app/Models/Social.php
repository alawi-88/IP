<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Social extends Model
{
    use LogsActivity, HasActivityLog;

    protected array $logFields = [
        'name',
        'url'
    ];

    protected string $moduleName = 'Social Link';
    protected string $logName = 'social';

    protected $fillable = ['name', 'url'];
}
