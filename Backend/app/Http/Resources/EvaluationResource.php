<?php

namespace App\Http\Resources;

use App\Models\FormEvaluationScore;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvaluationResource extends JsonResource
{
    public function toArray($request)
    {
        $form = $this->first()?->form;
        $stage = $this->first()?->stage;
        $judgeProject = $this->first()?->judgeProject;

        // Get raw evaluation_config to preserve ar/en labels
        // We need to bypass the accessor that converts labels to single value
        $rawConfig = [];
        if ($form) {
            // Get raw JSON string directly from database, bypassing casts and accessors
            $rawJson = DB::table('forms')
                ->where('id', $form->id)
                ->value('evaluation_config');

            if ($rawJson) {
                $rawConfig = is_string($rawJson) ? json_decode($rawJson, true) : $rawJson;
            }
        }
        $criteriaConfig = collect($rawConfig['evaluation_criteria'] ?? []);
        $total = FormEvaluationScore::where('form_id',$form?->id)
            ->where('judge_project_id',$judgeProject?->id)
            ->where('is_archived', false)
            ->first();

        $evolution = $this->map(function ($evaluation) use ($criteriaConfig, $rawConfig) {
            $criterion = $criteriaConfig->firstWhere('slug', $evaluation->question);
            $weight = $evaluation->weight ?? 0;
            
            // Get subcriteria from raw config to ensure we have ar/en labels
            $subcriteria = [];
            if ($criterion && isset($criterion['subcriteria'])) {
                $subcriteria = $criterion['subcriteria'];
            } else {
                // Try to find criterion in raw config and get subcriteria
                foreach ($rawConfig['evaluation_criteria'] ?? [] as $rawCriterion) {
                    if (isset($rawCriterion['slug']) && $rawCriterion['slug'] === $evaluation->question) {
                        $subcriteria = $rawCriterion['subcriteria'] ?? [];
                        break;
                    }
                }
            }

            return [
                'id' => (string) $evaluation->id,
                'question' => [
                    'key' => $evaluation->question,
                    'title' => $this->getTranslatedLabel($criterion['label'] ?? null) ?? ucwords(str_replace('_', ' ', $evaluation->question)),
                    'value' => is_numeric($evaluation->answer) ? number_format((float) $evaluation->answer, 2, '.', '') : $evaluation->answer,
                    'comment' => $evaluation->comment,
                    'weight' => $weight,
                ],
                'subquestions' => $this->isJson($evaluation->details)
                    ? $this->formatSubquestions(json_decode($evaluation->details, true), $subcriteria, $weight, $evaluation->question)
                    : [],
            ];
        });

        return [
            'form_id' => $form?->id,
            'stage' => [
                'id' => $stage?->id,
                'title' => $stage?->title,
                'evolution' => $evolution,
            ],
            'final_comment' => $judgeProject?->final_comment,
            'total' => $total ? number_format((float) $total->evaluation_score, 2, '.', '') : '0.00',
            'created_at' => $this->first()?->created_at,
            'updated_at' => $this->first()?->updated_at,
        ];
    }

    protected function formatSubquestions(array $subquestions, array $subcriteriaConfig = [], int $parentWeight = 0, string $parentQuestionSlug = ''): array
    {
        $grouped = [];

        foreach ($subquestions as $key => $value) {
            $isComment = Str::endsWith($key, '_comment');
            $fullSlug = $isComment ? Str::beforeLast($key, '_comment') : $key;

            // Remove parent question slug prefix if it exists
            // e.g., "product_value_proposition" -> "value_proposition"
            $slug = $fullSlug;
            if (!empty($parentQuestionSlug) && Str::startsWith($fullSlug, $parentQuestionSlug . '_')) {
                $slug = Str::after($fullSlug, $parentQuestionSlug . '_');
            }

            if (!isset($grouped[$slug])) {
                // Try to find subcriterion by slug
                $subcriterion = collect($subcriteriaConfig)->firstWhere('slug', $slug);
                
                // If not found, try searching without the prefix removal (in case slug doesn't have prefix)
                if (!$subcriterion) {
                    $subcriterion = collect($subcriteriaConfig)->firstWhere('slug', $fullSlug);
                }
                
                // If still not found, try to find by matching the end of the slug
                if (!$subcriterion && !empty($subcriteriaConfig)) {
                    foreach ($subcriteriaConfig as $sub) {
                        $subSlug = $sub['slug'] ?? '';
                        // Check if slug matches or if fullSlug ends with subSlug
                        if ($subSlug && ($subSlug === $slug || $subSlug === $fullSlug || Str::endsWith($fullSlug, '_' . $subSlug))) {
                            $subcriterion = $sub;
                            break;
                        }
                    }
                }

                // Get title with both ar and en
                $title = [
                    'ar' => null,
                    'en' => null,
                ];

                if ($subcriterion && isset($subcriterion['label'])) {
                    $label = $subcriterion['label'];

                    if (is_array($label)) {
                        // Check if it's an array with ar/en keys
                        if (isset($label['ar']) || isset($label['en'])) {
                            $title['ar'] = $label['ar'] ?? $label['en'] ?? null;
                            $title['en'] = $label['en'] ?? $label['ar'] ?? null;
                        } else {
                            // If it's a numeric array, use first value for both
                            $firstValue = reset($label);
                            if ($firstValue !== false) {
                                $title['ar'] = $firstValue;
                                $title['en'] = $firstValue;
                            }
                        }
                    } elseif (is_string($label) && !empty($label)) {
                        // If it's a single string, use it for both languages
                        $title['ar'] = $label;
                        $title['en'] = $label;
                    }
                }

                // Fallback to slug-based title if no label found
                if (empty($title['ar']) && empty($title['en'])) {
                    $fallbackTitle = Str::of($slug)->replace('_', ' ')->title()->toString();
                    $title['ar'] = $fallbackTitle;
                    $title['en'] = $fallbackTitle;
                } else {
                    // Ensure both ar and en have values
                    if (empty($title['ar']) && !empty($title['en'])) {
                        $title['ar'] = $title['en'];
                    }
                    if (empty($title['en']) && !empty($title['ar'])) {
                        $title['en'] = $title['ar'];
                    }
                }

                $subWeight = $subcriterion['weight'] ?? 0;
                $isIncluded = $parentWeight > 0 && $subWeight > 0;

                $grouped[$slug] = [
                    'key' => $slug,
                    'title' => $title,
                    'value' => null,
                    'comment' => null,
                    'weight' => $subWeight,
                    'is_included' => $isIncluded,
                    'not_included_label' => $isIncluded ? null : 'Not Included',
                ];
            }

            if ($isComment) {
                $grouped[$slug]['comment'] = $value;
            } else {
                $grouped[$slug]['value'] = is_numeric($value) ? number_format($value, 2, '.', '') : $value;
            }
        }

        return array_values($grouped);
    }

    protected function isJson($string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get translated label from array or return string as is
     */
    protected function getTranslatedLabel($label): ?string
    {
        if (is_array($label)) {
            // Get current language from Accept-Language header
            $currentLang = request()->header('Accept-Language', 'en');
            $currentLang = in_array($currentLang, ['ar', 'en']) ? $currentLang : 'en';
            return $label[$currentLang] ?? $label['en'] ?? $label['ar'] ?? null;
        }
        return is_string($label) ? $label : null;
    }
}

