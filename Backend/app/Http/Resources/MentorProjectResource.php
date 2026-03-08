<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class MentorProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $formSubmissions = $this->form_submissions instanceof SchemalessAttributes
            ? $this->form_submissions->toArray()
            : (is_array($this->form_submissions) ? $this->form_submissions : []);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'type' => $this->type ?? 'submission',
            'total_score' => $this->total_score,
            'evaluation_status' => $this->evaluation_status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Project details from form submissions
            'project_name' => $formSubmissions['project_name'] ?? null,
            'form_submissions' => $this->formatFormSubmissions($formSubmissions),

            // Track and Sub Track at root level (from team or form_submissions for individual)
            'track' => $this->getTrackData($formSubmissions),
            'sub_track' => $this->getSubTrackData($formSubmissions),

            // Team information
            'team' => $this->when($this->team, [
                'id' => $this->team?->id,
                'name' => $this->team?->name,
                'members_count' => $this->team?->members?->count() ?? 0,
                'members' => $this->team?->members?->map(function ($member) {
                    return [
                        'id' => $member->participant?->id,
                        'name' => $member->participant?->name,
                        'email' => $member->participant?->email,
                        'phone' => $member->participant?->phone,
                        'avatar' => $member->participant?->avatar,
                        'is_leader' => $member->is_leader,
                    ];
                }),
                'track' => $this->team?->track ? [
                    'id' => $this->team->track->id,
                    'name' => $this->team->track->name,
                ] : null,
                'sub_track' => $this->team?->subTrack ? [
                    'id' => $this->team->subTrack->id,
                    'name' => $this->team->subTrack->name,
                ] : null,
            ]),

            // Program information
            'program' => $this->when($this->program, [
                'id' => $this->program?->id,
                'title' => $this->program?->title,
                'slug' => $this->program?->slug,
            ]),

            // Application & Participant info
            'participant' => $this->when($this->application?->participant, [
                'id' => $this->application?->participant?->id,
                'name' => $this->application?->participant?->name,
                'email' => $this->application?->participant?->email,
            ]),

            // Form information
            'form' => $this->when($this->form, [
                'id' => $this->form?->id,
                'name' => $this->form?->name,
            ]),

            // All comments from admin and participant
            'comments' => $this->when(
                $this->relationLoaded('comments'),
                function () {
                    return $this->comments->map(function ($comment) {
                        return $this->formatComment($comment);
                    });
                }
            ),

            'comments_count' => $this->relationLoaded('comments') 
                ? $this->comments->count() 
                : $this->comments()->count(),

            'unread_comments_count' => $this->comments()
                ->whereNull('user_id')
                ->whereNotNull('author_type')
                ->where('is_read', false)
                ->count(),
        ];
    }

    /**
     * Get human-readable status label.
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => __('project_status.statuses.pending'),
            'qualified' => __('project_status.statuses.qualified'),
            'not_qualified' => __('project_status.statuses.not_qualified'),
            'winner' => __('project_status.statuses.winner'),
            default => ucfirst($this->status ?? 'unknown'),
        };
    }

    /**
     * Get track data from team or form_submissions.
     */
    protected function getTrackData(array $formSubmissions): ?array
    {
        // First try to get from team
        if ($this->team?->track) {
            return [
                'id' => $this->team->track->id,
                'name' => is_array($this->team->track->name) 
                    ? ($this->team->track->name['en'] ?? $this->team->track->name['ar'] ?? 'Unknown')
                    : $this->team->track->name,
            ];
        }
        
        // For individual participants, try to get from form_submissions
        $trackValue = $formSubmissions['track'] ?? null;
        if ($trackValue) {
            $track = is_numeric($trackValue) 
                ? \App\Models\Track::find($trackValue) 
                : \App\Models\Track::where('slug', $trackValue)->first();
            
            if ($track) {
                return [
                    'id' => $track->id,
                    'name' => is_array($track->name) 
                        ? ($track->name['en'] ?? $track->name['ar'] ?? 'Unknown')
                        : $track->name,
                ];
            }
        }
        
        return null;
    }

    /**
     * Get sub_track data from team or form_submissions.
     */
    protected function getSubTrackData(array $formSubmissions): ?array
    {
        // First try to get from team
        if ($this->team?->subTrack) {
            return [
                'id' => $this->team->subTrack->id,
                'name' => is_array($this->team->subTrack->name) 
                    ? ($this->team->subTrack->name['en'] ?? $this->team->subTrack->name['ar'] ?? 'Unknown')
                    : $this->team->subTrack->name,
            ];
        }
        
        // For individual participants, try to get from form_submissions
        $subTrackValue = $formSubmissions['sub_track'] ?? null;
        if ($subTrackValue) {
            $subTrack = is_numeric($subTrackValue) 
                ? \App\Models\SubTrack::find($subTrackValue) 
                : \App\Models\SubTrack::where('slug', $subTrackValue)->first();
            
            if ($subTrack) {
                return [
                    'id' => $subTrack->id,
                    'name' => is_array($subTrack->name) 
                        ? ($subTrack->name['en'] ?? $subTrack->name['ar'] ?? 'Unknown')
                        : $subTrack->name,
                ];
            }
        }
        
        return null;
    }

    /**
     * Format form submissions for display with type, label, and value.
     */
    protected function formatFormSubmissions(array $submissions): array
    {
        $formatted = [];
        $formFields = collect();
        
        // Get form fields if form is loaded
        if ($this->form_id && $this->form) {
            $formFields = $this->form->fields()
                ->whereNotIn('type', ['section_header', 'paragraph'])
                ->orderBy('sort')
                ->get()
                ->keyBy('slug');
        }

        // Track which fields we've processed
        $processedKeys = [];

        // Fields to skip (already returned at root level or internal)
        $skipFields = ['_token', 'application_id', 'program_id', 'project_name', 'track', 'sub_track'];

        // First, process all form fields (even if missing from submissions)
        foreach ($formFields as $slug => $field) {
            // Skip fields that are returned at root level
            if (in_array($slug, $skipFields)) {
                $processedKeys[] = $slug;
                continue;
            }
            
            $processedKeys[] = $slug;
            $value = $submissions[$slug] ?? null;
            
            // Get localized label
            $label = is_array($field->label)
                ? ($field->label[app()->getLocale()] ?? $field->label['en'] ?? $field->label['ar'] ?? $slug)
                : ($field->label ?? $slug);

            $formatted[] = $this->formatFieldValue($slug, $field->type, $label, $value, $field);
        }

        // Then, add any submission values that don't have corresponding form fields
        foreach ($submissions as $key => $value) {
            // Skip internal/technical fields, root-level fields, and already processed keys
            if (in_array($key, $skipFields) || in_array($key, $processedKeys)) {
                continue;
            }

            // Try to find field by slug in database
            $field = \App\Models\FormField::where('slug', $key)->first();
            
            $type = $field?->type ?? $this->guessFieldType($value);
            $label = $field ? (is_array($field->label) 
                ? ($field->label[app()->getLocale()] ?? $field->label['en'] ?? $field->label['ar'] ?? $key)
                : ($field->label ?? $key)) 
                : \Illuminate\Support\Str::headline($key);

            $formatted[] = $this->formatFieldValue($key, $type, $label, $value, $field);
        }

        return $formatted;
    }

    /**
     * Format a single field value with type, label, and processed value.
     */
    protected function formatFieldValue(string $key, string $type, string $label, $value, $field = null): array
    {
        // Handle file paths - return URL as direct string
        if (is_string($value) && str_starts_with($value, 'uploads/files/')) {
            return [
                'key' => $key,
                'type' => 'file',
                'label' => $label,
                'value' => Storage::url($value),
            ];
        }

        // Handle track field
        if ($key === 'track' && $value !== null) {
            $track = is_numeric($value) 
                ? \App\Models\Track::find($value) 
                : \App\Models\Track::where('slug', $value)->first();
            if ($track) {
                $value = is_array($track->name) 
                    ? ($track->name[app()->getLocale()] ?? $track->name['en'] ?? $track->name['ar'] ?? $value)
                    : $track->name;
            }
        }

        // Handle sub_track field
        if ($key === 'sub_track' && $value !== null) {
            $subTrack = is_numeric($value) 
                ? \App\Models\SubTrack::find($value) 
                : \App\Models\SubTrack::where('slug', $value)->first();
            if ($subTrack) {
                $value = is_array($subTrack->name) 
                    ? ($subTrack->name[app()->getLocale()] ?? $subTrack->name['en'] ?? $subTrack->name['ar'] ?? $value)
                    : $subTrack->name;
            }
        }

        // Handle option-based fields (dropdown, radio, checkbox, multi_select)
        if ($field && in_array($type, ['dropdown', 'radio', 'checkbox', 'multi_select', 'rating']) && $value !== null) {
          // $value = $this->formatOptionValue($field, $value);
          $isArrayValue = is_array($value); 
          $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));
          if ($isArrayValue || $isCommaSeparatedString) {
            // Convert string to array if needed
            $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
            // Pass the field object directly to avoid re-querying
            $value = \App\Models\ProgramApplication::formatFormFieldValueStatic($key, $arrayValue, $field);
        }else{
            $value = $this->formatOptionValue($field, $value);
        }
        }

        return [
            'key' => $key,
            'type' => $type,
            'label' => $label,
           // 'value' => $value ?? __('mentor.no_answer'),
           'value' => $value,

        ];
    }

    /**
     * Format option-based field values to show labels instead of numeric indices.
     */
    protected function formatOptionValue($field, $value)
    {
        $options = $field->options ?? [];
        $processedOptions = [];
        
        // Process options to handle both string and array formats
        if (isset($options['en']) && isset($options['ar']) && is_string($options['en']) && is_string($options['ar'])) {
            $enOptions = \App\Models\FormField::parseOptionsString($options['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($options['ar']);
            $maxLength = max(count($enOptions), count($arOptions));

            for ($i = 0; $i < $maxLength; $i++) {
                $processedOptions[] = [
                    'en' => $enOptions[$i] ?? '',
                    'ar' => $arOptions[$i] ?? ''
                ];
            }
        } elseif (is_array($options)) {
            $processedOptions = $options;
        }

        $currentLang = app()->getLocale();

        // Handle array values (for checkbox and multi_select)
        if (is_array($value)) {
            $labels = [];
            foreach ($value as $val) {
                if ($val === null || $val === '') continue;
                $labels[] = $this->getOptionLabel($processedOptions, $val, $currentLang);
            }
            return implode(', ', $labels);
        }

        // Handle single value
        if (is_numeric($value)) {
            return $this->getOptionLabel($processedOptions, $value, $currentLang);
        }

        return $value;
    }

    /**
     * Get option label from processed options.
     */
    protected function getOptionLabel(array $options, $value, string $lang): string
    {
        if (is_numeric($value)) {
            $index = (int)$value - 1; // Convert to 0-based index
            if (isset($options[$index])) {
                $option = $options[$index];
                if (is_array($option)) {
                    return $lang === 'ar' ? ($option['ar'] ?? $option['en'] ?? (string)$value) : ($option['en'] ?? $option['ar'] ?? (string)$value);
                }
                return is_string($option) ? $option : (string)$value;
            }
        }
        return (string)$value;
    }

    /**
     * Guess field type based on value.
     */
    protected function guessFieldType($value): string
    {
        if (is_string($value) && str_starts_with($value, 'uploads/files/')) {
            return 'file';
        }
        if (is_array($value)) {
            return 'multi_select';
        }
        if (is_bool($value)) {
            return 'checkbox';
        }
        if (is_numeric($value)) {
            return 'number';
        }
        return 'text';
    }

    /**
     * Format a comment for API response.
     */
    protected function formatComment($comment): array
    {
        // Determine author type
        $authorType = 'participant';
        if ($comment->user_id && !$comment->author_type) {
            $authorType = 'admin';
        } elseif ($comment->author_type === \App\Models\Mentor::class) {
            $authorType = 'mentor';
        }

        // Transform attachments to full URLs
        $attachments = collect($comment->attachments ?? [])->map(function ($path) {
            return Storage::disk('public')->url($path);
        })->toArray();

        // Get author name
        $authorName = null;
        if ($comment->user) {
            $authorName = $comment->user->name;
        } elseif ($comment->author) {
            $authorName = is_array($comment->author->name)
                ? ($comment->author->name['en'] ?? $comment->author->name['ar'] ?? 'Unknown')
                : $comment->author->name;
        }

        return [
            'id' => $comment->id,
            'project_id' => $comment->project_id,
            'comment' => $comment->comment,
            'attachments' => $attachments,
            'is_read' => $comment->is_read,
            'author_type' => $authorType,
            'author' => [
                'id' => $comment->user_id ?? $comment->author_id,
                'name' => $authorName,
            ],
            'created_at' => $comment->created_at?->toIso8601String(),
            'updated_at' => $comment->updated_at?->toIso8601String(),
        ];
    }
}

