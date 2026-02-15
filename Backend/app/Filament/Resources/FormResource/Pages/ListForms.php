<?php

namespace App\Filament\Resources\FormResource\Pages;

use App\Filament\Resources\FormResource;
use App\Models\Form;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        // IDOR prevention: scope forms to current competition only
        $baseQuery = Form::byCompetition();

        $tabs = [
            'all' => Tab::make('All')
                ->badge((clone $baseQuery)->count())
                ->modifyQueryUsing(fn (Builder $query) => clone $baseQuery),

            'active' => Tab::make(__('form_archive.active_forms'))
                ->badge((clone $baseQuery)->where('is_archived', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_archived', false)),
        ];

        // Add archived tab if user has archive/restore permissions
        if (auth()->user()?->can('archive Form') || auth()->user()?->can('restore Form')) {
            $tabs['archived'] = Tab::make(__('form_archive.archived_forms'))
                ->badge((clone $baseQuery)->where('is_archived', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_archived', true));
        }

        return $tabs;
    }

    protected function getTableQuery(): Builder
    {
        // IDOR prevention: scope forms to current competition only
        return parent::getTableQuery()->byCompetition();
    }
}
