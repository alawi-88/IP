<?php

namespace App\Http\Controllers\Api\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStartupRequest;
use App\Http\Requests\UpdateStartupRequest;
use App\Models\Startup;
use App\Services\VaInitializationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StartupController extends Controller
{
    private VaInitializationService $vaInitializationService;

    public function __construct(VaInitializationService $vaInitializationService)
    {
        $this->vaInitializationService = $vaInitializationService;
    }

    /**
     * List user's startups
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $startups = Startup::forUser($user->id)
            ->active()
            ->withCount('vaSections')
            ->get()
            ->map(function ($startup) {
                return [
                    'id' => $startup->id,
                    'name' => $startup->name,
                    'tagline' => $startup->tagline,
                    'description' => $startup->description,
                    'logo_path' => $startup->logo_path,
                    'status' => $startup->status,
                    'sector' => $startup->sector,
                    'stage' => $startup->stage,
                    'founding_date' => $startup->founding_date,
                    'team_size' => $startup->team_size,
                    'completion_percentage' => $startup->completion_percentage,
                    'sections_count' => $startup->va_sections_count,
                    'created_at' => $startup->created_at,
                    'updated_at' => $startup->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $startups,
        ]);
    }

    /**
     * Create a new startup
     */
    public function store(StoreStartupRequest $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has exceeded 10 startup limit
        $startupCount = Startup::forUser($user->id)->count();
        if ($startupCount >= 10) {
            return response()->json([
                'success' => false,
                'message' => __('startup.max_startups_reached'),
            ], 422);
        }

        // Handle logo upload
        $logoPath = null;
        if ($request->hasFile('logo_path')) {
            $logoPath = $request->file('logo_path')->store('startup_logos', 'public');
        }

        $startup = Startup::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'tagline' => $request->input('tagline'),
            'description' => $request->input('description'),
            'logo_path' => $logoPath,
            'sector' => $request->input('sector'),
            'stage' => $request->input('stage'),
            'founding_date' => $request->input('founding_date'),
            'team_size' => $request->input('team_size'),
            'status' => 'draft',
        ]);

        // Initialize VA sections and pages
        $this->vaInitializationService->initializeForStartup($startup);

        return response()->json([
            'success' => true,
            'message' => __('startup.created_successfully'),
            'data' => [
                'id' => $startup->id,
                'name' => $startup->name,
                'status' => $startup->status,
                'completion_percentage' => $startup->completion_percentage,
            ],
        ], 201);
    }

    /**
     * Get startup with sections and completion
     */
    public function show(Request $request, Startup $startup): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $startup->load('vaSections.vaPages');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $startup->id,
                'name' => $startup->name,
                'tagline' => $startup->tagline,
                'description' => $startup->description,
                'logo_path' => $startup->logo_path,
                'status' => $startup->status,
                'sector' => $startup->sector,
                'stage' => $startup->stage,
                'founding_date' => $startup->founding_date,
                'team_size' => $startup->team_size,
                'completion_percentage' => $startup->completion_percentage,
                'sections' => $startup->vaSections->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'section_key' => $section->section_key,
                        'title_en' => $section->title_en,
                        'title_ar' => $section->title_ar,
                        'completion_percentage' => $section->completion_percentage,
                        'pages_count' => $section->vaPages->count(),
                        'last_edited_at' => $section->last_edited_at,
                    ];
                }),
                'created_at' => $startup->created_at,
                'updated_at' => $startup->updated_at,
            ],
        ]);
    }

    /**
     * Edit startup info
     */
    public function update(UpdateStartupRequest $request, Startup $startup): JsonResponse
    {
        // Handle logo upload
        if ($request->hasFile('logo_path')) {
            $startup->logo_path = $request->file('logo_path')->store('startup_logos', 'public');
        }

        $startup->update([
            'name' => $request->input('name', $startup->name),
            'tagline' => $request->input('tagline', $startup->tagline),
            'description' => $request->input('description', $startup->description),
            'sector' => $request->input('sector', $startup->sector),
            'stage' => $request->input('stage', $startup->stage),
            'founding_date' => $request->input('founding_date', $startup->founding_date),
            'team_size' => $request->input('team_size', $startup->team_size),
            'status' => $request->input('status', $startup->status),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('startup.updated_successfully'),
            'data' => [
                'id' => $startup->id,
                'name' => $startup->name,
                'status' => $startup->status,
            ],
        ]);
    }

    /**
     * Soft delete a startup
     */
    public function destroy(Request $request, Startup $startup): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $startup->delete();

        return response()->json([
            'success' => true,
            'message' => __('startup.deleted_successfully'),
        ]);
    }

    /**
     * Restore a startup (within 30 days)
     */
    public function restore(Request $request, Startup $startup): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if can restore (within 30 days)
        if (!$startup->canRestore()) {
            return response()->json([
                'success' => false,
                'message' => __('startup.cannot_restore'),
            ], 422);
        }

        $startup->restore();

        return response()->json([
            'success' => true,
            'message' => __('startup.restored_successfully'),
            'data' => [
                'id' => $startup->id,
                'name' => $startup->name,
            ],
        ]);
    }

    /**
     * Trigger startup export
     */
    public function export(Request $request, Startup $startup): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'format' => ['required', 'in:pdf,docx,xlsx'],
        ]);

        $export = $startup->exports()->create([
            'user_id' => $request->user()->id,
            'format' => $validated['format'],
            'status' => 'pending',
        ]);

        // TODO: Dispatch export job
        // ExportStartupJob::dispatch($export);

        return response()->json([
            'success' => true,
            'message' => __('startup.export_started'),
            'data' => [
                'export_id' => $export->id,
                'status' => $export->status,
                'format' => $export->format,
            ],
        ], 201);
    }
}
