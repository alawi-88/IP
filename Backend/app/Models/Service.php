<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasActivityLog;

class Service extends Model
{
    use HasTranslations, HasFactory, LogsActivity, HasActivityLog;

    protected array $logFields = [
        'title',
        'metadata',
        'content',
        'relatedServices',
        'is_published'
    ];

    public array $translatable = ['title', 'content', 'metadata', 'relatedServices'];

    protected $fillable = ['title', 'metadata', 'content', 'relatedServices', 'is_published', 'order'];

    protected $casts = [
        'is_published' => 'boolean',
        'metadata' => 'array',
        'content' => 'array',
        'relatedServices' => 'array',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
    protected static function booted()
{
    static::creating(function ($service) {
        // إذا لم يتم تحديد order، نحسبه تلقائيًا
        if (is_null($service->order) || $service->order < 1) {
            $maxOrder = static::max('order') ?? 0;
            $service->order = $maxOrder > 0 ? $maxOrder + 1 : 1;
        }
    });
}

}
