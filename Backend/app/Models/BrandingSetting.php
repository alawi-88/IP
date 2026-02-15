<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class BrandingSetting extends Model
{
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
    public static function current()
    {
        $branding = self::first();

    // تحقق أولًا من وجود سجل
    if (!$branding) {
        // ترجع كائن افتراضي بدون كسر التطبيق
        return (object) [
           'logo' => null,
            'white_logo' => null,
            'favicon' => null,
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF',
            'font' => 'default',
            'email_bg_color' => '#FFFFFF',
            'email_text_color' => '#000000',
            'email_link_color' => '#007BFF',
            'email_border_color' => '#DDDDDD',
            'email_footer' => null,
            'email_logo' => null,
            'email_footer_footer' => null,
        ];
    }

    if ($branding->logo) {
        $branding->logo = Storage::url($branding->logo);
    }
    if ($branding->white_logo) {
        $branding->white_logo = Storage::url($branding->white_logo);
    }
    if ($branding->favicon) {
        $branding->favicon = Storage::url($branding->favicon);
    }
    if ($branding->email_logo) {
        $branding->email_logo = Storage::url($branding->email_logo);
    }
    if ($branding->email_footer_footer) {
        $branding->email_footer_footer = Storage::url($branding->email_footer_footer);
    }
        return $branding;
    }
    
    
}
