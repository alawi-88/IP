<?php

namespace App\Http\Controllers;

use App\Filters\Events\Badge;
use App\Filters\Events\Location;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\ProgramApplication;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pipeline\Pipeline;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): ResourceCollection
    {
        // Get application_id from request
        $applicationId = request('application_id');
        
        if (!$applicationId) {
            return EventResource::collection(collect());
        }
        
        // Get program_id from the application
        $application = ProgramApplication::findOrFail($applicationId);
        
        // IDOR Prevention: Verify that the application belongs to the authenticated user
        $userId = auth()->id();
        if ($userId && $application->participant_id !== $userId) {
            abort(404, 'Application not found');
        }
        
        $programId = $application->program_id;
        
        // Filter events by program_id
        $eventsQuery = Event::where('is_visible', 1)
            ->where('program_id', $programId)
            ->active(); // Only show non-archived events

        $filteredEvents = app(Pipeline::class)
            ->send($eventsQuery)
            ->through([
                Badge::class,
                Location::class,
            ])
            ->thenReturn();

        return EventResource::collection($filteredEvents
            ->orderBy('date', 'desc')
            ->cursorPaginate(config('resource.pagination_limit'))
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): JsonResource
    {
        // Prevent participants from viewing archived events
        if ($event->isArchived()) {
            abort(404, 'Event not found');
        }
        
        return new EventResource($event);
    }
}
