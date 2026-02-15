<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\NewAdminAccount;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function afterCreate(): void
    {
        $actor = auth()->user();
        $user = $this->record;

        $password = Str::random(10);

        $this->record->forceFill([
            'password' => bcrypt($password),
        ])->save();

        $this->record->refresh()->load('roles');

        $this->record->notify(new NewAdminAccount($this->record, $password));

        // (Logging)
        $roles = $user->roles->pluck('name')->toArray();
        $rolesList = implode(', ', $roles) ?: 'None';

        activity('role-assignment')
            ->causedBy($actor)
            ->performedOn($user)
            ->withProperties([
                'new_user' => $user->name,
                'roles' => $roles,
            ])
            ->log("{$actor->name} created new user '{$user->name}' with role(s): '{$rolesList}' on " . now()->format('Y-m-d H:i'));

    }
}
