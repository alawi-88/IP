<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Model;

trait CanBeDeletable
{
    public static function canDelete(Model $record): bool
    {
        return ! auth()->user()?->hasRole('coordinator');
    }

}
