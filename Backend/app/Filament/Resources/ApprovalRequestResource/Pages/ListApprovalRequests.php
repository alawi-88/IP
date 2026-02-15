<?php

namespace App\Filament\Resources\ApprovalRequestResource\Pages;

use App\Filament\Resources\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListApprovalRequests extends ListRecords
{
    protected static string $resource = ApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh / تحديث')
                ->icon('heroicon-o-arrow-path')
                ->action('$refresh'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All / الكل')
                ->badge(ApprovalRequest::count()),

            'pending' => Tab::make('Pending / قيد الانتظار')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(ApprovalRequest::where('status', 'pending')->count()),

            'approved' => Tab::make('Approved / موافق')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(ApprovalRequest::where('status', 'approved')->count()),

            'rejected' => Tab::make('Rejected / مرفوض')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(ApprovalRequest::where('status', 'rejected')->count()),

            'cancelled' => Tab::make('Cancelled / ملغي')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(ApprovalRequest::where('status', 'cancelled')->count()),

            'returned' => Tab::make('Returned / إعادة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'returned'))
                ->badge(ApprovalRequest::where('status', 'returned')->count()),
        ];
    }
}
