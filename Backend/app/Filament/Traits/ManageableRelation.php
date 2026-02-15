<?php

namespace App\Filament\Traits;

trait ManageableRelation
{
    public function isReadOnly(): bool
    {
        return false;
    }
}
