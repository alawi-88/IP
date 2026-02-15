<?php

namespace App\Filament\Exports;

use App\Models\FormEvaluationScore;
use App\Models\ProjectEvaluation;
use App\Models\SubTrack;
use App\Models\Track;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvaluationExporter extends Exporter
{
    protected static ?string $model = FormEvaluationScore::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('Submission ID'),

            ExportColumn::make('judgeProject.project')
                ->label('Project')
                ->getStateUsing(fn ($record) => $record->judgeProject?->project?->form_submissions['project_name'] ?? 'N/A'),

            ExportColumn::make('form.name')->label('Form')->default('N/A'),

            ExportColumn::make('judgeProject.judge.name')->label('Judge')->default('N/A'),

            ExportColumn::make('judgeProject.judge.email')->label('Judge Email')->default('N/A'),

            ExportColumn::make('track')
                ->label('Track')
                ->getStateUsing(fn ($record) => Track::find($record->judgeProject?->project?->form_submissions['track'] ?? null)?->name ?? 'N/A'),

            ExportColumn::make('sub_track')
                ->label('Sub Track')
                ->getStateUsing(fn ($record) => SubTrack::find($record->judgeProject?->project?->form_submissions['sub_track'] ?? null)?->name ?? 'N/A'),

            ExportColumn::make('evaluation_score')
                ->label('Evaluation Score')
                ->formatStateUsing(fn ($record) => is_null($record->evaluation_score) ? 'N/A' : number_format($record->evaluation_score, 2) . '%'),

            ExportColumn::make('exclude_from_calculation')
                ->label('Included in Average')
                ->formatStateUsing(fn ($state) => $state ? 'No' : 'Yes'),

            ExportColumn::make('evaluation_details')
                ->label('Evaluation Details')
                ->getStateUsing(function ($record) {
                    $evaluations = ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                        ->where('form_id', $record->form_id)
                        ->where('is_archived', false)
                        ->get();

                    if ($evaluations->isEmpty()) {
                        return 'N/A';
                    }

                    return $evaluations->map(function ($eval) {
                        $entry = "{$eval->question}: {$eval->answer}";
                        return !empty($eval->comment) ? "{$entry} ({$eval->comment})" : $entry;
                    })->implode(' | ');
                }),

            ExportColumn::make('has_comments')
                ->label('Has Comments')
                ->getStateUsing(fn ($record) => ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
                    ->where('form_id', $record->form_id)
                    ->whereHas('notes')
                    ->exists() ? 'Yes' : 'No'),

            ExportColumn::make('is_archived')
                ->label('Archived')
                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),

            ExportColumn::make('created_at')
                ->label('Submitted At')
                ->formatStateUsing(fn ($record) => $record->created_at?->format('d/m/Y H:i:s')),

            ExportColumn::make('updated_at')
                ->label('Last Updated')
                ->formatStateUsing(fn ($record) => $record->updated_at?->format('d/m/Y H:i:s')),
        ];
    }

    /**
     * Format evaluation details including main criteria and sub-criteria from details JSON.
     */
    protected static function formatEvaluationDetails(FormEvaluationScore $record): string
    {
        $evaluations = ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
            ->where('form_id', $record->form_id)
            ->where('is_archived', false)
            ->orderBy('question')
            ->get();

        if ($evaluations->isEmpty()) {
            return 'N/A';
        }

        $rawConfig = static::getFormEvaluationConfig($record->form_id);
        $criteriaConfig = collect($rawConfig['evaluation_criteria'] ?? []);

        $parts = [];
        foreach ($evaluations as $eval) {
            $mainLabel = static::getCriterionLabel($eval->question, $criteriaConfig);
            $mainEntry = "{$mainLabel}: {$eval->answer}";
            if (!empty($eval->comment)) {
                $mainEntry .= ' (' . static::normalizeComment($eval->comment) . ')';
            }
            $parts[] = $mainEntry;

            $details = $eval->details;
            if (is_string($details)) {
                $details = json_decode($details, true);
            }
            if (is_array($details) && !empty($details)) {
                $criterion = $criteriaConfig->firstWhere('slug', $eval->question);
                $subcriteriaConfig = $criterion['subcriteria'] ?? [];
                $subParts = static::formatSubCriteriaForExport($details, $subcriteriaConfig, $eval->question);
                if (!empty($subParts)) {
                    $parts[] = implode(' | ', $subParts);
                }
            }
        }

        return implode(' | ', $parts);
    }

    /**
     * Format sub-criteria from details JSON for export.
     */
    protected static function formatSubCriteriaForExport(array $details, array $subcriteriaConfig, string $parentSlug): array
    {
        $grouped = [];
        foreach ($details as $key => $value) {
            $isComment = Str::endsWith($key, '_comment');
            $fullSlug = $isComment ? Str::beforeLast($key, '_comment') : $key;
            $slug = $fullSlug;
            if (!empty($parentSlug) && Str::startsWith($fullSlug, $parentSlug . '_')) {
                $slug = Str::after($fullSlug, $parentSlug . '_');
            }

            if (!isset($grouped[$slug])) {
                $grouped[$slug] = ['value' => null, 'comment' => null];
            }
            if ($isComment) {
                $grouped[$slug]['comment'] = $value;
            } else {
                $grouped[$slug]['value'] = is_numeric($value) ? number_format((float) $value, 2) : $value;
            }
        }

        $subParts = [];
        foreach ($grouped as $slug => $data) {
            $label = static::getSubCriterionLabel($slug, $subcriteriaConfig);
            $entry = "{$label}: " . ($data['value'] ?? '—');
            if (!empty($data['comment'])) {
                $entry .= ' (' . static::normalizeComment($data['comment']) . ')';
            }
            $subParts[] = $entry;
        }
        return $subParts;
    }

    /**
     * Get form evaluation_config as array (raw from DB to preserve labels).
     */
    protected static function getFormEvaluationConfig(?int $formId): array
    {
        if (!$formId) {
            return [];
        }
        $raw = DB::table('forms')->where('id', $formId)->value('evaluation_config');
        if (!$raw) {
            return [];
        }
        return is_string($raw) ? json_decode($raw, true) : $raw;
    }

    protected static function getCriterionLabel(string $slug, $criteriaConfig): string
    {
        $criterion = $criteriaConfig->firstWhere('slug', $slug);
        return static::getLabelFromCriterion($criterion) ?: Str::of($slug)->replace('_', ' ')->title()->toString();
    }

    protected static function getSubCriterionLabel(string $slug, array $subcriteriaConfig): string
    {
        $sub = collect($subcriteriaConfig)->firstWhere('slug', $slug);
        return static::getLabelFromCriterion($sub) ?: Str::of($slug)->replace('_', ' ')->title()->toString();
    }

    protected static function getLabelFromCriterion(?array $criterion): ?string
    {
        if (!$criterion || empty($criterion['label'])) {
            return null;
        }
        $label = $criterion['label'];
        if (is_array($label)) {
            return $label['en'] ?? $label['ar'] ?? reset($label);
        }
        return (string) $label;
    }

    /**
     * Collect all judge comments for this evaluation (final comment + main criteria comments + sub-criteria comments).
     */
    protected static function formatAllJudgeComments(FormEvaluationScore $record): string
    {
        $lines = [];

        $judgeProject = $record->judgeProject;
        if ($judgeProject && !empty(trim((string) $judgeProject->final_comment))) {
            $lines[] = 'Final: ' . static::normalizeComment($judgeProject->final_comment);
        }

        $evaluations = ProjectEvaluation::where('judge_project_id', $record->judge_project_id)
            ->where('form_id', $record->form_id)
            ->where('is_archived', false)
            ->orderBy('question')
            ->get();

        $rawConfig = static::getFormEvaluationConfig($record->form_id);
        $criteriaConfig = collect($rawConfig['evaluation_criteria'] ?? []);

        foreach ($evaluations as $eval) {
            if (!empty(trim((string) $eval->comment))) {
                $mainLabel = static::getCriterionLabel($eval->question, $criteriaConfig);
                $lines[] = "{$mainLabel}: " . static::normalizeComment($eval->comment);
            }
            $details = $eval->details;
            if (is_string($details)) {
                $details = json_decode($details, true);
            }
            if (is_array($details)) {
                $criterion = $criteriaConfig->firstWhere('slug', $eval->question);
                $subcriteriaConfig = $criterion['subcriteria'] ?? [];
                foreach ($details as $key => $value) {
                    if (Str::endsWith($key, '_comment') && !empty(trim((string) $value))) {
                        $slug = Str::beforeLast($key, '_comment');
                        if (!empty($eval->question) && Str::startsWith($slug, $eval->question . '_')) {
                            $slug = Str::after($slug, $eval->question . '_');
                        }
                        $subLabel = static::getSubCriterionLabel($slug, $subcriteriaConfig);
                        $lines[] = "{$subLabel}: " . static::normalizeComment($value);
                    }
                }
            }
        }

        return $lines === [] ? '—' : implode(' | ', $lines);
    }

    protected static function normalizeComment(?string $comment): string
    {
        if ($comment === null || $comment === '') {
            return '';
        }
        return str_replace(["\r\n", "\n", "\r"], ' ', trim($comment));
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your evaluations export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
