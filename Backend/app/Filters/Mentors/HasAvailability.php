<?php

namespace App\Filters\Mentors;

use Closure;

final readonly class HasAvailability
{
    public function handle($mentors, Closure $next)
    {
        // Convert string boolean to actual boolean for validation
        $hasAvailability = request()->input('has_availability');
        if ($hasAvailability !== null) {
            request()->merge([
                'has_availability' => filter_var($hasAvailability, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $hasAvailability
            ]);
        }

        request()->validate([
            'has_availability' => 'nullable|boolean',
            'date' => 'nullable|date',
            'available_on' => 'nullable|date', // Backward compatibility with MentorResource
        ]);

        // Filter if has_availability is explicitly set to true, or if date/available_on is provided
        // This filter only activates when these parameters are present, ensuring backward compatibility
        // If neither parameter is provided, the query passes through unchanged (no effect on other APIs)
        $shouldFilter = request()->boolean('has_availability') || request()->has('date') || request()->has('available_on');

        if ($shouldFilter) {
            // Support both 'date' and 'available_on' for backward compatibility
            $date = request()->input('date') ?? request()->input('available_on');
            $checkDate = $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : now()->toDateString();

            // Ensure date is valid
            try {
                $checkDate = \Carbon\Carbon::parse($checkDate)->format('Y-m-d');
            } catch (\Exception $e) {
                // If date parsing fails, return empty result
                $mentors = $mentors->whereRaw('1 = 0');
                return $next($mentors);
            }

            // Get mentor IDs that match the search criteria first (already filtered by Search)
            // We clone the query to preserve the original query builder
            $mentorIds = (clone $mentors)->pluck('id')->toArray();

            // If no mentors match the search, return empty result
            if (empty($mentorIds)) {
                $mentors = $mentors->whereRaw('1 = 0');
                return $next($mentors);
            }

            // Check which mentors actually have available slots on the specified date
            $mentorIdsWithSlots = [];
            foreach ($mentorIds as $mentorId) {
                try {
                    // Check if mentor has availability records for this date
                    $hasAvailabilityRecord = \App\Models\MentorAvailability::where('mentor_id', $mentorId)
                        ->where('is_active', true)
                        ->where(function($q) use ($checkDate) {
                            $dayOfWeek = strtolower(\Carbon\Carbon::parse($checkDate)->format('l'));
                            $q->where(function($subQuery) use ($checkDate, $dayOfWeek) {
                                // Date-specific availability
                                $subQuery->where(function($dateQuery) use ($checkDate) {
                                    $dateQuery->where('is_recurring', false)
                                             ->where('date', $checkDate);
                                })
                                // Recurring availability for this day of week
                                ->orWhere(function($recurringQuery) use ($dayOfWeek) {
                                    $recurringQuery->where('is_recurring', true)
                                                 ->where('day_of_week', $dayOfWeek);
                                });
                            });
                        })
                        ->exists();

                    // Only check for slots if mentor has availability records
                    if ($hasAvailabilityRecord) {
                        $slots = app(\App\Services\SessionSchedulingService::class)
                            ->getAvailableSlots($mentorId, $checkDate, 30, null, null);
                        if (!empty($slots)) {
                            $mentorIdsWithSlots[] = $mentorId;
                        }
                    }
                } catch (\Exception $e) {
                    // Skip mentors with errors
                    continue;
                }
            }

            // Filter to only mentors with actual available slots
            if (!empty($mentorIdsWithSlots)) {
                $mentors = $mentors->whereIn('id', $mentorIdsWithSlots);
            } else {
                // No mentors have available slots, return empty result
                $mentors = $mentors->whereRaw('1 = 0');
            }
        }

        return $next($mentors);
    }
}

