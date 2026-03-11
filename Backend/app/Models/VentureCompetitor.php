<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentureCompetitor extends Model
{
    protected $fillable = [
        'venture_id',
        'name',
        'description',
        'strengths',
        'weaknesses',
        'market_share',
        'source_url',
    ];

    protected $casts = [
        'strengths' => 'array',
        'weaknesses' => 'array',
    ];

    /**
     * Get the venture this competitor belongs to.
     */
    public function venture(): BelongsTo
    {
        return $this->belongsTo(Venture::class);
    }
}
