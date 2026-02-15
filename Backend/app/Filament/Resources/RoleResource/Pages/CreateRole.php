<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $actor = auth()->user();
        $role = $this->record;
        $timestamp = now()->format('Y-m-d H:i');
        activity('Role')
            ->causedBy($actor)
            ->performedOn($role)
            ->event('created')
            ->withProperties([
                'role_name' => $role->name,
            ])
            ->log("{$actor->name} created new role '{$role->name}' on {$timestamp}");
    }
}
