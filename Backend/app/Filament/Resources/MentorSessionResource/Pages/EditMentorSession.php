<?php

namespace App\Filament\Resources\MentorSessionResource\Pages;

use App\Filament\Resources\MentorSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMentorSession extends EditRecord
{
    protected static string $resource = MentorSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
