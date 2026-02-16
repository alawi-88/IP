<?php

namespace App\Filament\Resources\TaskAssignmentResource\RelationManagers;

use App\Models\TaskComment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments / التعليقات';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('body')
                ->label('Comment / تعليق')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_internal')
                ->label('Internal (admin-only) / داخلي')
                ->default(false)
                ->helperText('Internal comments are not visible to participants.'),

            Forms\Components\Hidden::make('commentable_type')
                ->default('App\\Models\\User'),

            Forms\Components\Hidden::make('commentable_id')
                ->default(fn () => auth()->id()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('commenter')
                    ->label('By')
                    ->getStateUsing(function ($record) {
                        if ($record->commentable_type === 'App\\Models\\User') {
                            return $record->commentable?->name . ' (Admin)';
                        }
                        return $record->commentable?->name . ' (Participant)';
                    }),

                Tables\Columns\TextColumn::make('body')
                    ->label('Comment')
                    ->limit(80)
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_internal')
                    ->label('Internal')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-globe-alt'),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Comment'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->commentable_type === 'App\\Models\\User' && $record->commentable_id === auth()->id()),
            ]);
    }
}
