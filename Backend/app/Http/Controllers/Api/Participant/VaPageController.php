<?php

namespace App\Http\Controllers\Api\Participant;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVaPageRequest;
use App\Models\Startup;
use App\Models\VaSection;
use App\Models\VaPage;
use App\Services\CompletionCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaPageController extends Controller
{
    private CompletionCalculatorService $completionCalculator;

    public function __construct(CompletionCalculatorService $completionCalculator)
    {
        $this->completionCalculator = $completionCalculator;
    }

    /**
     * Get page with content
     */
    public function show(Request $request, Startup $startup, VaSection $section, VaPage $page): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id || $page->va_section_id !== $section->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $page->load('aiGenerations');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'page_key' => $page->page_key,
                'title_en' => $page->title_en,
                'title_ar' => $page->title_ar,
                'content' => $page->content ?? [],
                'status' => $page->status,
                'completion_percentage' => $page->completion_percentage,
                'order' => $page->order,
                'last_edited_at' => $page->last_edited_at,
                'auto_saved_at' => $page->auto_saved_at,
                'ai_generations' => $page->aiGenerations->map(function ($gen) {
                    return [
                        'id' => $gen->id,
                        'field_key' => $gen->field_key,
                        'response' => $gen->response,
                        'status' => $gen->status,
                        'created_at' => $gen->created_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Save page content (auto-save endpoint)
     */
    public function update(UpdateVaPageRequest $request, Startup $startup, VaSection $section, VaPage $page): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id || $page->va_section_id !== $section->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $completion = $request->input('completion_percentage');
        if ($completion === null) {
            $completion = $this->completionCalculator->calculatePageCompletion($page);
        }

        $page->updateContent($request->input('content'), $completion);

        return response()->json([
            'success' => true,
            'message' => __('startup.page_saved_successfully'),
            'data' => [
                'id' => $page->id,
                'completion_percentage' => $page->completion_percentage,
                'status' => $page->status,
                'last_edited_at' => $page->last_edited_at,
            ],
        ]);
    }

    /**
     * Mark page as completed
     */
    public function complete(Request $request, Startup $startup, VaSection $section, VaPage $page): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id || $page->va_section_id !== $section->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $page->markAsCompleted();

        return response()->json([
            'success' => true,
            'message' => __('startup.page_completed_successfully'),
            'data' => [
                'id' => $page->id,
                'status' => $page->status,
                'completion_percentage' => $page->completion_percentage,
            ],
        ]);
    }
}
