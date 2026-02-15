<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequestLevelVote extends Model
{
    protected $fillable = [
        'approval_request_level_id',
        'user_id',
        'decision',
        'comment',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequestLevel::class, 'approval_request_level_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


