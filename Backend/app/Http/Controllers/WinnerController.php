<?php

namespace App\Http\Controllers;

use App\Http\Resources\WinnerResource;
use App\Models\Winner;
use App\Models\CompetitionApplication;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class WinnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
      $application = CompetitionApplication::findOrFail($request->application_id);
      
      // IDOR Prevention: Verify that the application belongs to the authenticated user
      $userId = auth()->id();
      if ($userId && $application->participant_id !== $userId) {
          abort(404, 'Application not found');
      }

        $winners = Winner::with('track')
            ->whereHas('competition', function ($query) use ($application) {
                $query->where('id', $application->competition_id);
            })
            ->visible()
            ->orderBy('rank')
            ->get();

        $lang = app()->getLocale();
        $data = $winners->map(function ($winner) use ($lang) {          
            return [
            'id' => $winner->id,
            'competition_id' => $winner->competition_id,
            'track_id' => $winner->track_id,
            'rank' => $winner->rank,
            'name' => $winner->name[$lang] ?? null,
            'subtitle' => $winner->subtitle[$lang] ?? null,
            'image' => $winner->image ? Storage::url($winner->image) : null,
            'is_visible' => $winner->is_visible,
            'created_at' => $winner->created_at,
            'updated_at' => $winner->updated_at,
            ];
        });

        //return response()->json($data);
        return response()->json([
            'data' => $data
        ]);
    }
}
