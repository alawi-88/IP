<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DisclaimerAcceptance extends Model
{
    use HasFactory;

    protected $fillable = [
        'judge_id',
        'form_id',
        'stage_id',
        'accepted',
        'accepted_at',
    ];

    protected $casts = [
        'accepted'    => 'boolean',
        'accepted_at' => 'datetime',
    ];

    public function judge()
    {
        return $this->belongsTo(Judge::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }
}
