<?php

namespace App\Filament\Resources\GuidelineResource\RelationManagers;

use App\Filament\Traits\ManageableRelation;
use App\Models\GuidelineFile;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class FilesRelationManager extends RelationManager
{
    use ManageableRelation;

    protected static string $relationship = 'files';

    public function form(Form $form): Form
    {
        return $form->schema(GuidelineFile::form())->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns(GuidelineFile::columns())
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['attachment'] = $data['attachment_video']
                            ?? $data['attachment_document']
                            ?? $data['attachment_image']
                            ?? null;

                        if (!empty($data['attachment_video'])) {
                            $data['file_type'] = 'video';
                        } elseif (!empty($data['attachment_document'])) {
                            $data['file_type'] = 'document';
                        } elseif (!empty($data['attachment_image'])) {
                            $data['file_type'] = 'image';
                        }

                        unset($data['attachment_video'], $data['attachment_document'], $data['attachment_image']);

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('File uploaded successfully / تم رفع الملف بنجاح')
                            ->body('The file has been uploaded and is now available to participants / تم رفع الملف وهو متاح الآن للمشاركين')
                    )
                    ->failureNotification(
                        Notification::make()
                            ->danger()
                            ->title('Upload failed / فشل الرفع')
                            ->body('Please check the file format and size, then try again / يرجى التحقق من تنسيق الملف والحجم، ثم حاول مرة أخرى')
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['attachment'] = $data['attachment_video']
                        ?? $data['attachment_document']
                        ?? $data['attachment_image']
                        ?? null;

                    if (!empty($data['attachment_video'])) {
                        $data['file_type'] = 'video';
                    } elseif (!empty($data['attachment_document'])) {
                        $data['file_type'] = 'document';
                    } elseif (!empty($data['attachment_image'])) {
                        $data['file_type'] = 'image';
                    }

                    unset($data['attachment_video'], $data['attachment_document'], $data['attachment_image']);

                    return $data;
                })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('File updated successfully / تم تحديث الملف بنجاح')
                            ->body('The file has been updated and changes are now available to participants / تم تحديث الملف والتغييرات متاحة الآن للمشاركين')
                    )
                    ->failureNotification(
                        Notification::make()
                            ->danger()
                            ->title('Update failed / فشل التحديث')
                            ->body('Please check the file format and size, then try again / يرجى التحقق من تنسيق الملف والحجم، ثم حاول مرة أخرى')
                    )->visible(fn () => !$this->getOwnerRecord()->isArchived()),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('File deleted successfully / تم حذف الملف بنجاح')
                            ->body('The file has been removed from the guidelines / تم إزالة الملف من الإرشادات')
                    )
                    ,
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(GuidelineFile::details());
    }

    protected function canCreate(): bool
    {
        return !$this->getOwnerRecord()->isArchived() && $this->getOwnerRecord()->files->count() < 4;
    }
}
