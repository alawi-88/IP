<?php

namespace App\Filament\Resources\ProjectEvaluationResource\Pages;

use App\Filament\Resources\ProjectEvaluationResource;
use App\Models\FormEvaluationScore;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProjectEvaluations extends ListRecords
{
    protected static string $resource = ProjectEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = FormEvaluationScore::query()
            ->whereHas('judgeProject', fn($query) => $query->whereHas('project', fn($query) => $query->byCompetition()));

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn (Builder $query) => clone $baseQuery),
            
            'active' => Tab::make(__('evaluation_archive.active_evaluations'))
                ->badge((clone $baseQuery)->where('form_evaluation_scores.is_archived', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => (clone $baseQuery)->where('form_evaluation_scores.is_archived', false)),
        ];

        // Add archived tab if user has archive/restore permissions
        if (auth()->user()?->can('archive ProjectEvaluation') || auth()->user()?->can('restore ProjectEvaluation')) {
            $tabs['archived'] = Tab::make(__('evaluation_archive.archived_evaluations'))
                ->badge((clone $baseQuery)->where('form_evaluation_scores.is_archived', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => (clone $baseQuery)->where('form_evaluation_scores.is_archived', true));
        }

        return $tabs;
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery();
    }
}
