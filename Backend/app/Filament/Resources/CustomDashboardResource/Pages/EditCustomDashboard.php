<?php

namespace App\Filament\Resources\CustomDashboardResource\Pages;

use App\Filament\Resources\CustomDashboardResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCustomDashboard extends EditRecord
{
    protected static string $resource = CustomDashboardResource::class;

    protected function resolveRecord(string|int $key): Model
    {
        $record = parent::resolveRecord($key);

        if (!CustomDashboardResource::canEdit($record)) {
            abort(404);
        }

        return $record;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->isArchived()) {
            Notification::make()
                ->title('Cannot edit archived dashboard')
                ->warning()
                ->send();

            $this->redirect(CustomDashboardResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->modalHeading(__('dashboard.confirm_delete')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title(__('dashboard.dashboard_updated'))
            ->success();
    }
}
