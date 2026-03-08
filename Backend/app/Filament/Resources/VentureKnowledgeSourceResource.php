<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentureKnowledgeSourceResource\Pages;
use App\Models\VentureKnowledgeSource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VentureKnowledgeSourceResource extends Resource
{
    protected static ?string $model = VentureKnowledgeSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'AI Knowledge Base';

    protected static ?string $modelLabel = 'Knowledge Source';

    protected static ?string $pluralModelLabel = 'Knowledge Sources';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Knowledge Source')
                    ->description('Add knowledge that the AI will use as context when generating venture content. This is private and not visible to participants.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Saudi Market Insights, Industry Benchmarks, Competition Rules')
                            ->helperText('A descriptive title for this knowledge source.'),

                        Forms\Components\Select::make('type')
                            ->options([
                                'text' => 'Text Content',
                                'document' => 'Document (future)',
                                'url' => 'URL Reference (future)',
                            ])
                            ->default('text')
                            ->required(),

                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->rows(15)
                            ->columnSpanFull()
                            ->placeholder("Enter the knowledge content here. This text will be injected into the AI's context when generating venture sections.\n\nExamples:\n- Market data and statistics\n- Industry-specific guidelines\n- Regional business regulations\n- Competition criteria and rules\n- Company-specific context")
                            ->helperText('The AI will use this knowledge to inform and improve its analysis. Keep content focused and relevant.'),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Only active sources are injected into AI prompts.'),

                                Forms\Components\TextInput::make('priority')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->helperText('Higher priority = injected first (0-100).'),

                                Forms\Components\TextInput::make('max_tokens')
                                    ->label('Max Characters')
                                    ->numeric()
                                    ->default(500)
                                    ->minValue(100)
                                    ->maxValue(5000)
                                    ->helperText('Maximum characters to inject from this source per prompt.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'text',
                        'info' => 'document',
                        'warning' => 'url',
                    ]),

                Tables\Columns\TextColumn::make('content')
                    ->label('Preview')
                    ->limit(60)
                    ->wrap(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('max_tokens')
                    ->label('Max Chars')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'text' => 'Text',
                        'document' => 'Document',
                        'url' => 'URL',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('priority');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentureKnowledgeSources::route('/'),
            'create' => Pages\CreateVentureKnowledgeSource::route('/create'),
            'edit' => Pages\EditVentureKnowledgeSource::route('/{record}/edit'),
        ];
    }
}
