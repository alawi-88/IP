<?php

namespace App\Http\Controllers;

use App\Filters\Programs\Status;
use App\Filters\Programs\ProgramType;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use App\Models\ProgramLabel;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProgramController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $baseQuery = Program::published()->active();
            $status = request('status');
            $programType = request('program_type');
            
            // Apply status filter
            if ($status === 'open') {
                $baseQuery->whereHas('stages', function ($query) {
                    $query->where('slug', 'registration')
                          ->where(function ($subQ) {
                              // Registration has started (starts_at <= now)
                              $subQ->whereNull('starts_at')
                                   ->orWhere('starts_at', '<=', now());
                          })
                          ->where('ends_at', '>', now());
                });
            } elseif ($status === 'closed') {
                $baseQuery->where(function ($query) {
                    $query->whereDoesntHave('stages', function ($q) {
                        $q->where('slug', 'registration');
                    })->orWhereHas('stages', function ($q) {
                        $q->where('slug', 'registration')
                          ->where(function ($subQ) {
                              // Registration has ended (ends_at <= now)
                              $subQ->where('ends_at', '<=', now())
                                   // OR registration hasn't started yet (starts_at > now)
                                   ->orWhere('starts_at', '>', now());
                          });
                    });
                });
            }
            
            // Apply program type filter
            if ($programType) {
                $baseQuery->programType($programType);
            }
            
            $programs = $baseQuery->get();

            return response()->json([
                'programs_types' => $this->getProgramsCountByType(),
                'data' => ProgramResource::collection($programs),
                'filters_applied' => [
                    'program_type' => $programType,
                    'status' => $status,
                ],
            ]); 
        } catch (\Exception $e) {
            // Program filtering error
            
            return response()->json([
                'programs_types' => $this->getProgramsCountByType(),
                'data' => ProgramResource::collection(Program::published()->active()->get()),
                'error' => 'Filtering failed, showing all results',
            ], 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Program $program): JsonResource
    {
        $application = null;
        $participant = request()->user();

        if ($participant) {
            $application = $participant->programApplications()
                ->where('program_id', $program->id)
                ->submission()
                ->active()
                ->first();
        }

        return new ProgramResource($program, null, $application?->id);
    }
    /**
     * Get all labels for a program, keyed for easy frontend consumption.
     */
    public function labels(Program $program): JsonResponse
    {
        $labels = $program->labels()
            ->orderBy('category')
            ->orderBy('key')
            ->get();

        // Return as a flat key-value map for easy frontend usage
        // e.g., { "btn_register": { "en": "Register Now", "ar": "سجل الآن" }, ... }
        $mapped = $labels->mapWithKeys(function ($label) {
            return [$label->key => [
                'en' => $label->label_en,
                'ar' => $label->label_ar,
                'category' => $label->category,
            ]];
        });

        return response()->json([
            'data' => $mapped,
        ]);
    }

    public function getProgramsCountByType(): array
{
    $types = ['Hackathon', 'Sandbox', 'Idea Bank'];

    $counts = Program::selectRaw('type, COUNT(*) as total')
        ->whereIn('type', $types)
        ->published()
        ->active()
        ->groupBy('type')
        ->pluck('total', 'type')
        ->toArray();

    $programsCountByType = [];
    foreach ($types as $type) {
        $snakeKey = Str::snake($type, '_');
        $count = $counts[$type] ?? 0;

        $programsCountByType[] = [
            'title' => __('program_types.' . $snakeKey),
            'slug' => $snakeKey,
            'count' => $count,
        ];
    }

    return $programsCountByType;
}
}
