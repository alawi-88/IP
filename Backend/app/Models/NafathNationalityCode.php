<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NafathNationalityCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_en',
        'name_ar',
    ];

    /**
     * Get the localized name based on current locale
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?? $this->name_en;
    }

    /**
     * Find nationality code by Nafath code
     */
    public static function findByNafathCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get nationality name from Nafath code
     */
    public static function getNationalityNameFromCode(string $code): ?string
    {
        $nafathCode = static::findByNafathCode($code);

        if (!$nafathCode) {
            return null;
        }

        // Return the localized name from this table
        return $nafathCode->name;
    }
}
