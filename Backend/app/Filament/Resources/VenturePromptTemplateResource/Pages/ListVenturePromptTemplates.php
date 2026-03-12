<?php

namespace App\Filament\Resources\VenturePromptTemplateResource\Pages;

use App\Filament\Resources\VenturePromptTemplateResource;
use App\Models\VenturePromptTemplate;
use App\Models\VentureSectionConfig;
use App\Services\Ai\VenturePromptBuilder;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ListVenturePromptTemplates extends ListRecords
{
    protected static string $resource = VenturePromptTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('seedDefaults')
                ->label('Seed Default Prompts')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Seed Default Prompts')
                ->modalDescription('This will create prompt templates for all sections that don\'t already have one. Existing templates will NOT be overwritten.')
                ->action(function () {
                    $builder = new VenturePromptBuilder();
                    $configs = VentureSectionConfig::all();
                    $created = 0;

                    foreach ($configs as $config) {
                        // Skip if template already exists
                        if (VenturePromptTemplate::where('section_slug', $config->section_slug)->exists()) {
                            continue;
                        }

                        $defaultPrompt = $builder->getDefaultPrompt($config->section_slug);

                        VenturePromptTemplate::create([
                            'section_slug' => $config->section_slug,
                            'label' => $config->label_en,
                            'system_prompt' => 'You are an expert startup advisor and business analyst. Respond with valid JSON only. No markdown, no explanation, no code fences.',
                            'user_prompt' => $defaultPrompt,
                            'is_active' => true,
                            'max_tokens' => 4096,
                            'temperature' => 0.70,
                        ]);
                        $created++;
                    }

                    Notification::make()
                        ->success()
                        ->title("Created {$created} prompt template(s)")
                        ->body($created === 0 ? 'All sections already have templates.' : "{$created} new templates seeded from defaults.")
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(VenturePromptTemplate::query())
            ->columns([
                Tables\Columns\TextColumn::make('section_slug')
                    ->label('Section')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tab')
                    ->label('Tab')
                    ->state(function ($record) {
                        $config = VentureSectionConfig::where('section_slug', $record->section_slug)->first();
                        return $config ? ucwords(str_replace('_', ' ', $config->tab_slug)) : '—';
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('max_tokens')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('temperature')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user_prompt')
                    ->label('Prompt Preview')
                    ->limit(80)
                    ->tooltip(fn ($record) => \Illuminate\Support\Str::limit($record->user_prompt, 300))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
