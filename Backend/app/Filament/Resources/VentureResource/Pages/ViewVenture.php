<?php

namespace App\Filament\Resources\VentureResource\Pages;

use App\Filament\Resources\VentureResource;
use App\Filament\Resources\VentureResource\RelationManagers\TabsRelationManager;
use App\Models\Venture;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;

class ViewVenture extends ViewRecord
{
    protected static string $resource = VentureResource::class;

    protected static ?string $navigationLabel = 'View';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewAsParticipant')
                ->label('Preview as Participant')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn (Venture $record): string => $this->generatePreviewUrl($record))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Generate a signed preview URL pointing to the Next.js frontend.
     */
    protected function generatePreviewUrl(Venture $venture): string
    {
        // Generate a signed API URL that expires in 1 hour
        $signedApiUrl = URL::temporarySignedRoute(
            'admin.venture.preview',
            now()->addHour(),
            ['venture' => $venture->id]
        );

        // Extract signature params from the signed API URL
        $parsed = parse_url($signedApiUrl);
        parse_str($parsed['query'] ?? '', $queryParams);

        // Build frontend preview URL
        $frontendBase = config('app.frontend_url', 'http://localhost:3000');
        $previewUrl = "{$frontendBase}/en/preview/venture/{$venture->id}?" . http_build_query([
            'expires' => $queryParams['expires'] ?? '',
            'signature' => $queryParams['signature'] ?? '',
        ]);

        return $previewUrl;
    }

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
                            ->label('User\'s Idea Prompt')
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
