<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected string $oldName = '';
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
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

    /**
     * Log after a successful update
     */

    protected function beforeSave(): void
    {
        $this->oldName = $this->record->getOriginal('name') ?? '';
    }
    protected function afterSave(): void
    {
        $actor = auth()->user();
        $role = $this->record;

        $oldName = $this->oldName;
        $newName = $role->name;
        $timestamp = now()->format('Y-m-d H:i');

        activity('Role')
            ->causedBy($actor)
            ->performedOn($role)
            ->event('updated')
            ->withProperties([
                'old_role' => $oldName,
                'new_role' => $newName,
            ])
            ->log("{$actor->name} changed {$role->name}'s role(s) from '{$oldName}' to '{$newName}' on {$timestamp}");
    }



}
