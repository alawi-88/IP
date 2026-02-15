<?php

namespace App\Traits\CompetitionApplication;

use App\Models\CompetitionApplication;
use App\Notifications\ApplicationStatusUpdated;
use Illuminate\Database\Eloquent\Builder;

trait ManageStatus
{
    public function approve(): void
    {
        $oldStatus = $this->status;
        $this->update(['status' => 'approved']);
        
        // Send notification to participant
        $this->participant->notify(new ApplicationStatusUpdated($this, $oldStatus, 'approved'));
    }

    public function reject(): void
    {
        $oldStatus = $this->status;
        $this->update(['status' => 'rejected']);
        
        // Send notification to participant
        $this->participant->notify(new ApplicationStatusUpdated($this, $oldStatus, 'rejected'));
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function scopeGroupedByMonth(Builder $query): array
    {
        return $query->get()
            ->groupBy(fn($app) => $app->created_at->format('m'))
            ->map(fn($item) => $item->count())
            ->values()
            ->toArray();
    }
}
