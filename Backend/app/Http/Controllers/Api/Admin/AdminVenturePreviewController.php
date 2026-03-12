<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVenturePreviewController extends Controller
{
    /**
     * Return venture data for admin preview.
     * Accessed via a signed URL — no JWT required.
     */
    public function show(Request $request, int $ventureId): JsonResponse
    {
        if (!$request->hasValidSignature()) {
            return response()->json(['error' => 'Invalid or expired preview link'], 403);
        }

        $venture = Venture::with([
            'tabs' => function ($query) {
                $query->orderBy('sort_order');
            },
            'tabs.sections' => function ($query) {
                $query->orderBy('sort_order');
            },
        ])->findOrFail($ventureId);

        return response()->json([
            'data' => $venture,
        ]);
    }
}
