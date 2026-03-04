<?php

namespace App\Http\Controllers\Api\Participant;

use App\Http\Controllers\Controller;
use App\Models\Startup;
use App\Models\VaSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaSectionController extends Controller
{
    /**
     * List sections for a startup
     */
    public function index(Request $request, Startup $startup): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $sections = $startup->vaSections()
            ->with('vaPages')
            ->get()
            ->map(function ($section) {
                return [
                    'id' => $section->id,
                    'section_key' => $section->section_key,
                    'title_en' => $section->title_en,
                    'title_ar' => $section->title_ar,
                    'completion_percentage' => $section->completion_percentage,
                    'pages' => $section->vaPages->map(function ($page) {
                        return [
                            'id' => $page->id,
                            'page_key' => $page->page_key,
                            'title_en' => $page->title_en,
                            'title_ar' => $page->title_ar,
                            'status' => $page->status,
                            'completion_percentage' => $page->completion_percentage,
                            'order' => $page->order,
                        ];
                    }),
                    'last_edited_at' => $section->last_edited_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sections,
        ]);
    }

    /**
     * Get section with pages
     */
    public function show(Request $request, Startup $startup, VaSection $section): JsonResponse
    {
        // Authorization check
        if ($startup->user_id !== $request->user()->id || $section->startup_id !== $startup->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $section->load('vaPages');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $section->id,
                'section_key' => $section->section_key,
                'title_en' => $section->title_en,
                'title_ar' => $section->title_ar,
                'completion_percentage' => $section->completion_percentage,
                'pages' => $section->vaPages->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'page_key' => $page->page_key,
                        'title_en' => $page->title_en,
                        'title_ar' => $page->title_ar,
                        'status' => $page->status,
                        'completion_percentage' => $page->completion_percentage,
                        'order' => $page->order,
                        'content' => $page->content,
                        'last_edited_at' => $page->last_edited_at,
                    ];
                }),
                'last_edited_at' => $section->last_edited_at,
            ],
        ]);
    }
}
