<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\ApprovalRequestStatusChanged;
use App\Listeners\SendApprovalRequestNotification;
use App\Events\ApproverAssignedToRequest;
use App\Listeners\SendApproverAssignmentNotification;
use App\Events\ProgramApprovalRequestCreated;
use App\Listeners\SendProgramApprovalNotification;
use App\Events\ProgramCreated;
use App\Listeners\CreateProgramTabs;
use App\Listeners\CreateProgramStages;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ApprovalRequestStatusChanged::class => [
            SendApprovalRequestNotification::class,
        ],
        ApproverAssignedToRequest::class => [
            SendApproverAssignmentNotification::class,
        ],
        ProgramApprovalRequestCreated::class => [
            SendProgramApprovalNotification::class,
        ],
        ProgramCreated::class => [
            CreateProgramTabs::class,
            CreateProgramStages::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
