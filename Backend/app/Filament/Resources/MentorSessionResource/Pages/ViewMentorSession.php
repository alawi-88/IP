<?php

namespace App\Filament\Resources\MentorSessionResource\Pages;

use App\Filament\Resources\MentorSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMentorSession extends ViewRecord
{
    protected static string $resource = MentorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Admins cannot edit sessions - only view
            // Actions\EditAction::make(),
        ];
    }
}
