<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (object $record) => $record->users()->count() === 0)
                ->after(function ($record, $livewire) {
                    $actor = auth()->user();

                    activity('Role')
                        ->causedBy($actor)
                        ->performedOn($record)
                        ->event('deleted')
                        ->withProperties([
                            'affected_entity' => "Role: {$record->name}",
                            'role_name' => $record->name,
                        ])
                        ->log("{$actor->name} deleted role '{$record->name}'");
                })


        ];
    }

}
