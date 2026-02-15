<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mentor\AvailabilityRequest;
use App\Http\Resources\MentorAvailabilityResource;
use App\Models\MentorAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AvailabilityController extends Controller
{
    /**
     * Get all availability slots for the authenticated mentor
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $mentor = Auth::user();

        $availabilities = MentorAvailability::where('mentor_id', $mentor->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Grouped response by default; use ?raw=1 to get original list
        if (!$request->boolean('raw')) {
            $grouped = [];

            foreach ($availabilities as $model) {
                $groupKey = ($model->day_of_week ?: 'date') . '|' . ($model->date?->format('Y-m-d') ?? 'null');

                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'id' => $model->id, // representative id for this day/date group (first slot id)
                        'day_of_week' => $model->day_of_week,
                        'date' => $model->date?->format('Y-m-d'),
                        'is_recurring' => (bool) $model->is_recurring,
                        'is_active' => (bool) $model->is_active,
                        'slots' => [],
                    ];
                }

                $grouped[$groupKey]['slots'][] = [
                    'id' => $model->id,
                    'start_time' => $model->start_time->format('H:i'),
                    'end_time' => $model->end_time->format('H:i'),
                    'duration_minutes' => (new \App\Http\Resources\MentorAvailabilityResource($model))->toArray($request)['duration_minutes'],
                    'created_at' => $model->created_at?->toISOString(),
                    'updated_at' => $model->updated_at?->toISOString(),
                ];
            }

            return response()->json([
                'data' => array_values($grouped),
            ]);
        }

        return response()->json([
            'data' => MentorAvailabilityResource::collection($availabilities)->resolve(),
        ]);
    }

    /**
     * Get available slots for a specific date
     */
    public function showForDate($date): AnonymousResourceCollection
    {
        $mentor = Auth::user();

        $slots = MentorAvailability::getAvailableSlotsForDate($mentor->id, $date);

        return MentorAvailabilityResource::collection($slots);
    }

    /**
     * Create a new availability slot
     */
    public function store(Request $request): JsonResponse
    {
        $mentor = Auth::user();
        $data = $request->all();

        // BULK MODE: root is a list of items with day_of_week and slots
        if (is_array($data) && array_is_list($data)) {
            $validator = Validator::make(['items' => $data], [
                'items' => ['required', 'array', 'min:1'],
                'items.*.day_of_week' => ['nullable', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
                'items.*.date' => ['nullable', 'date', 'after_or_equal:today'],
                'items.*.is_recurring' => ['sometimes', 'boolean'],
                'items.*.is_active' => ['sometimes', 'boolean'],
                'items.*.slots' => ['required', 'array', 'min:1'],
                'items.*.slots.*.id' => ['nullable', 'integer'],
                'items.*.slots.*.type' => ['required', 'in:new,updated'],
                'items.*.slots.*.start_time' => ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:00)?$/'],
                'items.*.slots.*.end_time' => ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:00)?$/'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => __('mentor_availability.Invalid payload'),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $created = [];
            $updated = [];
            foreach ($data as $item) {
                $dayOfWeek = $item['day_of_week'] ?? null;
                $date = $item['date'] ?? null;
                $isRecurring = (bool)($item['is_recurring'] ?? true);
                $isActive = (bool)($item['is_active'] ?? true);
                
                foreach ($item['slots'] as $slot) {
                    $slotType = $slot['type'] ?? 'new';
                    $slotId = $slot['id'] ?? null;
                    
                    // Normalize time format (H:i to H:i:s)
                    $startTime = $this->normalizeTime($slot['start_time']);
                    $endTime = $this->normalizeTime($slot['end_time']);
                    
                    // Validate end_time > start_time
                    if ($this->timeToMinutes($endTime) <= $this->timeToMinutes($startTime)) {
                        return response()->json([
                            'message' => __('mentor_availability.End time must be after start time'),
                            'errors' => ['end_time' => [__('mentor_availability.End time must be after start time')]]
                        ], 422);
                    }
                    
                    $payload = [
                        'day_of_week' => $isRecurring ? $dayOfWeek : null,
                        'date' => $isRecurring ? null : $date,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'is_recurring' => $isRecurring,
                        'is_active' => $isActive,
                    ];

                    if ($slotType === 'updated' && $slotId) {
                        // Update existing slot
                        $existingSlot = MentorAvailability::where('id', $slotId)
                            ->where('mentor_id', $mentor->id)
                            ->first();
                        
                        if (!$existingSlot) {
                            return response()->json([
                                'message' => __('mentor_availability.Slot not found'),
                                'errors' => ['id' => [__('mentor_availability.Slot not found')]]
                            ], 404);
                        }

                        // Check overlaps excluding current slot
                        if ($this->checkForOverlaps($payload, $mentor->id, $slotId)) {
                            return response()->json([
                                'message' => __('mentor_availability.Time slots cannot overlap'),
                                'errors' => [
                                    'start_time' => [__('mentor_availability.Time slots cannot overlap')],
                                    'overlapping_slot' => [
                                        'day_of_week' => $dayOfWeek,
                                        'date' => $date,
                                        'start_time' => $slot['start_time'],
                                        'end_time' => $slot['end_time']
                                    ]
                                ]
                            ], 422);
                        }

                        $existingSlot->update($payload);
                        $updated[] = $existingSlot->fresh();
                    } else {
                        // Create new slot
                        if ($this->checkForOverlaps($payload, $mentor->id)) {
                            return response()->json([
                                'message' => __('mentor_availability.Time slots cannot overlap'),
                                'errors' => [
                                    'start_time' => [__('mentor_availability.Time slots cannot overlap')],
                                    'overlapping_slot' => [
                                        'day_of_week' => $dayOfWeek,
                                        'date' => $date,
                                        'start_time' => $slot['start_time'],
                                        'end_time' => $slot['end_time']
                                    ]
                                ]
                            ], 422);
                        }

                        $created[] = MentorAvailability::create(array_merge($payload, ['mentor_id' => $mentor->id]));
                    }
                }
            }

            // Merge created and updated slots
            $allSlots = array_merge($created, $updated);

            // Group response by day/date
            $grouped = [];
            foreach ($allSlots as $model) {
                $key = ($model->day_of_week ?: 'date') . '|' . ($model->date?->format('Y-m-d') ?? 'null');
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'day_of_week' => $model->day_of_week,
                        'date' => $model->date?->format('Y-m-d'),
                        'is_recurring' => (bool)$model->is_recurring,
                        'is_active' => (bool)$model->is_active,
                        'slots' => [],
                    ];
                }
                $grouped[$key]['slots'][] = [
                    'id' => $model->id,
                    'start_time' => $model->start_time->format('H:i'),
                    'end_time' => $model->end_time->format('H:i'),
                    'duration_minutes' => (new \App\Http\Resources\MentorAvailabilityResource($model))->toArray(request())['duration_minutes'],
                ];
            }

            return response()->json([
                'message' => __('mentor_availability.Slots saved successfully'),
                'data' => array_values($grouped),
            ], 201);
        }

        // SINGLE MODE (existing behavior)
        $isRecurring = filter_var($request->input('is_recurring', false), FILTER_VALIDATE_BOOLEAN);
        $singleRules = [
            'start_time' => ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:00)?$/'],
            'end_time' => ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:00)?$/'],
            'is_recurring' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($isRecurring) {
            $singleRules['day_of_week'] = ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'];
        } else {
            $singleRules['date'] = ['required', 'date', 'after_or_equal:today'];
        }

        $singleValidator = Validator::make($request->all(), $singleRules);
        if ($singleValidator->fails()) {
            return response()->json([
                'message' => __('mentor_availability.Invalid payload'),
                'errors' => $singleValidator->errors(),
            ], 422);
        }

        $validated = $singleValidator->validated();
        
        // Normalize time format (H:i to H:i:s)
        if (isset($validated['start_time'])) {
            $validated['start_time'] = $this->normalizeTime($validated['start_time']);
        }
        if (isset($validated['end_time'])) {
            $validated['end_time'] = $this->normalizeTime($validated['end_time']);
        }
        
        // Validate end_time > start_time
        if ($this->timeToMinutes($validated['end_time']) <= $this->timeToMinutes($validated['start_time'])) {
            return response()->json([
                'message' => __('mentor_availability.End time must be after start time'),
                'errors' => ['end_time' => [__('mentor_availability.End time must be after start time')]]
            ], 422);
        }

        $overlap = $this->checkForOverlaps($validated, $mentor->id);
        if ($overlap) {
            return response()->json([
                'message' => __('mentor_availability.Time slots cannot overlap'),
                'errors' => ['start_time' => [__('mentor_availability.Time slots cannot overlap')]]
            ], 422);
        }

        $availability = MentorAvailability::create(array_merge($validated, [
            'mentor_id' => $mentor->id,
        ]));

        return response()->json([
            'message' => __('mentor_availability.Slot created successfully'),
            'data' => new MentorAvailabilityResource($availability)
        ], 201);
    }

    /**
     * Update an existing availability slot
     */
    public function update(Request $request, MentorAvailability $availability): JsonResponse
    {
        $mentor = Auth::user();

        // BULK REPLACE MODE: if the incoming payload is a list, ignore the path id and replace the set
        $raw = $request->all();
        if (is_array($raw) && array_is_list($raw)) {
            // Accept legacy key 'solts' as 'slots'
            foreach ($raw as $idx => $it) {
                if (!isset($it['slots']) && isset($it['solts'])) {
                    $raw[$idx]['slots'] = $it['solts'];
                }
            }

            $validator = Validator::make(['items' => $raw], [
                'items' => ['required', 'array', 'min:1'],
                'items.*.day_of_week' => ['nullable', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
                'items.*.date' => ['nullable', 'date', 'after_or_equal:today'],
                'items.*.is_recurring' => ['sometimes', 'boolean'],
                'items.*.is_active' => ['sometimes', 'boolean'],
                'items.*.slots' => ['required', 'array', 'min:1'],
                'items.*.slots.*.start_time' => ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:00)?$/'],
                'items.*.slots.*.end_time' => ['required', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9](:00)?$/'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => __('mentor_availability.Invalid payload'),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $result = [];
            foreach ($raw as $item) {
                $isRecurring = filter_var($item['is_recurring'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $isActive = (bool)($item['is_active'] ?? true);
                $dayOfWeek = $item['day_of_week'] ?? null;
                $date = $item['date'] ?? null;

                // Replace existing target set (day_of_week or date)
                $delQuery = MentorAvailability::where('mentor_id', $mentor->id);
                if ($isRecurring) {
                    $delQuery->where('is_recurring', true)->where('day_of_week', $dayOfWeek);
                } else {
                    $delQuery->where('is_recurring', false)->whereDate('date', $date);
                }
                $delQuery->delete();

                $created = [];
                foreach ($item['slots'] as $slot) {
                    // Normalize time format (H:i to H:i:s)
                    $startTime = $this->normalizeTime($slot['start_time']);
                    $endTime = $this->normalizeTime($slot['end_time']);
                    
                    // Validate end_time > start_time
                    if ($this->timeToMinutes($endTime) <= $this->timeToMinutes($startTime)) {
                        return response()->json([
                            'message' => __('mentor_availability.End time must be after start time'),
                            'errors' => ['end_time' => [__('mentor_availability.End time must be after start time')]]
                        ], 422);
                    }
                    
                    $payload = [
                        'mentor_id' => $mentor->id,
                        'day_of_week' => $isRecurring ? $dayOfWeek : null,
                        'date' => $isRecurring ? null : $date,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'is_recurring' => $isRecurring,
                        'is_active' => $isActive,
                    ];

                    // Check overlaps within persisted set
                    if ($this->checkForOverlaps($payload, $mentor->id)) {
                        return response()->json([
                            'message' => __('mentor_availability.Time slots cannot overlap'),
                            'errors' => [
                                'start_time' => [__('mentor_availability.Time slots cannot overlap')],
                                'overlapping_slot' => [
                                    'day_of_week' => $isRecurring ? $dayOfWeek : null,
                                    'date' => $isRecurring ? null : $date,
                                    'start_time' => $slot['start_time'],
                                    'end_time' => $slot['end_time']
                                ]
                            ]
                        ], 422);
                    }

                    $created[] = MentorAvailability::create($payload);
                }

                $group = [
                    'day_of_week' => $isRecurring ? $dayOfWeek : null,
                    'date' => $isRecurring ? null : $date,
                    'is_recurring' => $isRecurring,
                    'is_active' => $isActive,
                    'slots' => [],
                ];
                foreach ($created as $slotModel) {
                    $group['slots'][] = [
                        'id' => $slotModel->id,
                        'start_time' => $slotModel->start_time->format('H:i'),
                        'end_time' => $slotModel->end_time->format('H:i'),
                        'duration_minutes' => (new \App\Http\Resources\MentorAvailabilityResource($slotModel))->toArray(request())['duration_minutes'],
                        'created_at' => $slotModel->created_at?->toISOString(),
                        'updated_at' => $slotModel->updated_at?->toISOString(),
                    ];
                }

                $result[] = $group;
            }

            return response()->json([
                'message' => __('mentor_availability.Slots updated successfully'),
                'data' => $result,
            ]);
        }

        // Check if the slot belongs to the mentor
        if ($availability->mentor_id !== $mentor->id) {
            return response()->json([
                'message' => __('mentor_availability.Unauthorized access')
            ], 403);
        }

        $data = $request->validated();
        
        // Normalize time format (H:i to H:i:s)
        if (isset($data['start_time'])) {
            $data['start_time'] = $this->normalizeTime($data['start_time']);
        }
        if (isset($data['end_time'])) {
            $data['end_time'] = $this->normalizeTime($data['end_time']);
        }
        
        // Validate end_time > start_time
        if (isset($data['start_time']) && isset($data['end_time'])) {
            if ($this->timeToMinutes($data['end_time']) <= $this->timeToMinutes($data['start_time'])) {
                return response()->json([
                    'message' => __('mentor_availability.End time must be after start time'),
                    'errors' => ['end_time' => [__('mentor_availability.End time must be after start time')]]
                ], 422);
            }
        }

        // Check for overlapping slots (excluding current slot)
        $overlap = $this->checkForOverlaps($data, $mentor->id, $availability->id);

        if ($overlap) {
            return response()->json([
                'message' => __('mentor_availability.Time slots cannot overlap'),
                'errors' => ['start_time' => [__('mentor_availability.Time slots cannot overlap')]]
            ], 422);
        }

        $availability->update($data);

        // Build grouped response for the affected day/date
        $fresh = $availability->fresh();
        $query = MentorAvailability::where('mentor_id', $mentor->id)
            ->where('is_active', true);

        if ($fresh->is_recurring) {
            $query->where('is_recurring', true)->where('day_of_week', $fresh->day_of_week);
        } else {
            $query->where('is_recurring', false)->whereDate('date', optional($fresh->date)->format('Y-m-d'));
        }

        $siblings = $query->orderBy('start_time')->get();

        $grouped = [
            'day_of_week' => $fresh->is_recurring ? $fresh->day_of_week : null,
            'date' => $fresh->is_recurring ? null : $fresh->date?->format('Y-m-d'),
            'is_recurring' => (bool) $fresh->is_recurring,
            'is_active' => (bool) $fresh->is_active,
            'slots' => [],
        ];

        foreach ($siblings as $slot) {
            $grouped['slots'][] = [
                'id' => $slot->id,
                'start_time' => $slot->start_time->format('H:i'),
                'end_time' => $slot->end_time->format('H:i'),
                'duration_minutes' => (new \App\Http\Resources\MentorAvailabilityResource($slot))->toArray(request())['duration_minutes'],
                'created_at' => $slot->created_at?->toISOString(),
                'updated_at' => $slot->updated_at?->toISOString(),
            ];
        }

        return response()->json([
            'message' => __('mentor_availability.Slot updated successfully'),
            'data' => $grouped,
        ]);
    }

    /**
     * Delete an availability slot
     */
    public function destroy(MentorAvailability $availability): JsonResponse
    {
        $mentor = Auth::user();

        // Check if the slot belongs to the mentor
        if ($availability->mentor_id !== $mentor->id) {
            return response()->json([
                'message' => __('mentor_availability.Unauthorized access')
            ], 403);
        }

        $availability->delete();

        return response()->json([
            'message' => __('mentor_availability.Slot deleted successfully')
        ]);
    }

    /**
     * Check if a slot overlaps with existing slots
     */
    private function checkForOverlaps(array $data, int $mentorId, ?int $excludeId = null): bool
    {
        $query = MentorAvailability::where('mentor_id', $mentorId)
            ->where('is_active', true);

        // Check for date or day_of_week
        if (!empty($data['date'])) {
            $query->where('date', $data['date']);
        } elseif (!empty($data['day_of_week'])) {
            $query->where('day_of_week', $data['day_of_week']);
        }

        // Exclude current slot if updating
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingSlots = $query->get();

        foreach ($existingSlots as $existing) {
            // Check if times overlap
            $start1 = $data['start_time'];
            $end1 = $data['end_time'];
            $start2 = $existing->start_time->format('H:i:s');
            $end2 = $existing->end_time->format('H:i:s');

            // Convert to minutes for comparison
            $start1Minutes = $this->timeToMinutes($start1);
            $end1Minutes = $this->timeToMinutes($end1);
            $start2Minutes = $this->timeToMinutes($start2);
            $end2Minutes = $this->timeToMinutes($end2);

            // Check for overlap
            if (!($end1Minutes <= $start2Minutes || $end2Minutes <= $start1Minutes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize time format from H:i to H:i:s
     */
    private function normalizeTime(string $time): string
    {
        // If already in H:i:s format, return as is
        if (substr_count($time, ':') === 2) {
            return $time;
        }
        
        // Convert H:i to H:i:s by appending :00
        if (substr_count($time, ':') === 1) {
            return $time . ':00';
        }
        
        return $time;
    }

    /**
     * Convert time string to minutes
     */
    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int)$hours * 60 + (int)$minutes;
    }
}

