<?php

namespace App\Filament\Resources\MyRequestsResource\Pages;

use App\Filament\Resources\MyRequestsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMyRequests extends ListRecords
{
    protected static string $resource = MyRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for my requests
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Requests / جميع الطلبات')
                ->icon('heroicon-o-document-text'),

            'pending' => Tab::make('Pending / قيد الانتظار')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'approved' => Tab::make('Approved / معتمد')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),

            'rejected' => Tab::make('Rejected / مرفوض')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),

            'cancelled' => Tab::make('Cancelled / ملغي')
                ->icon('heroicon-o-minus-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),

            'returned' => Tab::make('Returned / إعادة')
                ->icon('heroicon-o-arrow-right')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'returned')),
        ];
    }
}
