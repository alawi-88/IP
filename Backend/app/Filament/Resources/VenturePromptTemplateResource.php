<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenturePromptTemplateResource\Pages;
use App\Models\VenturePromptTemplate;
use App\Services\Ai\VenturePromptBuilder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class VenturePromptTemplateResource extends Resource
{
    protected static ?string $model = VenturePromptTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationGroup = 'Startup Builder';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'AI Prompts';

    protected static ?string $modelLabel = 'Prompt Template';

    protected static ?string $pluralModelLabel = 'Prompt Templates';

    public static function form(Form $form): Form
    {
        $sectionKeys = VenturePromptTemplate::allSectionKeys();

        return $form
            ->schema([
                Forms\Components\Section::make('Prompt Template Configuration')
                    ->description('Override the default AI prompt for a specific section or the global system prompt. Leave fields empty to use the hardcoded defaults.')
                    ->schema([
                        Forms\Components\Select::make('section_key')
                            ->label('Section')
                            ->options($sectionKeys)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->searchable()
                            ->helperText('Select "__system__" to override the global system prompt, or a specific section to override its task prompt.'),

                        Forms\Components\Textarea::make('system_prompt')
                            ->label('System Prompt Override')
                            ->rows(10)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('section_key') === '__system__')
                            ->helperText('This replaces the global system prompt sent to the AI. Leave empty to use the default.')
                            ->placeholder(fn () => 'Default: ' . substr((new VenturePromptBuilder())->getSystemPrompt(), 0, 200) . '...'),

                        Forms\Components\Textarea::make('section_prompt')
                            ->label('Section Prompt Override')
                            ->rows(10)
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get) => $get('section_key') !== null && $get('section_key') !== '__system__')
                            ->helperText('This replaces the task instructions for this section. Leave empty to use the default.')
                            ->placeholder(fn (Forms\Get $get) => self::getDefaultPromptPreview($get('section_key'))),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('When inactive, the default hardcoded prompt will be used instead.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $sectionKeys = VenturePromptTemplate::allSectionKeys();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('section_key')
                    ->label('Section')
                    ->formatStateUsing(fn ($state) => $sectionKeys[$state] ?? $state)
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state === '__system__' ? 'danger' : 'primary'),

                Tables\Columns\TextColumn::make('prompt_preview')
                    ->label('Prompt Preview')
                    ->getStateUsing(function ($record) {
                        $text = $record->section_key === '__system__'
                            ? ($record->system_prompt ?? '(using default)')
                            : ($record->section_prompt ?? '(using default)');
                        return \Illuminate\Support\Str::limit($text, 80);
                    })
                    ->wrap(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('section_key')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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
            ->headerActions([
                Tables\Actions\Action::make('seed_defaults')
                    ->label('Seed All Defaults')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Seed Default Prompts')
                    ->modalDescription('This will create a template entry for each section with the current default prompts. Existing overrides will NOT be affected.')
                    ->action(function () {
                        $promptBuilder = new VenturePromptBuilder();
                        $sectionKeys = VenturePromptTemplate::allSectionKeys();
                        $created = 0;

                        foreach ($sectionKeys as $key => $label) {
                            if (VenturePromptTemplate::where('section_key', $key)->exists()) {
                                continue;
                            }

                            $data = [
                                'section_key' => $key,
                                'is_active' => true,
                            ];

                            if ($key === '__system__') {
                                $data['system_prompt'] = $promptBuilder->getSystemPrompt();
                            } else {
                                $data['section_prompt'] = $promptBuilder->getDefaultSectionPrompt($key);
                            }

                            VenturePromptTemplate::create($data);
                            $created++;
                        }

                        Notification::make()
                            ->title("Seeded {$created} default prompt templates")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Get a preview of the default prompt for a section key.
     */
    private static function getDefaultPromptPreview(?string $sectionKey): string
    {
        if (!$sectionKey || $sectionKey === '__system__') {
            return '';
        }

        try {
            $promptBuilder = new VenturePromptBuilder();
            $default = $promptBuilder->getDefaultSectionPrompt($sectionKey);
            return 'Default: ' . substr($default, 0, 200) . (strlen($default) > 200 ? '...' : '');
        } catch (\Exception $e) {
            return '';
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenturePromptTemplates::route('/'),
            'create' => Pages\CreateVenturePromptTemplate::route('/create'),
            'edit' => Pages\EditVenturePromptTemplate::route('/{record}/edit'),
        ];
    }
}
