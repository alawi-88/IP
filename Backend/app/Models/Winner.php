<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Winner extends Model
{
    use HasFactory, LogsActivity, HasActivityLog;

    protected $fillable = [
        'competition_id',
        'track_id',
        'rank',
        'name',
        'subtitle',
        'image',
        'is_visible',
        'notes',
    ];
    protected $casts = [
        'name' => 'array',
        'subtitle' => 'array',
    ];

    public array $translatable = ['name', 'subtitle'];

    protected array $logFields = [
        'rank',
        'name',
        'subtitle',
        'image',
        'competition.title',
        'competition_id',
        'track.name'
    ];

    protected string $moduleName = 'Winner';

    protected string $logName = 'Winner';

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }


    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
