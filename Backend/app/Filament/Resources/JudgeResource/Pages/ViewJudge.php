<?php

namespace App\Filament\Resources\JudgeResource\Pages;

use App\Filament\Resources\JudgeResource;
use App\Models\Judge;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewJudge extends ViewRecord
{
    protected static string $resource = JudgeResource::class;

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
                ->modalHeading('Restore Judge / استعادة الحكم')
                ->modalDescription('Are you sure you want to restore this judge? / هل أنت متأكد من استعادة هذا الحكم؟')
                ->authorize(fn () => JudgeResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    \Filament\Notifications\Notification::make()
                        ->title('Judge Restored / تم استعادة الحكم')
                        ->body('The judge has been restored successfully. / تم استعادة الحكم بنجاح.')
                        ->success()
                        ->send();
                    
                    $this->redirect(JudgeResource::getUrl('index'));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(Judge::details());
    }
}
