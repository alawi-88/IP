<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandingProgramResource\Pages;
use App\Filament\Resources\BrandingProgramResource\RelationManagers;
use App\Models\BrandingProgram;
use App\Models\UserProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;;
use Illuminate\Database\Eloquent\Model;
use App\Models\Program;
use App\Models\Font;
use Filament\Tables\Columns\ImageColumn;
use App\Services\GoogleFontsService;
use Illuminate\Support\Str;
class BrandingProgramResource extends Resource
{
    protected static ?string $model = BrandingProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Branding Programs';
    protected static ?string $modelLabel = 'Branding Program';
protected static ?string $pluralModelLabel = 'Branding Programs';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            //Program
            Forms\Components\Select::make('program_id')
            ->label('Program')
                ->options(function () {
                    $user = auth()->user();

                    if ($user->isSuperAdmin()) {
                        return Program::pluck('title', 'id')->map(fn ($title) => $title ?? '—');
                    }

                    $supervisorPrograms = UserProgram::where('user_id', $user->id)
                        ->pluck('program_id');

                    return Program::whereIn('id', $supervisorPrograms)
                        ->pluck('title', 'id')
                        ->map(fn ($title) => $title ?? '—');
                })
                ->required()
            ->searchable()
            ->preload(false),

            //logo
                Forms\Components\FileUpload::make('logo')
                ->label('Logo')
                ->directory('branding/programs')
                ->image()
                ->imagePreviewHeight('100')
                ->maxFiles(1)
                ->required()
                ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),

            //favicon
                Forms\Components\FileUpload::make('favicon')
                ->label('Favicon')
                ->directory('branding/programs')
                ->image()
                ->imagePreviewHeight('50')
                ->maxFiles(1)
                ->getUploadedFileNameForStorageUsing(fn ($file) => (string) str($file->hashName())),

            //primary color
                Forms\Components\ColorPicker::make('primary_color')
                    ->label('Primary Color')
                    ->required()
                    ->rules(['regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'])
                    ->validationMessages([
                        'required' => 'Primary color is required.',
                        'regex' => 'Primary color must be a valid hex color code (e.g., #FF0000 or #F00).',
                    ])
                    ->default('#6E62E5'),

            //secondary color
                Forms\Components\ColorPicker::make('secondary_color')
                    ->label('Secondary Color')
                    ->required()
                    ->rules(['regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'])
                    ->validationMessages([
                        'required' => 'Secondary color is required.',
                        'regex' => 'Secondary color must be a valid hex color code (e.g., #FF0000 or #F00).',
                    ])
                    ->default('#4B5563'),

            //font
                Forms\Components\Select::make('font')
                    ->label('Font')
                    ->options(function () {
                        $fonts = GoogleFontsService::getFonts();
                        $fonts = array_merge([
                            Str::snake('Madani Arabic', '_') => 'Madani Arabic',
                            Str::snake('Mestika', '_') => 'Mestika'
                        ], $fonts);
                        return $fonts;
                    })
                    ->default('Inter')
                    ->searchable()
                    ->preload(false),

            //is published
                Forms\Components\Toggle::make('is_published')
                    ->label('Is Published')
                    ->default(false),
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

                return $query->whereIn('program_id', $supervisorPrograms);
            })
            ->columns([
                Tables\Columns\TextColumn::make('program.title')
                    ->label('Program')
                    ->sortable(),
                ImageColumn::make('logo')
                    ->label('Logo'),
                // ImageColumn::make('white_logo')
                //     ->label('White Logo'),
                Tables\Columns\ImageColumn::make('favicon')
                    ->label('Favicon')
                    ->height(20)
                    ->width(20),
                Tables\Columns\TextColumn::make('font')
                    ->label('Font')
                    ->formatStateUsing(fn ($state) => $state ? $state : '—'),
                Tables\Columns\ColorColumn::make('primary_color')
                    ->label('Primary Color')
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->copyMessageDuration(1500),
                Tables\Columns\ColorColumn::make('secondary_color')
                    ->label('Secondary Color')
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->copyMessageDuration(1500),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Is Published')
                    ->boolean()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->visible(fn () => auth()->user()?->can('create BrandingProgram'))
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete BrandingProgram'))
                ]),
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
            'index' => Pages\ListBrandingPrograms::route('/'),
            'create' => Pages\CreateBrandingProgram::route('/create'),
            'edit' => Pages\EditBrandingProgram::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view BrandingProgram') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create BrandingProgram') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('update BrandingProgram');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete BrandingProgram');
    }

}
