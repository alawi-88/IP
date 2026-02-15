<?php

namespace App\Filament\Resources\ActivitylogResource\Pages;

use App\Filament\Resources\ActivitylogResource;
use App\Models\Judge;
use App\Models\Participant;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\{Grid, Section, TextEntry};
use Illuminate\Support\HtmlString;

class ViewActivitylog extends ViewRecord
{
    public static function getResource(): string
    {
        return ActivitylogResource::class;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Grid::make([
                'default' => 2,
                'sm' => 2,
            ])
                ->schema([
                    Section::make()
                        ->schema([
                            TextEntry::make('causer.name')->label('User'),

                            TextEntry::make('subject')
                                ->label('Subject')
                                ->state(
                                    fn($record) =>
                                    class_basename($record->subject_type) . ' # ' . $record->subject_id
                                ),

                            TextEntry::make('description')
                                ->label('Description')
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->columnSpan(1),

                    Section::make()
                        ->schema([
                            TextEntry::make('log_name')->label('Type'),

                            TextEntry::make('event')->label('Event'),

                            TextEntry::make('created_at')
                                ->label('Logged at')
                                ->dateTime('d/m/Y H:i:s'),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                ]),

            Section::make('Changes')
                ->schema([
                    Grid::make([
                        'default' => 2,
                        'sm' => 2,
                    ])->schema([
                        Section::make('New Values')
                            ->schema([
                                KeyValueEntry::make('changes.attributes')
                                    ->label('New')
                                    ->keyLabel('Key')
                                    ->valueLabel('Value')
                                    ->state(fn($record) => $this->replaceUserIds($record, 'attributes')),
                            ])
                            ->columnSpan(1),

                        Section::make('Old Values')
                            ->schema([
                                KeyValueEntry::make('changes.old')
                                    ->label('Old')
                                    ->state(fn($record) => $this->replaceUserIds($record, 'old')),
                            ])
                            ->columnSpan(1),
                    ]),
                ])
        ]);
    }

    private function replaceUserIds(object $record, string $key): array
    {
        $state = $record->changes[$key] ?? [];

        if (isset($state['user_ids'])) {
            $ids  = is_array($state['user_ids'])
                ? $state['user_ids']
                : array_filter(explode(',', $state['user_ids']));

            $type  = data_get($record, 'properties.attributes.user_type');
            $names = match ($type) {
                'judge'       => Judge::whereIn('id', $ids)->pluck('name')->toArray(),
                'participant' => Participant::whereIn('id', $ids)->pluck('name')->toArray(),
                default       => $ids,
            };

            $state['user_ids'] = implode(', ', $names);
        }

        foreach ($state as $k => $v) {
            $state[$k] = $this->prettyPrint($v);
        }

        return $state;
    }


    private function prettyPrint(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return new HtmlString(
                '<pre class="whitespace-pre-wrap text-xs leading-relaxed">' .
                    json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) .
                    '</pre>'
            );
        }

        return $value;
    }
}
