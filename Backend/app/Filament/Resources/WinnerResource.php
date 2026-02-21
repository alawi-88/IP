<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WinnerResource\Pages;
use App\Filament\Resources\WinnerResource\RelationManagers;
use App\Models\Program;
use App\Models\UserProgram;
use App\Models\Winner;
use App\Models\Track;
use App\Services\WinnerApprovalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Filament\Notifications\Notification;


class WinnerResource extends Resource
{
    protected static ?string $model = Winner::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Winners';
    protected static ?string $pluralModelLabel = 'Winners';
    protected static ?string $modelLabel = 'Winner';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationGroup = 'Programs';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Winner Details')
                ->description('Add or edit winner details here')
                ->columns(2)
                ->schema([

                    Forms\Components\Select::make('program_id')
                        ->label('Program')
                        ->placeholder('Select a program')
                        ->options(function () {
                            $user = auth()->user();

                            if ($user->isSuperAdmin()) {
                                return Program::pluck('title', 'id');
                            }

                            $programIds = UserProgram::where('user_id', $user->id)
                                ->pluck('program_id');

                            return Program::whereIn('id', $programIds)->pluck('title', 'id');
                        })
                        ->required()
                        ->reactive()
                        ->searchable()
                        ->preload()
                        ->afterStateUpdated(fn($state, callable $set) => $set('track_id', null)),


                    Forms\Components\Select::make('track_id')
                        ->label('Track (Optional)')
                        ->placeholder('Select a track')
                        ->options(function (callable $get) {
                            $programId = $get('program_id');
                            if (!$programId) return [];
                            return Track::where('program_id', $programId)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->visible(fn(callable $get) => filled($get('program_id')))
                        ->nullable()
                        ->helperText('Select a track if applicable.'),

                    Forms\Components\TextInput::make('rank')
                        ->label('Ranking Position')
                        ->placeholder('1 for first place, 2 for second, etc.')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(6)
                        ->required()
                        ->rule(function (callable $get) {
                            return Rule::unique('winners', 'rank')
                                ->where(function ($query) use ($get) {
                                    $query->where('program_id', $get('program_id'))
                                        ->where('track_id', $get('track_id'));
                                })
                                ->ignore($get('id') ?? null);
                        }),

                    Forms\Components\TextInput::make('name.en')
                        ->label('Winner Name or Team Name')
                        ->placeholder('e.g. John Doe or Team Alpha')
                        ->required()
                        ->maxLength(255),
                        Forms\Components\TextInput::make('name.ar')
                        ->label('اسم الفائز أو اسم الفريق')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->placeholder('أدخل اسم الفائز أو اسم الفريق باللغة العربية')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('subtitle.en')
                        ->label('Subtitle (Project Title or Prize Info)')
                        ->placeholder('Optional subtitle')
                        ->nullable()
                        ->maxLength(500),
                        Forms\Components\TextInput::make('subtitle.ar')
                        ->label('العنوان الفرعي (عنوان المشروع أو معلومات الجائزة)')
                        ->extraFieldWrapperAttributes(['class' => 'text-right'])
                        ->placeholder('عنوان فرعي اختياري')
                        ->nullable()
                        ->maxLength(500),

                    Forms\Components\FileUpload::make('image')
                        ->label('Winner Image')
                        ->image()
                        ->directory('winners')
                        ->imagePreviewHeight('120')
                        ->nullable()
                        ->columnSpanFull()
                        ->helperText('Upload a clear photo of the winner or team.'),
                ]),

            Forms\Components\Section::make('Visibility & Notes')
                ->columns(1)
                ->collapsed()
                ->schema([
                    Forms\Components\Toggle::make('is_visible')
                        ->label('Show this winner on public page')
                        ->default(true),

                    Forms\Components\Textarea::make('notes')
                        ->label('Additional Notes')
                        ->rows(3)
                        ->placeholder('Any extra info or remarks (optional)')
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();
                if ($user->isSuperAdmin()) {
                    return $query;
                }

                $supervisorPrograms = UserProgram::where('user_id', $user->id)
                    ->pluck('program_id')
                    ->toArray();

                return $query->whereHas('program', function ($q) use ($supervisorPrograms) {
                    $q->whereIn('programs.id', $supervisorPrograms);
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('program.title')->label('Program')->sortable(),
                Tables\Columns\TextColumn::make('track.name')->label('Track')->sortable(),
                Tables\Columns\TextColumn::make('rank')->label('Rank')->sortable(),
                Tables\Columns\TextColumn::make('name.en')->label('Winner Name')->searchable(),
                Tables\Columns\TextColumn::make('subtitle.en')->label('Subtitle')->limit(30),
                Tables\Columns\ImageColumn::make('image')->label('Image'),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->action(
                        Tables\Actions\Action::make('toggleVisibility')
                            ->requiresConfirmation()
                            ->modalHeading(fn(Model $record) => $record->is_visible ? 'Hide this record?' : 'Show this record again?')
                            ->modalDescription(fn(Model $record) => $record->is_visible
                                ? 'Hidden records are no longer visible to participants.'
                                : 'This record will become visible to all participants.')
                            ->modalSubmitActionLabel(fn(Model $record) => $record->is_visible ? 'Yes, hide it' : 'Yes, show it')
                            ->modalCancelActionLabel('Cancel')
                            ->color(fn($record) => $record->is_visible ? 'danger' : 'success')
                            ->visible(fn () => auth()->user()->can('update Winner'))
                            ->form([
                                Forms\Components\Textarea::make('reason')
                                    ->label('Reason / السبب')
                                    ->rows(2)
                                    ->placeholder('Optional reason for this change / سبب اختياري لهذا التغيير')
                                    ->nullable(),
                            ])
                            ->action(function (array $data, Model $record) {
                                $approvalService = new WinnerApprovalService();

                                $result = $approvalService->processAction(
                                    'toggle_visibility',
                                    [
                                        'winner_id' => $record->id,
                                        'is_visible' => !$record->is_visible,
                                        'old_values' => ['is_visible' => $record->is_visible],
                                    ],
                                    $record->id,
                                    $data['reason'] ?? 'Winner visibility change request / طلب تغيير ظهور الفائز'
                                );

                                if ($result['success'] ?? false) {
                                    if ($result['requires_approval'] ?? false) {
                                        Notification::make()
                                            ->title('Request Submitted for Approval / تم تقديم الطلب للموافقة')
                                            ->body('Visibility change request submitted for approval. / تم تقديم طلب تغيير الظهور للموافقة.')
                                            ->success()
                                            ->send();
                                        return;
                                    }

                                    // No workflow => apply directly
                                    $record->update(['is_visible' => !$record->is_visible]);

                                    Notification::make()
                                        ->title('Visibility Updated / تم تحديث الظهور')
                                        ->body('Winner visibility updated successfully. / تم تحديث ظهور الفائز بنجاح.')
                                        ->success()
                                        ->send();
                                    return;
                                }

                                Notification::make()
                                    ->title('Error / خطأ')
                                    ->body($result['message'] ?? 'Failed to submit request / فشل في تقديم الطلب')
                                    ->danger()
                                    ->send();
                            })
                    )
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit (Approval) / تعديل (موافقة)'),

                Tables\Actions\Action::make('delete')
                    ->label('Delete / حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Winner / حذف الفائز')
                    ->modalDescription('Are you sure you want to delete this winner? This action will be submitted for approval. / هل أنت متأكد من حذف هذا الفائز؟ سيتم تقديم هذا الإجراء للموافقة.')
                    ->authorize(fn (Model $record) => auth()->user()?->can('delete Winner') ?? false)
                    ->action(function (Model $record) {
                        $approvalService = new WinnerApprovalService();

                        $result = $approvalService->processAction(
                            'delete',
                            [
                                'winner_id' => $record->id,
                                'old_values' => $record->toArray(),
                            ],
                            $record->id,
                            'Winner deletion request / طلب حذف فائز'
                        );

                        if ($result['success'] ?? false) {
                            if ($result['requires_approval'] ?? false) {
                                Notification::make()
                                    ->title('Request Submitted / تم تقديم الطلب')
                                    ->body('Your deletion request has been submitted for approval. / تم تقديم طلب الحذف للموافقة.')
                                    ->success()
                                    ->send();
                                return;
                            }

                            $record->delete();
                            Notification::make()
                                ->title('Winner Deleted / تم حذف الفائز')
                                ->body('The winner has been deleted successfully. / تم حذف الفائز بنجاح.')
                                ->success()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Error / خطأ')
                            ->body($result['message'] ?? 'Failed to submit deletion request / فشل في تقديم طلب الحذف')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // Bulk actions are intentionally disabled for Winners because updates/deletes
                // must go through the approval workflow.
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWinners::route('/'),
            'create' => Pages\CreateWinner::route('/create'),
            'edit' => Pages\EditWinner::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view Winner') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create Winner') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update Winner');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete Winner');
    }
}
