<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\UpdateAdminAccount;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Resolve the record and check authorization
     */
    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);

        // Check if the current user is authorized to edit this admin
        if (!UserResource::canEdit($record)) {
            abort(404);
        }

        return $record;
    }

    /**
     * Mount the component and check if record is archived
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Prevent editing archived records - they can only be deleted or restored
        if ($this->record->isArchived()) {
            \Filament\Notifications\Notification::make()
                ->title('Cannot Edit Archived Admin / لا يمكن تعديل مسؤول مؤرشف')
                ->body('Archived admins cannot be edited. You can only delete or restore them. / لا يمكن تعديل المسؤولين المؤرشفين. يمكنك فقط حذفهم أو استعادتهم.')
                ->warning()
                ->send();

            $this->redirect(UserResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->id !== auth()->id()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function beforeSave(): void
    {
        $this->oldRoles = $this->record->roles()->pluck('name')->toArray();
    }
    protected function afterSave(): void
    {
        $actor = auth()->user();
        $user = $this->record;

        $password = Str::random(10);

        $this->record->forceFill([
            'password' => bcrypt($password),
        ])->save();

        $this->record->refresh()->load('roles');

        // send notification
        $this->record->notify(new UpdateAdminAccount($this->record, $password));

        $oldRoles = $this->oldRoles;

        $newRoles = $user->roles->pluck('name')->toArray();

        $oldRolesList = implode(', ', $oldRoles) ?: 'None';
        $newRolesList = implode(', ', $newRoles) ?: 'None';

        activity('role-update')
            ->causedBy($actor)
            ->performedOn($user)
            ->withProperties([
                'updated_user' => $user->name,
                'old_roles' => $oldRoles,
                'new_roles' => $newRoles,
            ])
            ->log("{$actor->name} changed {$user->name}'s role(s) from '{$oldRolesList}' to '{$newRolesList}' on " . now()->format('Y-m-d H:i'));
    }
}
