<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Track extends Model
{
    use HasFactory, HasTranslations, LogsActivity, HasActivityLog;

    public array $translatable = ['name'];

    protected $fillable = [
        'competition_id',
        'name',
        'slug',
        'order',
    ];

    protected $casts = [
        'name' => 'array',
    ];

    protected array $logFields = [
        'name',
        'order',
        'competition.title',
        'competition_id',
    ];

    protected string $moduleName = 'Track';
    protected string $logName = 'track';

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function subTracks()
    {
        return $this->hasMany(SubTrack::class)->orderBy('order', 'asc');
    }

    public function winners()
    {
        return $this->hasMany(Winner::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($track) {
            if (empty($track->slug) && !empty($track->name)) {
                $track->slug = $track->generateUniqueSlug();
            }
        });

        static::updating(function ($track) {
            // If name changed, regenerate slug to ensure uniqueness
            if ($track->isDirty('name') && !empty($track->name)) {
                $track->slug = $track->generateUniqueSlug();
            }
        });
    }

    public function setSlugAttribute($value)
    {
        // If slug is being set manually and not empty, use it
        if (!empty($value)) {
            $this->attributes['slug'] = $value;
        } else {
            // If slug is empty, it will be generated in the boot method from name
            $this->attributes['slug'] = null;
        }
    }

    /**
     * Generate a unique slug from the track name
     */
    protected function generateUniqueSlug(): string
    {
        $label = json_decode($this->attributes['name'] ?? '{}', true);
        $nameValue = $label['en'] ?? '';
        
        if (empty($nameValue)) {
            return '';
        }

        $baseSlug = $this->toSnakeCase($nameValue);
        $slug = $baseSlug;
        $counter = 1;

        // Check for existing slug and make it unique
        while (static::where('slug', $slug)
            ->where('id', '!=', $this->id ?? 0)
            ->exists()) {
            $slug = $baseSlug . '_' . $counter;
            $counter++;
        }

        return $slug;
    }

    // --- Helpers ---
    protected function toSnakeCase(string $value): string
    {
        $value = preg_replace('/\s+/', '_', trim($value));               // spaces to underscores
        $value = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);     // camelCase to snake_case
        return strtolower($value);
    }

}

