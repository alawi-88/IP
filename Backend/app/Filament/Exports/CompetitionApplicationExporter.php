<?php

namespace App\Filament\Exports;

use App\Models\CompetitionApplication;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Track;
use App\Models\SubTrack;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class CompetitionApplicationExporter extends BaseExporter
{
    protected static ?string $model = CompetitionApplication::class;

    /**
     * Get registration form fields to use as dynamic export columns.
     * Uses current competition from session when set; otherwise all forms that have applications.
     */
    protected static function getFormFieldsForExport(): \Illuminate\Support\Collection
    {
        $currentCompetitionId = session('current_competition_id');
        if (!$currentCompetitionId && function_exists('request') && request()->hasSession()) {
            $currentCompetitionId = request()->session()->get('current_competition_id');
        }

        if ($currentCompetitionId) {
            $form = Form::where('competition_id', $currentCompetitionId)
                ->registrationType()
                ->published()
                ->active()
                ->first();
            if ($form) {
                return $form->fields()
                    ->whereNotIn('type', ['section_header', 'paragraph'])
                    ->orderBy('sort')
                    ->get();
            }
        }

        // Fallback: collect all unique form fields from forms that have at least one application
        $formIds = CompetitionApplication::query()
            ->select('form_id')
            ->distinct()
            ->whereNotNull('form_id')
            ->pluck('form_id')
            ->filter()
            ->unique()
            ->values();

        if ($formIds->isEmpty()) {
            return collect();
        }

        $allFields = collect();
        foreach (Form::whereIn('id', $formIds)->get() as $form) {
            foreach ($form->fields()->whereNotIn('type', ['section_header', 'paragraph'])->orderBy('sort')->get() as $field) {
                if (!$allFields->has($field->slug)) {
                    $allFields->put($field->slug, $field);
                }
            }
        }

        return $allFields->values();
    }

    /**
     * Get human-readable label for a form field (for column header).
     */
    protected static function getFormFieldLabel(FormField $field): string
    {
        $label = $field->label;
        if (is_array($label)) {
            return trim((string) ($label['en'] ?? $label['ar'] ?? $field->slug ?? ''));
        }
        return trim((string) ($label ?? $field->slug ?? ''));
    }

    /**
     * Format a single form submission value for export (option labels, track names, arrays, etc.).
     */
    protected static function formatFormSubmissionValue(CompetitionApplication $record, string $slug, ?FormField $formField): string
    {
        $submissions = $record->form_submissions instanceof \Spatie\SchemalessAttributes\SchemalessAttributes
            ? $record->form_submissions->toArray()
            : (array) $record->form_submissions;
        $value = $submissions[$slug] ?? null;

        if ($value === null || $value === '') {
            return '';
        }

        // Use the record's form field when available; always load fresh so options are present (e.g. in queue)
        $fieldForOptions = $formField;
        if ($record->form_id && $slug) {
            $recordFormField = FormField::where('form_id', $record->form_id)->where('slug', $slug)->first();
            if ($recordFormField) {
                $fieldForOptions = $recordFormField;
            }
        }
        if ($fieldForOptions && ($fieldForOptions->options === null || $fieldForOptions->options === [])) {
            $fresh = FormField::find($fieldForOptions->id);
            if ($fresh) {
                $fieldForOptions = $fresh;
            }
        }

        // Track / sub_track: resolve to display name
        if ($slug === 'track') {
            $track = is_numeric($value)
                ? Track::find((int) $value)
                : Track::where('slug', $value)->first();
            if ($track) {
                $name = $track->name;
                return is_array($name) ? ($name['en'] ?? $name['ar'] ?? $track->slug ?? '') : (string) $name;
            }
            return (string) $value;
        }
        if ($slug === 'sub_track') {
            $subTrack = is_numeric($value)
                ? SubTrack::find((int) $value)
                : SubTrack::where('slug', $value)->first();
            if ($subTrack) {
                $name = $subTrack->name;
                return is_array($name) ? ($name['en'] ?? $name['ar'] ?? $subTrack->slug ?? '') : (string) $name;
            }
            return (string) $value;
        }

        // Array (e.g. multi_select, checkbox group)
        if (is_array($value)) {
            $value = array_map(function ($v) use ($formField) {
                if (is_bool($v)) {
                    return $v ? 'Yes' : 'No';
                }
                // Resolve option keys to labels for checkbox/multi_select
                if ($formField && in_array($formField->type, ['checkbox', 'multi_select'], true)) {
                    $label = static::getOptionLabel($formField, $v);
                    if ($label !== null) {
                        return $label;
                    }
                }
                return (string) $v;
            }, $value);
            return implode(', ', $value);
        }

        // Comma-separated string of IDs (e.g. "1,4,2,3" from checkbox/multi_select)
        if ($formField && in_array($formField->type, ['checkbox', 'multi_select'], true)
            && is_string($value) && str_contains($value, ',')
            && preg_match('/^\s*\d+(\s*,\s*\d+)*\s*$/', trim($value))) {
            $ids = array_map('trim', explode(',', $value));
            $labels = [];
            foreach ($ids as $id) {
                $label = static::getOptionLabel($formField, $id);
                $labels[] = $label !== null ? $label : $id;
            }
            return implode(', ', $labels);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        // Option-based fields: resolve numeric/index to label
        if ($formField && in_array($formField->type, ['dropdown', 'radio', 'rating', 'checkbox', 'multi_select'], true)) {
            $label = static::getOptionLabel($formField, $value);
            if ($label !== null) {
                return $label;
            }
        }

        // File upload fields: output full URL so the link opens when pasted from the export
        if ($formField && $formField->type === 'file') {
            return static::filePathToExportUrl($value);
        }
        // Value may be a file path stored without form field context (e.g. legacy or dynamic key)
        if (static::looksLikeStoragePath($value)) {
            return static::filePathToExportUrl($value);
        }

        return (string) $value;
    }

    /**
     * Check if value looks like a storage path (relative path to a file), not a full URL.
     */
    protected static function looksLikeStoragePath($value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return false;
        }
        return str_contains($value, '/') || str_contains($value, '\\');
    }

    /**
     * Format comment attachments array to full URLs (comma-separated).
     */
    protected static function formatCommentAttachments($attachments): string
    {
        if ($attachments === null || $attachments === '') {
            return '';
        }
        $paths = is_array($attachments) ? $attachments : (array) json_decode((string) $attachments, true);
        if (empty($paths)) {
            return '';
        }
        return static::filePathToExportUrl($paths);
    }

    /**
     * Convert stored file path(s) to full URL(s) for export so links open when pasted in browser.
     */
    protected static function filePathToExportUrl($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value)) {
            $urls = array_map(function ($path) {
                return static::filePathToExportUrl($path);
            }, $value);
            return implode(', ', array_filter($urls));
        }
        $path = (string) $value;
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            return URL::to($path);
        }
        return URL::to(Storage::disk('public')->url($path));
    }

    /**
     * Get option label from FormField for a given value (handles numeric index or value key).
     */
    protected static function getOptionLabel(FormField $field, $value): ?string
    {
        if (!$field->options || !is_array($field->options)) {
            return null;
        }

        $options = $field->options;
        $currentLang = app()->getLocale();
        $useAr = $currentLang === 'ar';

        // String format (en/ar)
        if (isset($options['en']) && is_string($options['en']) && isset($options['ar']) && is_string($options['ar'])) {
            $enOpts = FormField::parseOptionsString($options['en']);
            $arOpts = FormField::parseOptionsString($options['ar']);
            if (is_numeric($value)) {
                $idx = (int) $value - 1; // 1-based (e.g. id: 1 -> index 0)
                if ($idx >= 0 && isset($enOpts[$idx])) {
                    return $useAr ? ($arOpts[$idx] ?? $enOpts[$idx]) : $enOpts[$idx];
                }
                $idx = (int) $value; // 0-based fallback (e.g. index 0, 1, 2)
                if (isset($enOpts[$idx])) {
                    return $useAr ? ($arOpts[$idx] ?? $enOpts[$idx]) : $enOpts[$idx];
                }
            } else {
                $pos = array_search($value, $enOpts, true);
                if ($pos !== false) {
                    return $useAr ? ($arOpts[$pos] ?? $enOpts[$pos]) : $enOpts[$pos];
                }
            }
            return null;
        }

        // Array of options
        if (isset($options[0]) && is_array($options[0])) {
            if (isset($options[0]['value'])) {
                foreach ($options as $opt) {
                    $optVal = $opt['value'] ?? null;
                    if ((string) $optVal === (string) $value) {
                        $label = $opt['en'] ?? $opt['ar'] ?? $opt['label']['en'] ?? $opt['label']['ar'] ?? null;
                        if ($label === null && is_string($opt['label'] ?? null)) {
                            $label = $opt['label'];
                        }
                        if ($label === null && isset($opt['value'])) {
                            $label = is_string($opt['value']) ? $opt['value'] : (string) $opt['value'];
                        }
                        return $label !== null ? $label : (string) $optVal;
                    }
                }
            }
            if (is_numeric($value)) {
                $idx = (int) $value - 1; // 1-based
                if ($idx >= 0 && isset($options[$idx])) {
                    $opt = $options[$idx];
                    if (is_array($opt)) {
                        return $opt['en'] ?? $opt['ar'] ?? $opt['label']['en'] ?? $opt['label']['ar'] ?? null;
                    }
                }
                $idx = (int) $value; // 0-based fallback
                if (isset($options[$idx])) {
                    $opt = $options[$idx];
                    if (is_array($opt)) {
                        return $opt['en'] ?? $opt['ar'] ?? $opt['label']['en'] ?? $opt['label']['ar'] ?? null;
                    }
                }
            }
        }

        return null;
    }

    public static function getColumns(): array
    {
        $columns = [
            ExportColumn::make('id'),
            ExportColumn::make('competition.title')->label('Program'),
            ExportColumn::make('participant.name')->label('Participant Name'),
            ExportColumn::make('participant.email')->label('Participant Email'),
            ExportColumn::make('has_team')->formatStateUsing(fn ($record) => $record->has_team ? 'Yes' : 'No'),
            ExportColumn::make('team.name')->label('Team Name'),
            ExportColumn::make('team.logo')->label('Team Logo')
                ->formatStateUsing(fn ($record) => $record->team?->logo ? Storage::url($record->team->logo) : ''),

            ExportColumn::make('serial_numbers')
                ->formatStateUsing(fn ($record) => $record->team?->members->pluck('participant.serial_number')->join(', ') ?? ''),

            ExportColumn::make('team.members')
                ->label('Team Members')
                ->formatStateUsing(fn ($record) => $record->team?->members->pluck('participant.name')->join(', ') ?? ''),

            ExportColumn::make('team_members_emails')
                ->label("Team Members' Emails")
                ->getStateUsing(function ($record) {
                    if (!$record->has_team || !$record->team) {
                        return '';
                    }
                    $members = $record->team->members;
                    if ($members->isEmpty()) {
                        return '';
                    }
                    return $members
                        ->map(fn ($member) => $member->participant?->email)
                        ->filter()
                        ->values()
                        ->join(', ');
                }),

            ExportColumn::make('team_leader')
                ->label('Team Leader')
                ->getStateUsing(fn ($record) => $record->team?->members->where('is_leader', true)->first()?->participant->name ?? 'N/A'),

            ExportColumn::make('track')
                ->label('Track')
                ->getStateUsing(function ($record) {
                    if ($record->has_team && $record->team?->track_id) {
                        $track = $record->team->track;
                        $name = $track?->name;
                        return is_array($name) ? ($name['en'] ?? $name['ar'] ?? $track?->slug ?? '—') : ($name ?? '—');
                    }
                    return static::formatFormSubmissionValue($record, 'track', null);
                }),

            ExportColumn::make('sub_track')
                ->label('Sub-Track')
                ->getStateUsing(function ($record) {
                    if ($record->has_team && $record->team?->sub_track_id) {
                        $subTrack = $record->team->subTrack;
                        $name = $subTrack?->name;
                        return is_array($name) ? ($name['en'] ?? $name['ar'] ?? $subTrack?->slug ?? '—') : ($name ?? '—');
                    }
                    return static::formatFormSubmissionValue($record, 'sub_track', null);
                }),

            ExportColumn::make('registered_as')->formatStateUsing(function ($record) {
                $value = $record->registered_as;
                if (is_bool($value)) {
                    $value = $value ? 'team' : 'individual';
                }
                if (empty($value) || !in_array($value, ['team', 'individual'], true)) {
                    $value = $record->has_team ? 'team' : 'individual';
                }
                return ucfirst($value);
            }),
            ExportColumn::make('status')->formatStateUsing(fn ($record) => str($record->status)->ucfirst()),
            ExportColumn::make('created_at')
                ->label('Submitted At')
                ->formatStateUsing(fn ($record) => $record->created_at->format('d/m/Y H:i:s')),
            ExportColumn::make('updated_at')
                ->label('Last Updated')
                ->formatStateUsing(fn ($record) => $record->updated_at->format('d/m/Y H:i:s')),
            ExportColumn::make('total_score')
                ->label('Assessment Total Score')
                ->getStateUsing(function ($record) {
                    if ($record->total_score === null && empty($record->assessment_scores)) {
                        return '';
                    }
                    $maxTotal = 0;
                    $criteria = $record->getAssessmentCriteria();
                    foreach ($criteria as $criterion) {
                        $maxTotal += $criterion->max_score;
                    }
                    return $maxTotal > 0
                        ? ($record->total_score ?? 0) . ' / ' . $maxTotal
                        : (string) ($record->total_score ?? '');
                }),
            ExportColumn::make('assessment_scores_by_criterion')
                ->label('Assessment Scores (by Criterion)')
                ->getStateUsing(function ($record) {
                    if (!$record->hasScoringEnabled() || $record->assessment_scores === null || empty($record->assessment_scores)) {
                        return '';
                    }
                    $criteria = $record->getAssessmentCriteria();
                    $scores = $record->assessment_scores ?? [];
                    $pairs = [];
                    foreach ($criteria as $criterion) {
                        $score = $scores[$criterion->id] ?? 0;
                        $desc = is_array($criterion->description)
                            ? ($criterion->description['en'] ?? $criterion->description['ar'] ?? 'Criterion')
                            : ($criterion->description ?? 'Criterion');
                        $pairs[] = $desc . ': ' . $score . '/' . $criterion->max_score;
                    }
                    return implode('; ', $pairs);
                }),
            ExportColumn::make('ai_evaluation_status')
                ->label('AI Evaluation Status')
                ->getStateUsing(fn ($record) => $record->ai_evaluation_status ?? ''),
            ExportColumn::make('ai_evaluation_score')
                ->label('AI Evaluation Score')
                ->getStateUsing(function ($record) {
                    if (empty($record->ai_evaluation_response)) {
                        return '';
                    }
                    $normalized = $record->ai_evaluation_normalized_score;
                    $total = $record->ai_evaluation_total_score;
                    $maxWeight = $record->ai_evaluation_max_weight ?? 0;
                    if ($normalized !== null && $maxWeight > 0) {
                        return round((float) $normalized, 2) . ' / ' . round((float) $maxWeight, 2);
                    }
                    if ($total !== null) {
                        return $maxWeight > 0
                            ? round((float) $total, 2) . ' / ' . round((float) $maxWeight, 2)
                            : (string) round((float) $total, 2);
                    }
                    return '';
                }),
            ExportColumn::make('ai_evaluation_details')
                ->label('AI Evaluation Details')
                ->getStateUsing(function ($record) {
                    $criteria = $record->ai_evaluation_display_criteria ?? [];
                    if (empty($criteria)) {
                        $msg = data_get($record->ai_evaluation_response, 'message');
                        return is_string($msg) ? $msg : '';
                    }
                    $parts = [];
                    foreach ($criteria as $c) {
                        $name = data_get($c, 'name', data_get($c, 'description', '—'));
                        $score = data_get($c, 'totalScore', 0);
                        $max = data_get($c, 'maxWeight', 0);
                        $parts[] = $max > 0 ? "{$name}: {$score}/{$max}" : "{$name}: {$score}";
                    }
                    return implode('; ', $parts);
                }),
            ExportColumn::make('comments_added')
                ->label('Comments Added')
                ->getStateUsing(function ($record) {
                    $comments = $record->comments()->orderBy('created_at')->get();
                    if ($comments->isEmpty()) {
                        return '';
                    }
                    return $comments->map(function ($c) {
                        $author = ($c->user_id && !$c->author_type)
                            ? ($c->user?->name ?? 'Admin')
                            : ($c->author?->name ?? 'Participant');
                        $date = $c->created_at->format('d/m/Y H:i');
                        $text = str_replace(["\r\n", "\n", "\r"], ' ', $c->comment ?? '');
                        $attachmentPart = static::formatCommentAttachments($c->attachments);
                        return $attachmentPart ? "{$date} | {$author} | {$text} | Attachments: {$attachmentPart}" : "{$date} | {$author} | {$text}";
                    })->implode("\n");
                }),
            ExportColumn::make('comment_attachments')
                ->label('Comment Attachments')
                ->getStateUsing(function ($record) {
                    $allUrls = [];
                    foreach ($record->comments()->orderBy('created_at')->get() as $c) {
                        $urls = static::formatCommentAttachments($c->attachments);
                        if ($urls) {
                            $allUrls[] = $urls;
                        }
                    }
                    return implode("\n", $allUrls);
                }),
        ];

        // Dynamic columns from registration form fields (answers from form_submissions)
        $formFields = static::getFormFieldsForExport();
        $excludedSlugs = ['email', 'participant_name', 'participant_email', 'track', 'sub_track', 'project_name'];
        foreach ($formFields as $field) {
            if (in_array($field->slug, $excludedSlugs, true)) {
                continue;
            }
            $slug = $field->slug;
            $label = static::getFormFieldLabel($field);
            $columns[] = ExportColumn::make('form_submissions_' . $slug)
                ->label($label)
                ->getStateUsing(fn ($record) => static::formatFormSubmissionValue($record, $slug, $field));
        }

        return $columns;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your program application export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }
        return $body;
    }
}
