<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    protected $fillable = ['title', 'content', 'government_verification_banner_enabled', 'dga_registration_number', 'dga_certificate_url'];
    protected $casts = [
        'content' => 'array',
        'government_verification_banner_enabled' => 'boolean',
    ];
    public $translatable = ['content'];
    // public function getContentAttribute($value)
    // {
    //     $content = json_decode($value, true);

    //     if (!$content) return [];

    //     array_walk_recursive($content, function (&$item) {
    //         if (is_string($item) && str_starts_with($item, 'uploads/')) {
    //             $item = asset('storage/' . $item);
    //         }
    //     });

    //     return $content;
    // }
}
