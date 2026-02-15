<?php

namespace App\Filament\Resources\GuidelineResource\Pages;

use App\Filament\Resources\GuidelineResource;
use App\Models\Guideline;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewGuideline extends ViewRecord
{
    protected static string $resource = GuidelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => !$this->record->isArchived()),
            Actions\Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Guideline / استعادة الإرشادات')
                ->modalDescription('Are you sure you want to restore this guideline? / هل أنت متأكد من استعادة هذه الإرشادات؟')
                ->authorize(fn () => GuidelineResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    \Filament\Notifications\Notification::make()
                        ->title('Guideline Restored / تم استعادة الإرشادات')
                        ->body('The guideline has been restored successfully. / تم استعادة الإرشادات بنجاح.')
                        ->success()
                        ->send();
                    
                    $this->redirect(GuidelineResource::getUrl('index'));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(Guideline::details());
    }
}
