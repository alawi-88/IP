<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentureVersion extends Model
{
    protected $fillable = [
        'venture_section_id',
        'content',
        'content_ar',
        'version_number',
        'edited_by',
        'change_note',
    ];

    protected $casts = [
        'content' => 'array',
        'content_ar' => 'array',
    ];

    /**
     * Get the section this version belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(VentureSection::class, 'venture_section_id');
    }

    /**
     * Get the user who edited this version.
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
