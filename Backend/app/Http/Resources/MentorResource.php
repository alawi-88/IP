<?php

namespace App\Http\Resources;

use App\Models\Track;
use App\Http\Controllers\Mentor\AvailabilityController;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MentorResource extends JsonResource
{
    protected $lang;
    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->lang = request()->getPreferredLanguage(['en', 'ar']);
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isParticipantsApi = $request->is('api/participants/*');

        // Helper to read translated value using preferred language
        $getTranslated = function ($value) {
            if (is_array($value)) {
                return $value[$this->lang] ?? ($value['en'] ?? ($value['ar'] ?? ''));
            }
            return $value;
        };

        // Public response for participants API - concise, translated, and privacy-safe
        if ($isParticipantsApi) {
            // Get program from application_id if provided
            $program = null;
            $applicationId = $request->input('application_id');
            if ($applicationId) {
                $application = \App\Models\ProgramApplication::find($applicationId);
                if ($application && $application->program) {
                    $program = new ProgramResource($application->program, null, $applicationId);
                }
            } elseif ($this->programs && $this->programs->isNotEmpty()) {
                // Fallback to first program from mentor's programs
                $program = new ProgramResource($this->programs->first());
            } elseif ($this->program) {
                // Fallback to mentor's default program
                $program = new ProgramResource($this->program);
            }

            return [
                'id' => $this->id,
                'name' => $getTranslated($this->name),
                'profession' => $getTranslated($this->profession),
                'experience' => $getTranslated($this->experience),
                'brief' => $getTranslated($this->brief),
                'image' => !empty($this->image) ? Storage::url($this->image) : null,
                'linkedin' => $this->linkedin,
                'facebook' => $this->facebook,
                'instagram' => $this->instagram,
                'is_visible' => $this->is_visible,
                'program' => $program,
                // Availability indicators - based on original availability windows
                'has_available_slots' => (function () use ($request) {
                    $availabilities = \App\Models\MentorAvailability::where('mentor_id', $this->id)
                        ->where('is_active', true);

                    // If available_on is specified, filter by date
                    if ($request->has('available_on')) {
                        $requestedDate = $request->input('available_on');
                        $dayOfWeek = strtolower(\Carbon\Carbon::parse($requestedDate)->format('l'));

                        $availabilities->where(function($q) use ($requestedDate, $dayOfWeek) {
                            $q->where(function($query) use ($requestedDate) {
                                $query->where('is_recurring', false)
                                      ->where('date', $requestedDate);
                            })->orWhere(function($query) use ($dayOfWeek) {
                                $query->where('is_recurring', true)
                                      ->where('day_of_week', $dayOfWeek);
                            });
                        });
                    }
                    // If no available_on, just check if any availabilities exist
                    // (will be expanded to dates in available_slots)

                    return $availabilities->exists();
                })(),
                'available_slots_count' => (function () use ($request) {
                    // Count unique availability windows (not generated slots)
                    $availabilities = \App\Models\MentorAvailability::where('mentor_id', $this->id)
                        ->where('is_active', true);

                    // If available_on is specified, filter by date
                    if ($request->has('available_on')) {
                        $requestedDate = $request->input('available_on');
                        $dayOfWeek = strtolower(now()->parse($requestedDate)->format('l'));

                        $availabilities->where(function($q) use ($requestedDate, $dayOfWeek) {
                            $q->where(function($query) use ($requestedDate) {
                                $query->where('is_recurring', false)
                                      ->where('date', $requestedDate);
                            })->orWhere(function($query) use ($dayOfWeek) {
                                $query->where('is_recurring', true)
                                      ->where('day_of_week', $dayOfWeek);
                            });
                        });

                        return $availabilities->count();
                    } else {
                        // Count total unique availability windows for next 30 days
                        $allAvailabilities = $availabilities->get();
                        $uniqueSlots = [];

                        foreach ($allAvailabilities as $availability) {
                            if ($availability->date) {
                                $dates = [$availability->date->format('Y-m-d')];
                            } else {
                                $dates = [];
                                $dayOfWeek = strtolower($availability->day_of_week);
                                $carbonDayMap = [
                                    'sunday' => \Carbon\Carbon::SUNDAY,
                                    'monday' => \Carbon\Carbon::MONDAY,
                                    'tuesday' => \Carbon\Carbon::TUESDAY,
                                    'wednesday' => \Carbon\Carbon::WEDNESDAY,
                                    'thursday' => \Carbon\Carbon::THURSDAY,
                                    'friday' => \Carbon\Carbon::FRIDAY,
                                    'saturday' => \Carbon\Carbon::SATURDAY,
                                ];

                                if (isset($carbonDayMap[$dayOfWeek])) {
                                    $startDate = now()->startOfDay();
                                    for ($i = 0; $i < 30; $i++) {
                                        $checkDate = $startDate->copy()->addDays($i);
                                        if ($checkDate->dayOfWeek === $carbonDayMap[$dayOfWeek]) {
                                            $dates[] = $checkDate->toDateString();
                                        }
                                    }
                                }
                            }

                            $startTime = $availability->start_time instanceof \Carbon\Carbon
                                ? $availability->start_time->format('H:i:s')
                                : \Carbon\Carbon::parse($availability->start_time)->format('H:i:s');
                            $endTime = $availability->end_time instanceof \Carbon\Carbon
                                ? $availability->end_time->format('H:i:s')
                                : \Carbon\Carbon::parse($availability->end_time)->format('H:i:s');

                            foreach ($dates as $date) {
                                $uniqueKey = $date . '_' . $startTime . '_' . $endTime;
                                if (!in_array($uniqueKey, $uniqueSlots)) {
                                    $uniqueSlots[] = $uniqueKey;
                                }
                            }
                        }

                        return count($uniqueSlots);
                    }
                })(),
                // Collect available slots - divided into 30-minute chunks, expanded to dates
                'available_slots' => (function () use ($request) {
                    // Get all active availabilities for this mentor
                    $availabilities = \App\Models\MentorAvailability::where('mentor_id', $this->id)
                        ->where('is_active', true)
                        ->orderBy('day_of_week')
                        ->orderBy('start_time')
                        ->get();

                    $slotDurationMinutes = 30; // Divide slots into 30-minute chunks
                    $allSlots = [];
                    $seenKeys = []; // Track unique slots to avoid duplicates

                    // Day name mapping
                    $dayNameMap = [
                        'sunday' => 'Sunday',
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                    ];

                    foreach ($availabilities as $availability) {
                        // Get the start and end times
                        $startTime = $availability->start_time instanceof \Carbon\Carbon
                            ? $availability->start_time
                            : \Carbon\Carbon::parse($availability->start_time);
                        $endTime = $availability->end_time instanceof \Carbon\Carbon
                            ? $availability->end_time
                            : \Carbon\Carbon::parse($availability->end_time);

                        // Calculate time range in minutes
                        $startHour = $startTime->hour;
                        $startMinute = $startTime->minute;
                        $endHour = $endTime->hour;
                        $endMinute = $endTime->minute;

                        $startTotal = $startHour * 60 + $startMinute;
                        $endTotal = $endHour * 60 + $endMinute;

                        // Get dates this availability applies to
                        $dates = [];
                        $dayName = null;

                        if ($availability->date) {
                            // Date-specific availability
                            $dates = [$availability->date->format('Y-m-d')];
                        } else {
                            // Recurring availability - get only the next occurrence (first matching date)
                            $dayOfWeek = strtolower($availability->day_of_week);
                            $dayName = $dayNameMap[$dayOfWeek] ?? ucfirst($dayOfWeek);

                            $carbonDayMap = [
                                'sunday' => \Carbon\Carbon::SUNDAY,
                                'monday' => \Carbon\Carbon::MONDAY,
                                'tuesday' => \Carbon\Carbon::TUESDAY,
                                'wednesday' => \Carbon\Carbon::WEDNESDAY,
                                'thursday' => \Carbon\Carbon::THURSDAY,
                                'friday' => \Carbon\Carbon::FRIDAY,
                                'saturday' => \Carbon\Carbon::SATURDAY,
                            ];

                            if (isset($carbonDayMap[$dayOfWeek])) {
                                $startDate = now()->startOfDay();
                                // Find the next occurrence of this day
                                for ($i = 0; $i < 7; $i++) {
                                    $checkDate = $startDate->copy()->addDays($i);
                                    if ($checkDate->dayOfWeek === $carbonDayMap[$dayOfWeek]) {
                                        $dates[] = $checkDate->toDateString();
                                        break; // Only get the first/next occurrence
                                    }
                                }
                            }
                        }

                        // Filter by available_on if specified
                        if ($request->has('available_on')) {
                            $requestedDate = $request->input('available_on');
                            if (!in_array($requestedDate, $dates)) {
                                continue; // Skip this availability if it doesn't match the requested date
                            }
                            $dates = [$requestedDate];
                        }

                        // Generate slots for each date
                        foreach ($dates as $date) {
                            // Generate slots within this availability window (divide into 30-minute chunks)
                            $cursor = $startTotal;
                            while (($cursor + $slotDurationMinutes) <= $endTotal) {
                                $slotStartH = str_pad((string) floor($cursor / 60), 2, '0', STR_PAD_LEFT);
                                $slotStartM = str_pad((string) ($cursor % 60), 2, '0', STR_PAD_LEFT);

                                $slotEnd = $cursor + $slotDurationMinutes;
                                $slotEndH = str_pad((string) floor($slotEnd / 60), 2, '0', STR_PAD_LEFT);
                                $slotEndM = str_pad((string) ($slotEnd % 60), 2, '0', STR_PAD_LEFT);

                                $slotStartTime = $slotStartH . ':' . $slotStartM;
                                $slotEndTime = $slotEndH . ':' . $slotEndM;

                                // Create full datetime to check if slot is in the past
                                try {
                                    $slotDateTime = \Carbon\Carbon::parse("{$date} {$slotStartTime}:00");
                                    // Skip past slots - only show future slots
                                    if ($slotDateTime->isPast()) {
                                        $cursor += $slotDurationMinutes;
                                        continue;
                                    }
                                } catch (\Exception $e) {
                                    // Skip invalid dates
                                    $cursor += $slotDurationMinutes;
                                    continue;
                                }

                                // Create unique key with date, start_time, and end_time
                                $uniqueKey = $date . '_' . $slotStartTime . '_' . $slotEndTime;

                                // Only add if we haven't seen this exact slot before
                                if (!isset($seenKeys[$uniqueKey])) {
                                    $seenKeys[$uniqueKey] = true;

                                    $slot = [
                                        'id' => $availability->id,
                                        'start_time' => $slotStartTime,
                                        'end_time' => $slotEndTime,
                                        'date' => $date,
                                        'duration_minutes' => $slotDurationMinutes,
                                    ];

                                    // Add day name for recurring slots
                                    if ($dayName) {
                                        $slot['day'] = $dayName;
                                    }

                                    $allSlots[] = $slot;
                                }

                                $cursor += $slotDurationMinutes;
                            }
                        }
                    }

                    // Filter out any past slots (final safety check)
                    $allSlots = array_filter($allSlots, function($slot) {
                        $slotDate = $slot['date'] ?? null;
                        $slotStartTime = $slot['start_time'] ?? null;

                        if (!$slotDate || !$slotStartTime) {
                            return false;
                        }

                        try {
                            $slotDateTime = \Carbon\Carbon::parse("{$slotDate} {$slotStartTime}:00");
                            return !$slotDateTime->isPast();
                        } catch (\Exception $e) {
                            return false;
                        }
                    });

                    // Sort slots by date, then by start_time
                    usort($allSlots, function($a, $b) {
                        $dateCompare = strcmp($a['date'], $b['date']);
                        if ($dateCompare !== 0) {
                            return $dateCompare;
                        }
                        return strcmp($a['start_time'], $b['start_time']);
                    });

                    return array_values($allSlots);
                })(),

            ];
        }

        // Default/full response
        return [
            'id' => $this->id,
            'program' => new ProgramResource($this->program),
            'programs' => ProgramResource::collection($this->programs),
            'track' => new TrackResource($this->track),
            'name' => $this->name,
            'experience' => $this->experience,
            'brief' => $this->brief,
            'profession' => $this->profession,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => !empty($this->image) ? Storage::url($this->image) : null,
            'linkedin' => $this->linkedin,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'last_login_at' => $this->last_login_at,
            'is_visible' => $this->is_visible,
        ];
    }
}
