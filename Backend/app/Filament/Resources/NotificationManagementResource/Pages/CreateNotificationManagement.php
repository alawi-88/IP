<?php

namespace App\Filament\Resources\NotificationManagementResource\Pages;

use App\Filament\Resources\NotificationManagementResource;
use App\Mail\AdminNotificationEmail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateNotificationManagement extends CreateRecord
{
    protected static string $resource = NotificationManagementResource::class;

    protected function afterCreate(): void
    {
        $users = collect();

        if (!empty($this->record->user_ids)) {
            $ids = is_array($this->record->user_ids) ? $this->record->user_ids : json_decode($this->record->user_ids, true);
            $model = match ($this->record->user_type) {
                'participant' => \App\Models\Participant::class,
                'judge' => \App\Models\Judge::class,
            };
            $users = $model::whereIn('id', $ids)->get();
        } elseif ($this->record->user_type === 'all') {
            $participants = \App\Models\Participant::whereHas('competitionApplications', function ($query) {
                if ($this->record->competition_id) {
                    $query->where('competition_id', $this->record->competition_id);
                }
            })->get();

            $judges = \App\Models\Judge::whereHas('competitions', function ($query) {
                if ($this->record->competition_id) {
                    $query->where('competition_id', $this->record->competition_id);
                }
            })->get();

            $users = $participants->merge($judges);
        }

        // Update recipient count
        $this->record->update(['recipient_count' => $users->count()]);

        foreach ($users as $user) {
            // Send database notification using Filament
            $user->notify(
                Notification::make()
                    ->title($this->record->title)
                    ->body($this->record->body)
                    ->toDatabase()
            );

            // Send email if enabled
            if ($this->record->send_email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($user->email)->queue(new AdminNotificationEmail(
                    $this->record->title,
                    $this->record->body
                ));
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
