<?php

namespace App\Filament\Resources\MentorVideoToolResource\Pages;

use App\Filament\Resources\MentorVideoToolResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMentorVideoTool extends EditRecord
{
    protected static string $resource = MentorVideoToolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
