<?php

namespace App\Filament\Resources\MentorVideoToolResource\Pages;

use App\Filament\Resources\MentorVideoToolResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMentorVideoTools extends ListRecords
{
    protected static string $resource = MentorVideoToolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since video tools are created through OAuth flow
        ];
    }
}
