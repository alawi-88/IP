<?php

namespace App\Filament\Resources\StageResource\Pages;

use App\Filament\Resources\StageResource;
use App\Models\Stage;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditStage extends EditRecord
{
    protected static string $resource = StageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Stage')
                ->modalDescription(function () {
                    if ($this->record->slug === 'team-formation') {
                        return 'This stage cannot be deleted because it is a team-formation stage.';
                    }
                    $formIds = $this->record->getFormIds();
                    if (!empty($formIds)) {
                        return 'This stage cannot be deleted because it is linked to one or more forms.';
                    }
                    return 'Are you sure you want to delete this stage? This action cannot be undone.';
                })
                ->disabled(fn () => $this->record->slug === 'team-formation' || !empty($this->record->getFormIds()))
                ->tooltip(function () {
                    if ($this->record->slug === 'team-formation') {
                        return 'Cannot delete team-formation stage';
                    }
                    $formIds = $this->record->getFormIds();
                    if (!empty($formIds)) {
                        return 'Cannot delete stage linked to forms';
                    }
                    return 'Delete stage';
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema(Stage::form());
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.admin.resources.stages.index');
    }
}
