<?php

namespace App\Filament\Resources\JudgeContactUsResource\Pages;

use App\Filament\Resources\JudgeContactUsResource;
use App\Models\ContactUs;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Carbon\Carbon;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Placeholder;

class ViewContactUs extends ViewRecord
{
    protected static string $resource = JudgeContactUsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label(fn($record) => $record->isReplied() ? 'View Reply' : 'Reply')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->modalHeading(fn($record) => $record->isReplied() ? 'View Reply' : 'Reply to Contact Us')
                ->form(
                    fn($record) => $record->isReplied()
                        ? [
                            Placeholder::make('replied_at')
                                ->label('Reply Date')
                                ->content($record->replied_at ? Carbon::parse($record->replied_at)->format('Y-m-d H:i') : '-'),
                            RichEditor::make('reply')
                                ->label('Reply')
                                ->disabled()
                                ->default($record->reply),
                        ]
                        : [
                            RichEditor::make('reply')
                                ->label('Reply')
                                ->required(),
                        ]
                )
                ->action(function ($record, array $data) {
                    if (!$record->isReplied()) {
                        $record->reply = $data['reply'];
                        $record->status = 'resolved';
                        $record->replied_by = auth()->id();
                        $record->replied_at = now();
                        $record->save();
                    }
                })
                ->modalSubmitAction(fn($record) => $record->reply ? false : null)
                ->modalCancelAction(fn() => null)
                ->visible(fn () => !$this->record->isArchived() && auth()->user()?->can('update ContactUs') ?? false),
            Actions\Action::make('restore')
                ->label('Restore / استعادة')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Contact Us / استعادة التواصل')
                ->modalDescription('Are you sure you want to restore this contact us record? / هل أنت متأكد من استعادة سجل التواصل هذا؟')
                ->authorize(fn () => JudgeContactUsResource::canRestore($this->record))
                ->visible(fn () => $this->record->isArchived())
                ->action(function () {
                    $this->record->restore();
                    \Filament\Notifications\Notification::make()
                        ->title('Contact Us Restored / تم استعادة التواصل')
                        ->body('The contact us record has been restored successfully. / تم استعادة سجل التواصل بنجاح.')
                        ->success()
                        ->send();
                    
                    $this->redirect(JudgeContactUsResource::getUrl('index'));
                }),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(ContactUs::details());
    }
}
