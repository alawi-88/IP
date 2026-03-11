<?php

namespace App\Filament\Resources\VentureResource\Pages;

use App\Filament\Resources\VentureResource;
use App\Filament\Resources\VentureResource\RelationManagers\TabsRelationManager;
use App\Models\Venture;
use Filament\Infolists\Components\BadgeColumn;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewVenture extends ViewRecord
{
    protected static string $resource = VentureResource::class;

    protected static ?string $navigationLabel = 'View';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Venture Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('participant.name')
                            ->label('Participant'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'generating' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('viability_score')
                            ->suffix('/100'),
                        TextEntry::make('industry'),
                        TextEntry::make('target_market'),
                        TextEntry::make('business_model')
                            ->columnSpanFull(),
                        TextEntry::make('idea_prompt')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
                Section::make('Generation Stats')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_sections')
                            ->label('Total Sections')
                            ->state(fn (Venture $record): int => $record->tabs()->withCount('sections')->get()->sum('sections_count'))
                            ->color('info'),
                        TextEntry::make('completed_sections')
                            ->label('Completed Sections')
                            ->state(fn (Venture $record): int => $record->tabs()
                                ->with(['sections' => fn ($query) => $query->where('status', 'completed')])
                                ->get()
                                ->sum(fn ($tab) => $tab->sections->count()))
                            ->color('success'),
                        TextEntry::make('failed_sections')
                            ->label('Failed Sections')
                            ->state(fn (Venture $record): int => $record->tabs()
                                ->with(['sections' => fn ($query) => $query->where('status', 'failed')])
                                ->get()
                                ->sum(fn ($tab) => $tab->sections->count()))
                            ->color('danger'),
                    ]),
            ]);
    }

    public function getRelationManagers(): array
    {
        return [
            TabsRelationManager::class,
        ];
    }
}
