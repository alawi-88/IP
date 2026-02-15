<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class SubTrack extends Model
{
    use HasFactory,HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'track_id',
        'name',
        'slug',
        'order',
    ];

    protected $casts = [
        'name' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subTrack) {
            if (empty($subTrack->slug) && !empty($subTrack->name)) {
                $subTrack->slug = $subTrack->generateUniqueSlug();
            }
        });

        static::updating(function ($subTrack) {
            // If name changed, regenerate slug to ensure uniqueness
            if ($subTrack->isDirty('name') && !empty($subTrack->name)) {
                $subTrack->slug = $subTrack->generateUniqueSlug();
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
     * Generate a unique slug from the subtrack name
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

