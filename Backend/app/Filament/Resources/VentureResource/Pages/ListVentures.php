<?php

namespace App\Filament\Resources\VentureResource\Pages;

use App\Filament\Resources\VentureResource;
use App\Models\Venture;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class ListVentures extends ListRecords
{
    protected static string $resource = VentureResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(Venture::byCompetition())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('participant.name')
                    ->label('Participant')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'generating',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('viability_score')
                    ->suffix('/100')
                    ->sortable(),
                Tables\Columns\TextColumn::make('industry')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_archived')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'generating' => 'Generating',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\TernaryFilter::make('is_archived'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Venture $record): string => $this->generatePreviewUrl($record))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function generatePreviewUrl(Venture $venture): string
    {
        $signedApiUrl = URL::temporarySignedRoute(
            'admin.venture.preview',
            now()->addHour(),
            ['venture' => $venture->id]
        );

        $parsed = parse_url($signedApiUrl);
        parse_str($parsed['query'] ?? '', $queryParams);

        $frontendBase = config('app.frontend_url', 'http://localhost:3000');

        return "{$frontendBase}/en/preview/venture/{$venture->id}?" . http_build_query([
            'expires' => $queryParams['expires'] ?? '',
            'signature' => $queryParams['signature'] ?? '',
        ]);
    }
}
