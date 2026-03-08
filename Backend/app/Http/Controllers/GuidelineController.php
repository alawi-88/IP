<?php

namespace App\Http\Controllers;

use App\Http\Resources\GuidelineResource;
use App\Models\Guideline;
use App\Models\ProgramApplication;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GuidelineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): ResourceCollection
    {
        // Get application_id from request
        $applicationId = request('application_id');
        
        if (!$applicationId) {
            return GuidelineResource::collection(collect());
        }
        
        // Get program_id from the application
        $application = ProgramApplication::findOrFail($applicationId);
        
        // IDOR Prevention: Verify that the application belongs to the authenticated user
        $userId = auth()->id();
        if ($userId && $application->participant_id !== $userId) {
            abort(404, 'Application not found');
        }
        
        $programId = $application->program_id;
        
        // Filter guidelines by program_id
        $guidelines = Guideline::where('is_visible', 1)
            ->where('program_id', $programId)
            ->active(); // Only show non-archived guidelines
        
        return GuidelineResource::collection(
            $guidelines->orderBy('id', 'desc')
                ->cursorPaginate(config('resource.pagination_limit'))
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Guideline $guideline): GuidelineResource
    {
        // Prevent participants from viewing archived guidelines
        if ($guideline->isArchived()) {
            abort(404, 'Guideline not found');
        }
        
        return new GuidelineResource($guideline);
    }
}
