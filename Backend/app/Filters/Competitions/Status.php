<?php

namespace App\Filters\Competitions;

use Closure;

final readonly class Status
{
    public function handle($competitions, Closure $next)
    {
        $keyword = request('status');

        // Only validate if status parameter is provided
        if ($keyword !== null && $keyword !== '') {
            request()->validate([
                'status' => 'string|in:open,closed',
            ]);
        }

        if ($keyword === 'open') {
            $competitions->whereHas('stages', function ($query) {
                $query->where('slug', 'registration')
                      ->where(function ($subQ) {
                          // Registration has started (starts_at <= now)
                          $subQ->whereNull('starts_at')
                               ->orWhere('starts_at', '<=', now());
                      })
                      ->where('ends_at', '>', now());
            });
        } elseif ($keyword === 'closed') {
            $competitions->where(function ($query) {
                $query->where('registration_closed_date', '<=', now())
                      ->orWhere(function ($subQ) {
                          $subQ->whereDoesntHave('stages', function ($stageQ) {
                              $stageQ->where('slug', 'registration');
                          })->orWhereHas('stages', function ($stageQ) {
                              $stageQ->where('slug', 'registration')
                                     ->where(function ($statusQ) {
                                         // Registration has ended (ends_at <= now)
                                         $statusQ->where(function ($endsAtQ) {
                                             $endsAtQ->whereNull('ends_at')
                                                     ->orWhere('ends_at', '<=', now());
                                         })
                                         // OR registration hasn't started yet (starts_at > now)
                                         ->orWhere('starts_at', '>', now());
                                     });
                          });
                      });
            });
        }
        // If keyword is null, empty, or invalid, no filtering is applied

        return $next($competitions);
    }
}
