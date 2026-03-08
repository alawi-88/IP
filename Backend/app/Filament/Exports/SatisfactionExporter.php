<?php

namespace App\Filament\Exports;

use App\Models\Satisfaction;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class SatisfactionExporter extends Exporter
{
    protected static ?string $model = Satisfaction::class;

    private static array $questions = [
        'overall_experience',
        'benefit_from_training',
        'support_and_guidance_mentors',
        'support_organizers',
        'location_surrounding_environment',
        'interested_attending_similar_programs',
        'how_did_you_hear_about_filmathon',
        'suggestions_comments',
    ];

    public static function getColumns(): array
    {
        $columns = [];

        foreach (static::$questions as $question) {
            $columns[] = ExportColumn::make(__('satisfaction.' . $question))
                ->getStateUsing(function (Satisfaction $satisfaction) use ($question) {

                    if ($question == 'interested_attending_similar_programs'){
                        return static::getAnswer($satisfaction->participant_id, $question) == 1 ? 'Yes, Interested' : 'No, Not interested';
                    }

                    return static::getAnswer($satisfaction->participant_id, $question);
                });
        }

        return array_merge([
            ExportColumn::make('participant.name'),
        ], $columns);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your satisfaction export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }

    private static function getAnswer($participantId, mixed $question)
    {
        return Satisfaction::where('program_id', currentProgramId())
            ->where('participant_id', $participantId)
            ->where('question', $question)->first()?->answer;
    }
}
