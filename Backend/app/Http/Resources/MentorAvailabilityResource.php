<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MentorAvailabilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $slotDuration = (int) ($request->get('slot_duration') ?? 30);

        return [
            'id' => $this->id,
            'mentor_id' => $this->mentor_id,
            'date' => $this->date?->format('Y-m-d'),
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time->format('H:i'),
            'end_time' => $this->end_time->format('H:i'),
            'is_recurring' => $this->is_recurring,
            'is_active' => $this->is_active,
            'duration_minutes' => $this->getDurationInMinutes(),
            'available_slots' => $this->generateSlots($slotDuration),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Calculate duration in minutes
     */
    private function getDurationInMinutes(): int
    {
        $start = $this->start_time;
        $end = $this->end_time;

        // Convert to minutes since midnight
        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes = $end->hour * 60 + $end->minute;

        return $endMinutes - $startMinutes;
    }

    /**
     * Generate plain slots within this availability window.
     */
    private function generateSlots(int $durationMinutes): array
    {
        if ($durationMinutes <= 0) {
            $durationMinutes = 30;
        }

        $slots = [];

        $startHour = $this->start_time->hour;
        $startMinute = $this->start_time->minute;
        $endHour = $this->end_time->hour;
        $endMinute = $this->end_time->minute;

        $startTotal = $startHour * 60 + $startMinute;
        $endTotal = $endHour * 60 + $endMinute;

        $cursor = $startTotal;
        while (($cursor + $durationMinutes) <= $endTotal) {
            $slotStartH = str_pad((string) floor($cursor / 60), 2, '0', STR_PAD_LEFT);
            $slotStartM = str_pad((string) ($cursor % 60), 2, '0', STR_PAD_LEFT);

            $slotEnd = $cursor + $durationMinutes;
            $slotEndH = str_pad((string) floor($slotEnd / 60), 2, '0', STR_PAD_LEFT);
            $slotEndM = str_pad((string) ($slotEnd % 60), 2, '0', STR_PAD_LEFT);

            $slots[] = [
                'start_time' => $slotStartH . ':' . $slotStartM,
                'end_time' => $slotEndH . ':' . $slotEndM,
                'duration_minutes' => $durationMinutes,
            ];

            $cursor += $durationMinutes;
        }

        return $slots;
    }
}

