<?php

namespace App\Http\Controllers\Api\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiGenerateRequest;
use App\Models\Startup;
use App\Models\VaSection;
use App\Models\VaPage;
use App\Models\AiGeneration;
use App\Services\AiGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiGenerationController extends Controller
{
    private AiGenerationService $aiService;

    public function __construct(AiGenerationService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Trigger AI generation for a field
     */
    public function generate(AiGenerateRequest $request, Startup $startup, VaSection $section, VaPage $page): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id || $page->va_section_id !== $section->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $generation = $this->aiService->generateForField(
            $page,
            $request->input('field_key'),
            $request->input('prompt')
        );

        return response()->json([
            'success' => true,
            'message' => __('startup.generation_completed'),
            'data' => [
                'id' => $generation->id,
                'field_key' => $generation->field_key,
                'response' => $generation->response,
                'status' => $generation->status,
                'generation_time_ms' => $generation->generation_time_ms,
                'tokens_used' => $generation->tokens_used,
            ],
        ], 201);
    }

    /**
     * Accept AI suggestion
     */
    public function accept(Request $request, Startup $startup, VaSection $section, VaPage $page, AiGeneration $generation): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id || $page->va_section_id !== $section->id || $generation->va_page_id !== $page->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $generation->markAsAccepted();

        return response()->json([
            'success' => true,
            'message' => __('startup.generation_accepted'),
            'data' => [
                'id' => $generation->id,
                'status' => $generation->status,
            ],
        ]);
    }

    /**
     * Accept with modifications
     */
    public function modify(Request $request, Startup $startup, VaSection $section, VaPage $page, AiGeneration $generation): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id || $page->va_section_id !== $section->id || $generation->va_page_id !== $page->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'modified_response' => ['required', 'string', 'max:5000'],
        ]);

        $generation->response = $validated['modified_response'];
        $generation->markAsModified();

        return response()->json([
            'success' => true,
            'message' => __('startup.generation_modified'),
            'data' => [
                'id' => $generation->id,
                'status' => $generation->status,
                'response' => $generation->response,
            ],
        ]);
    }

    /**
     * Dismiss suggestion
     */
    public function dismiss(Request $request, Startup $startup, VaSection $section, VaPage $page, AiGeneration $generation): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id || $page->va_section_id !== $section->id || $generation->va_page_id !== $page->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $generation->markAsDismissed();

        return response()->json([
            'success' => true,
            'message' => __('startup.generation_dismissed'),
            'data' => [
                'id' => $generation->id,
                'status' => $generation->status,
            ],
        ]);
    }

    /**
     * Get generation history for a page
     */
    public function history(Request $request, Startup $startup, VaSection $section, VaPage $page): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id || $page->va_section_id !== $section->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $generations = $page->aiGenerations()
            ->with('user:id,name')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $generations->map(function ($gen) {
                return [
                    'id' => $gen->id,
                    'field_key' => $gen->field_key,
                    'prompt' => $gen->prompt,
                    'response' => $gen->response,
                    'status' => $gen->status,
                    'model_used' => $gen->model_used,
                    'tokens_used' => $gen->tokens_used,
                    'generation_time_ms' => $gen->generation_time_ms,
                    'created_by' => $gen->user->name,
                    'created_at' => $gen->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $generations->currentPage(),
                'total' => $generations->total(),
                'per_page' => $generations->perPage(),
            ],
        ]);
    }
}
