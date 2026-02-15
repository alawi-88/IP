<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MentorAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'is_recurring',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the mentor that owns this availability slot.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    /**
     * Check if this slot overlaps with another slot for the same day/date
     */
    public function overlapsWith(MentorAvailability $otherSlot): bool
    {
        // Same mentor
        if ($this->mentor_id !== $otherSlot->mentor_id) {
            return false;
        }

        // For recurring slots, check day of week
        if ($this->is_recurring && $otherSlot->is_recurring) {
            if ($this->day_of_week !== $otherSlot->day_of_week) {
                return false;
            }
        }
        
        // For one-time slots, check date
        if (!$this->is_recurring && !$otherSlot->is_recurring) {
            if ($this->date !== $otherSlot->date) {
                return false;
            }
        }

        // Check time overlap
        return $this->timesOverlap(
            $this->start_time,
            $this->end_time,
            $otherSlot->start_time,
            $otherSlot->end_time
        );
    }

    /**
     * Check if two time ranges overlap
     */
    private function timesOverlap($start1, $end1, $start2, $end2): bool
    {
        // Convert times to minutes for easier comparison
        $start1Minutes = $this->timeToMinutes($start1);
        $end1Minutes = $this->timeToMinutes($end1);
        $start2Minutes = $this->timeToMinutes($start2);
        $end2Minutes = $this->timeToMinutes($end2);

        // Check for overlap
        return !($end1Minutes <= $start2Minutes || $end2Minutes <= $start1Minutes);
    }

    /**
     * Convert time to minutes
     */
    private function timeToMinutes($time): int
    {
        if (is_string($time)) {
            [$hours, $minutes] = explode(':', $time);
            return (int)$hours * 60 + (int)$minutes;
        }
        
        // If it's a datetime object
        return $time->hour * 60 + $time->minute;
    }

    /**
     * Get available slots for a specific date considering recurring slots
     */
    public static function getAvailableSlotsForDate($mentorId, $date)
    {
        $dateString = is_string($date) ? $date : $date->format('Y-m-d');
        $dayOfWeek = strtolower(now()->parse($dateString)->format('l')); // Get day name

        return self::where('mentor_id', $mentorId)
            ->where('is_active', true)
            ->where(function ($query) use ($dateString, $dayOfWeek) {
                $query->where(function ($q) use ($dateString) {
                    // One-time slots for this specific date
                    $q->where('is_recurring', false)
                      ->where('date', $dateString);
                })->orWhere(function ($q) use ($dayOfWeek) {
                    // Weekly recurring slots for this day of week
                    $q->where('is_recurring', true)
                      ->where('day_of_week', $dayOfWeek);
                });
            })
            ->get();
    }
}

