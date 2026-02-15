<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use Spatie\Activitylog\Models\Activity as SpatieActivity;
use Spatie\SchemalessAttributes\Casts\SchemalessAttributes;


class ActivityLog extends SpatieActivity
{
    protected $table = 'activity_log';



    public $casts = [
        'properties' => SchemalessAttributes::class,
    ];

    public function causer(): MorphTo
    {
        return parent::causer();
    }

    public function getChangesListAttribute(): array
    {
        $old = $this->properties['old'] ?? [];
        $new = $this->properties['attributes'] ?? [];

        $changes = [];

        foreach ($new as $key => $value) {
            $changes[$key] = [
                'old' => $old[$key] ?? null,
                'new' => $value,
            ];
        }

        return $changes;
    }


}
