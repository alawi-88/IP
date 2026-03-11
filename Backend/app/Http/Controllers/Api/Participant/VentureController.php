<?php

namespace App\Http\Controllers\Api\Participant;

use App\Http\Controllers\Controller;
use App\Models\Venture;
use App\Models\VentureSection;
use App\Services\Ai\VentureGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VentureController extends Controller
{
    protected VentureGenerationService $generationService;

    public function __construct(VentureGenerationService $generationService)
    {
        $this->generationService = $generationService;
    }

    /**
     * Display a paginated listing of ventures for the authenticated participant.
     */
    public function index(Request $request): JsonResponse
    {
        $query = auth()->user()->ventures();

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $ventures = $query
            ->withCount('tabs')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $ventures->items(),
            'pagination' => [
                'total' => $ventures->total(),
                'per_page' => $ventures->perPage(),
                'current_page' => $ventures->currentPage(),
                'last_page' => $ventures->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created venture.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'idea_prompt' => 'required|string|min:20',
            'industry' => 'nullable|string',
            'target_market' => 'nullable|string',
            'business_model' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['competition_id'] = $this->currentCompetitionId();

        $venture = Venture::create($validated);

        // Dispatch generation
        $this->generationService->generate($venture);

        return response()->json([
            'data' => $venture,
        ], 201);
    }

    /**
     * Display the specified venture with tabs and sections.
     */
    public function show(Venture $venture): JsonResponse
    {
        // Verify the venture belongs to the authenticated participant
        if ($venture->created_by !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $venture->load([
            'tabs' => function ($query) {
                $query->orderBy('sort_order');
            },
            'tabs.sections' => function ($query) {
                $query->orderBy('sort_order');
            },
        ]);

        return response()->json([
            'data' => $venture,
        ]);
    }

    /**
     * Get generation progress for a venture.
     */
    public function progress(Venture $venture): JsonResponse
    {
        // Verify the venture belongs to the authenticated participant
        if ($venture->created_by !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $sections = $venture->tabs()
            ->with('sections')
            ->get()
            ->flatMap(fn($tab) => $tab->sections);

        $total = $sections->count();
        $completed = $sections->filter(fn($s) => $s->status === 'completed')->count();
        $failed = $sections->filter(fn($s) => $s->status === 'failed')->count();
        $pending = $sections->filter(fn($s) => $s->status === 'pending')->count();

        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        $sectionStatus = $sections->map(fn($section) => [
            'id' => $section->id,
            'key' => $section->slug,
            'status' => $section->status,
            'display_name' => $section->displayConfig?->label_en ?? ucwords(str_replace('_', ' ', $section->slug)),
        ]);

        return response()->json([
            'data' => [
                'status' => $venture->status,
                'total_sections' => $total,
                'completed' => $completed,
                'failed' => $failed,
                'pending' => $pending,
                'percentage' => $percentage,
                'sections' => $sectionStatus,
            ],
        ]);
    }

    /**
     * Retry all failed sections for a venture.
     */
    public function retryFailed(Venture $venture): JsonResponse
    {
        if ($venture->created_by !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->generationService->retryFailed($venture);

        $venture->update(['status' => 'generating']);

        return response()->json([
            'data' => $venture,
        ]);
    }

    /**
     * Regenerate a specific section.
     */
    public function regenerateSection(Venture $venture, VentureSection $section): JsonResponse
    {
        if ($venture->created_by !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($section->tab->venture_id !== $venture->id) {
            return response()->json(['error' => 'Section does not belong to this venture'], 422);
        }

        $this->generationService->regenerateSection($section);

        return response()->json([
            'data' => $section,
        ]);
    }

    /**
     * Update a venture section's content.
     */
    public function updateSection(Venture $venture, VentureSection $section, Request $request): JsonResponse
    {
        if ($venture->created_by !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($section->tab->venture_id !== $venture->id) {
            return response()->json(['error' => 'Section does not belong to this venture'], 422);
        }

        $validated = $request->validate([
            'content' => 'required|json',
        ]);

        $section->update([
            'content' => $validated['content'],
        ]);

        // Create version record
        $section->versions()->create([
            'content' => $validated['content'],
        ]);

        return response()->json([
            'data' => $section,
        ]);
    }

    /**
     * Toggle archive status of a venture.
     */
    public function toggleArchive(Venture $venture): JsonResponse
    {
        if ($venture->created_by !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $venture->update([
            'is_archived' => !$venture->is_archived,
        ]);

        return response()->json([
            'data' => $venture,
        ]);
    }

    /**
     * Get the current competition ID.
     * This should be implemented based on your competition/season logic.
     */
    protected function currentCompetitionId(): ?int
    {
        // Implement based on your business logic
        // Example: return Competition::where('status', 'active')->latest()->first()?->id;
        return null;
    }
}
