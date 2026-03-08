<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BrandingSetting extends Model
{
    use HasFactory;

    protected $table = 'branding_settings';

    protected $fillable = [
        'logo',
        'white_logo',
        'favicon',
        'primary_color',
        'secondary_color',
        'font',
        'email_bg_color',
        'email_text_color',
        'email_link_color',
        'email_border_color',
        'email_footer',
        'email_logo',
        'email_footer_footer',
    ];

    /**
     * Get the branding data with full URLs for images
     */
    /**
     * Get the current branding settings (alias for first())
     */
    public static function current(): ?self
    {
        return static::first();
    }

    public static function getWithUrls(): ?self
    {
        $branding = static::first();
        if (!$branding) {
            return null;
        }

        $imageFields = ['logo', 'white_logo', 'favicon', 'email_logo', 'email_footer_footer'];
        foreach ($imageFields as $field) {
            if ($branding->$field) {
                $branding->$field = Storage::url($branding->$field);
            }
        }

        return $branding;
    }
}

