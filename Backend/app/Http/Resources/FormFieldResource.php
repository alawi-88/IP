<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormFieldResource extends JsonResource
{
    protected array $answers;
    protected ?array $aiEnhancementConfig = null;
    protected ?string $suggestedValue = null;

    public function __construct($resource, array $answers = [], ?array $aiEnhancementConfig = null, ?string $suggestedValue = null)
    {
        parent::__construct($resource);
        $this->answers = $answers;
        $this->aiEnhancementConfig = $aiEnhancementConfig;
        $this->suggestedValue = $suggestedValue;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rawValue = $this->slug ? ($this->answers[$this->slug] ?? null) : null;
        $formattedValue = $this->slug
            ? $this->formatValue($this->slug, $rawValue)
            : null;

        $hasValidRules = $this->hasValidConditionalLogicRules();
        $conditionalLogic = $this->conditional_logic;

        $optionBasedTypes = ['dropdown', 'radio', 'rating', 'checkbox', 'multi_select'];
        $useValueId = in_array($this->type, $optionBasedTypes);

        $base = [
            'id' => $this->id,
            'slug' => $this->slug,
            'label' => $this->label,
        ];
        if ($useValueId) {
            $base['value_id'] = $this->resolveValueToOptionIds($rawValue);
        } else {
            $base['value'] = $formattedValue;
        }
        $base['type'] = $this->type;
        $base['required'] = $this->required;
        $base['placeholder'] = ! empty($this->placeholder) ? $this->placeholder : null;
        $base['hint'] = $this->hint;
        $base['validation_rules'] = $this->validation_rules;
        $base['conditional_logic'] = $hasValidRules ? $conditionalLogic : false;
        $base['conditional_logic_rules'] = $hasValidRules && $conditionalLogic
            ? $this->formatConditionalLogicRules()
            : null;
        $base['options'] = $this->formatOptions();
        $base['mandatory_options'] = $this->type === 'checkbox' ? $this->formatMandatoryOptions() : null;
        $base['sort'] = $this->sort;
        $base['created_at'] = $this->created_at;
        $base['updated_at'] = $this->updated_at;

        return $base;
    }

    /**
     * Resolve raw answer value to option id(s) for dropdown, radio, rating, checkbox, multi_select.
     * Returns option id (int) or array of int for multi_select/checkbox. Always int type(s).
     */
    protected function resolveValueToOptionIds($rawValue): int|array|null
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }
        $options = $this->getOptionsWithIds();
        if (empty($options)) {
            return null;
        }
        $isMulti = in_array($this->type, ['checkbox', 'multi_select'], true);
        $values = $isMulti ? $this->normalizeMultiValue($rawValue) : [$rawValue];
        $ids = [];
        foreach ($values as $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $id = $this->findOptionIdInOptions($options, $v);
            if ($id !== null) {
                $ids[] = $id;
            }
        }
        if (empty($ids)) {
            return null;
        }
        $ids = array_map('intval', $ids);
        if ($isMulti) {
            return count($ids) === 1 ? $ids[0] : $ids;
        }
        return $ids[0];
    }

    /**
     * Get options as array of [id, value, label, en?, ar?] for resolving value_id.
     * Handles DB format with id, en/ar arrays, and en/ar strings.
     */
    protected function getOptionsWithIds(): array
    {
        if (! $this->options) {
            return [];
        }
        $raw = $this->options;
        $currentLang = request()->header('Accept-Language', 'en');
        $currentLang = in_array($currentLang, ['ar', 'en']) ? $currentLang : 'en';

        if (! is_array($raw)) {
            if (is_string($raw)) {
                $parsed = \App\Models\FormField::parseOptionsString($raw);
                $out = [];
                foreach ($parsed as $i => $opt) {
                    if ($opt !== '') {
                        $out[] = ['id' => $i + 1, 'value' => $opt, 'label' => $opt];
                    }
                }
                return $out;
            }
            return [];
        }

        // Direct array of options with id (e.g. [{"id":1,"value":"ا","label":"ا"}, ...])
        if (isset($raw[0]) && is_array($raw[0]) && array_key_exists('id', $raw[0])) {
            return $raw;
        }

        // en/ar as arrays
        if (isset($raw['en']) && is_array($raw['en']) && isset($raw['ar']) && is_array($raw['ar'])) {
            $en = $raw['en'];
            $ar = $raw['ar'];
            $out = [];
            $maxLen = max(count($en), count($ar));
            for ($i = 0; $i < $maxLen; $i++) {
                $enVal = $en[$i] ?? '';
                $arVal = $ar[$i] ?? '';
                if ($enVal !== '' || $arVal !== '') {
                    $val = $currentLang === 'ar' ? $arVal : $enVal;
                    $out[] = [
                        'id' => $i + 1,
                        'value' => $val,
                        'label' => $val,
                        'en' => $enVal,
                        'ar' => $arVal,
                    ];
                }
            }
            return $out;
        }

        // en/ar as strings — build with BOTH languages so "aa" matches when current is Arabic
        if (isset($raw['en']) && is_string($raw['en']) && isset($raw['ar']) && is_string($raw['ar'])) {
            $enOptions = \App\Models\FormField::parseOptionsString($raw['en'] ?? '');
            $arOptions = \App\Models\FormField::parseOptionsString($raw['ar'] ?? '');
            $maxLen = max(count($enOptions), count($arOptions));
            $out = [];
            for ($i = 0; $i < $maxLen; $i++) {
                $enVal = trim($enOptions[$i] ?? '');
                $arVal = trim($arOptions[$i] ?? '');
                if ($enVal !== '' || $arVal !== '') {
                    $val = $currentLang === 'ar' ? $arVal : $enVal;
                    $out[] = [
                        'id' => $i + 1,
                        'value' => $val,
                        'label' => $val,
                        'en' => $enVal,
                        'ar' => $arVal,
                    ];
                }
            }
            return $out;
        }

        // Single array of strings (e.g. ["ا", "ب", "ج"])
        if (isset($raw[0]) && (is_string($raw[0]) || is_numeric($raw[0]))) {
            $out = [];
            foreach ($raw as $i => $opt) {
                $s = trim((string) $opt);
                if ($s !== '') {
                    $out[] = ['id' => $i + 1, 'value' => $s, 'label' => $s];
                }
            }
            return $out;
        }

        return [];
    }

    /**
     * @param array<int, array{id?: int, value?: string, label?: string, en?: string, ar?: string}> $options
     */
    protected function findOptionIdInOptions(array $options, mixed $value): ?int
    {
        $search = is_string($value) ? trim($value) : $value;
        $searchStr = (string) $search;
        foreach ($options as $index => $opt) {
            if (! is_array($opt)) {
                continue;
            }
            $optId = isset($opt['id']) ? (int) $opt['id'] : ($index + 1);
            $optValue = isset($opt['value']) ? trim((string) $opt['value']) : null;
            $optLabel = isset($opt['label']) ? trim((string) $opt['label']) : null;
            $optEn = isset($opt['en']) ? trim((string) $opt['en']) : null;
            $optAr = isset($opt['ar']) ? trim((string) $opt['ar']) : null;

            if (is_numeric($search)) {
                $num = (int) $search;
                if ($optId === $num) {
                    return $optId;
                }
                if ($optValue !== null && (int) $optValue === $num) {
                    return $optId;
                }
            }
            $match = function (?string $s) use ($searchStr) {
                return $s !== null && $s !== '' && (strcasecmp($s, $searchStr) === 0 || $s === $searchStr);
            };
            if ($match($optValue) || $match($optLabel) || $match($optEn) || $match($optAr)) {
                return $optId;
            }
        }
        return null;
    }

    /**
     * @return array<int, mixed>
     */
    protected function normalizeMultiValue(mixed $rawValue): array
    {
        if (is_array($rawValue)) {
            return array_values($rawValue);
        }
        if (is_string($rawValue) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($rawValue))) {
            return array_map('trim', explode(',', $rawValue));
        }
        if (is_string($rawValue) && str_contains($rawValue, ',')) {
            return array_map('trim', explode(',', $rawValue));
        }
        return $rawValue !== null && $rawValue !== '' ? [$rawValue] : [];
    }

    protected function formatValue(string $slug, $value)
    {
        if ($this->type === 'file' && $value) {
            return asset('storage/' . $value);
        }

        // Handle team_serial field specifically
        if ($slug === 'team_serial' && is_array($value)) {
            return implode(',', array_filter($value));
        }

        // Handle team_logo field specifically - return null for empty arrays
        if ($slug === 'team_logo') {
            if (is_array($value) && empty($value)) {
                return null;
            }
            if (is_array($value) && !empty($value[0])) {
                return asset('storage/' . $value[0]);
            }
            if (is_string($value) && !empty($value)) {
                return asset('storage/' . $value);
            }
            return null;
        }

        // Handle track field specifically
        if ($slug === 'track' && $value) {
            return optional(\App\Models\Track::find($value))->name ?? $value;
        }

        // Handle sub_track field specifically
        if ($slug === 'sub_track' && $value) {
            return optional(\App\Models\SubTrack::find($value))->name ?? $value;
        }

        // Handle multi-select and checkbox fields
        // if (in_array($this->type, ['multi_select', 'checkbox'])) {
        //     if (is_array($value)) {
        //         // Filter out empty values and return comma-separated string
        //         $filteredValues = array_filter($value, function($v) {
        //             return !empty($v) && $v !== '';
        //         });
        //         return empty($filteredValues) ? null : implode(',', $filteredValues);
        //     }
        //     if (is_string($value) && !empty($value)) {
        //         return $value; // Return string as is
        //     }
        //     return null; // Return null if no value
        // }

        // Handle dropdown, radio, rating, multi-select, and checkbox fields
        // Check if request is from judges API - if so, return labels instead of IDs
        $isJudgesRequest = request()->is('api/judges/*') || request()->is('judges/*');

        if (in_array($this->type, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'])) {
            // Normalize value to ensure consistent format
            if ($value === null || $value === '') {
                return null;
            }

            // For judges API, return labels instead of IDs
            if ($isJudgesRequest) {
                return $this->formatValueAsLabels($value);
            }

            // For participants API, return numeric IDs for form state
            $isArrayValue = is_array($value);
            $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

            // For checkbox and multi_select, return as comma-separated string with English comma (,)
            if (in_array($this->type, ['checkbox', 'multi_select'])) {
                if ($isArrayValue) {
                    // Filter and convert to strings, then join with English comma
                    $filteredValues = array_filter($value, function($v) {
                        return $v !== null && $v !== '';
                    });
                    $stringValues = array_map(function($v) {
                        return (string)$v;
                    }, $filteredValues);
                    // Return comma-separated string with English comma (,)
                    return implode(',', $stringValues);
                } elseif ($isCommaSeparatedString) {
                    // Return comma-separated string as-is (already has English commas)
                    return $value;
                } else {
                    // Single value - return as string
                    return (string)$value;
                }
            } else {
                // For dropdown, radio, rating (single selection), return as string
                if ($isArrayValue) {
                    // If somehow an array, take first element
                    $firstValue = reset($value);
                    return $firstValue !== null ? (string)$firstValue : null;
                } elseif ($isCommaSeparatedString) {
                    // If comma-separated, take first value
                    $values = array_map('trim', explode(',', $value));
                    return !empty($values) ? (string)$values[0] : null;
                } else {
                    // Single value - return as string
                    return (string)$value;
                }
            }
        }


        return $value;
    }

    /**
     * Format value as labels for judges API (convert numeric IDs to option labels)
     */
    protected function formatValueAsLabels($value)
    {
        if (!$this->options) {
            return $value;
        }

        // Get current language from Accept-Language header or app locale
        $currentLang = request()->header('Accept-Language', app()->getLocale());
        $currentLang = in_array($currentLang, ['ar', 'en']) ? $currentLang : 'en';

        // Process options to handle both string and array formats
        $processedOptions = [];
        if (isset($this->options['en']) && isset($this->options['ar']) &&
            is_string($this->options['en']) && is_string($this->options['ar'])) {
            // Convert string format to array
            $enOptions = \App\Models\FormField::parseOptionsString($this->options['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($this->options['ar']);
            $maxLength = max(count($enOptions), count($arOptions));

            for ($i = 0; $i < $maxLength; $i++) {
                $processedOptions[] = [
                    'en' => $enOptions[$i] ?? '',
                    'ar' => $arOptions[$i] ?? ''
                ];
            }
        } elseif (is_array($this->options)) {
            // Handle array format - could be ['en' => [...], 'ar' => [...]] or direct array
            if (isset($this->options['en']) && is_array($this->options['en']) &&
                isset($this->options['ar']) && is_array($this->options['ar'])) {
                $maxLength = max(count($this->options['en']), count($this->options['ar']));
                for ($i = 0; $i < $maxLength; $i++) {
                    $processedOptions[] = [
                        'en' => $this->options['en'][$i] ?? '',
                        'ar' => $this->options['ar'][$i] ?? ''
                    ];
                }
            } else {
                // Direct array format
                $processedOptions = $this->options;
            }
        }

        // Handle array values (for checkbox and multi_select)
        $isArrayValue = is_array($value);
        $isCommaSeparatedString = is_string($value) && preg_match('/^\d+(\s*,\s*\d+)*$/', trim($value));

        if ($isArrayValue || $isCommaSeparatedString) {
            // Convert string to array if needed
            $arrayValue = $isArrayValue ? $value : array_map('trim', explode(',', $value));
            $labels = [];

            foreach ($arrayValue as $val) {
                // Skip empty values
                if ($val === null || $val === '') {
                    continue;
                }

                // Handle numeric values (index-based, 1-indexed)
                if (is_numeric($val)) {
                    $index = (int)$val - 1; // Convert to 0-based index
                    if (isset($processedOptions[$index])) {
                        $option = $processedOptions[$index];
                        if (is_array($option)) {
                            // Return the appropriate language value
                            $label = $currentLang === 'ar' ? ($option['ar'] ?? $option['en'] ?? '') : ($option['en'] ?? $option['ar'] ?? '');
                            if ($label) {
                                $labels[] = $label;
                            }
                        } elseif (is_string($option)) {
                            $labels[] = $option;
                        }
                    }
                } else {
                    // Non-numeric value - use as-is
                    $labels[] = $val;
                }
            }

            // Return comma-separated string of labels
            return implode(', ', $labels);
        } else {
            // Handle single numeric value (for dropdown, radio, rating)
            if (is_numeric($value)) {
                $index = (int)$value - 1; // Convert to 0-based index
                if (isset($processedOptions[$index])) {
                    $option = $processedOptions[$index];
                    if (is_array($option)) {
                        // Return the appropriate language value
                        $label = $currentLang === 'ar' ? ($option['ar'] ?? $option['en'] ?? '') : ($option['en'] ?? $option['ar'] ?? '');
                        return $label ?: $value;
                    } elseif (is_string($option)) {
                        return $option;
                    }
                }
                // If index not found, return value as-is
                return (string)$value;
            } else {
                // Non-numeric value - return as-is
                return $value !== null ? (string)$value : null;
            }
        }
    }

    /**
     * Format options as direct array of objects with localized values
     */
    protected function formatOptions()
    {
        if (!$this->options) {
            return null;
        }

        // Get current language from Accept-Language header
        $currentLang = request()->header('Accept-Language', 'en');
        $currentLang = in_array($currentLang, ['ar', 'en']) ? $currentLang : 'en';

        // If options is already an array (from database)
        if (is_array($this->options)) {
            // Check if it's already in the new format (with ar/en arrays)
            if (isset($this->options['ar']) && is_array($this->options['ar'])) {
                // Return only the requested language
                return $this->options[$currentLang] ?? $this->options['en'] ?? [];
            }

            // If it's in the old format (ar/en as strings), convert it
            if (isset($this->options['ar']) && is_string($this->options['ar'])) {
                $formattedOptions = [];

                // Parse options for the requested language
                $langOptions = \App\Models\FormField::parseOptionsString($this->options[$currentLang] ?? $this->options['en'] ?? '');
                foreach ($langOptions as $index => $option) {
                    if (!empty($option)) {
                        $formattedOptions[] = [
                            'id' => $index + 1,
                            'value' => $option,
                            'label' => $option
                        ];
                    }
                }

                return $formattedOptions;
            }

            return $this->options;
        }

        // If options is a string, parse it
        if (is_string($this->options)) {
            $options = \App\Models\FormField::parseOptionsString($this->options);
            $formattedOptions = [];

            foreach ($options as $index => $option) {
                if (!empty($option)) {
                    $formattedOptions[] = [
                        'id' => $index + 1,
                        'value' => $option,
                        'label' => $option
                    ];
                }
            }

            return $formattedOptions;
        }

        return null;
    }

    /**
     * Format mandatory options to include in the API response.
     * Returns an array with mandatory option indices and labels.
     *
     * @return array|null
     */
    protected function formatMandatoryOptions()
    {
        if ($this->type !== 'checkbox' || empty($this->mandatory_options)) {
            return null;
        }

        // Get current language from Accept-Language header
        $currentLang = request()->header('Accept-Language', 'en');
        $currentLang = in_array($currentLang, ['ar', 'en']) ? $currentLang : 'en';

        $mandatoryIndices = array_map('intval', $this->mandatory_options);

        if (empty($mandatoryIndices)) {
            return null;
        }

        // Get option labels for the mandatory options
        $mandatoryOptions = [];
        $processedOptions = $this->resource->processed_options;

        foreach ($mandatoryIndices as $index) {
            $arrayIndex = $index - 1; // Convert to 0-based index
            if (isset($processedOptions[$arrayIndex])) {
                $option = $processedOptions[$arrayIndex];
                $label = is_array($option)
                    ? ($option[$currentLang] ?? $option['en'] ?? $option['ar'] ?? '')
                    : $option;

                $mandatoryOptions[] = [
                    'id' => $index,
                    'label' => $label,
                ];
            }
        }

        return [
            'indices' => $mandatoryIndices,
            'options' => $mandatoryOptions,
        ];
    }

    /**
     * Format conditional logic rules to work with option objects
     */
    protected function formatConditionalLogicRules()
    {
        $rules = $this->conditional_logic_rules;
        if (!$rules) {
            return null;
        }

        // Get current language from Accept-Language header
        $currentLang = request()->header('Accept-Language', 'en');
        $currentLang = in_array($currentLang, ['ar', 'en']) ? $currentLang : 'en';

        return collect($rules)->map(function ($rule) use ($currentLang) {
            if (isset($rule['values'])) {
                $rule['values'] = collect($rule['values'])->map(function ($val) use ($currentLang, $rule) {
                    // Get the source field (the field being checked) - note: field_id is the source field, not target
                    $sourceFieldId = $rule['field_id'] ?? null;
                    $sourceFieldType = $this->getTargetFieldType($sourceFieldId);

                    // Handle the format from FormField model accessor: ['value' => 'english,arabic']
                    if (is_array($val) && isset($val['value'])) {
                        $value = $val['value'];

                        // If value contains comma, it's in "english,arabic" format
                        if (strpos($value, ',') !== false) {
                            $parts = explode(',', $value, 2);
                            $enValue = trim($parts[0]);
                            $arValue = trim($parts[1]);

                            // Return the appropriate language value
                            $selectedValue = $currentLang === 'ar' ? $arValue : $enValue;

                            // For dropdown/radio/rating/multi_select/checkbox fields, return the option index
                            if (in_array($sourceFieldType, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'])) {
                                $optionIndex = $this->findOptionIndexByValueForTargetField($enValue, $arValue, $sourceFieldId);

                                // For radio and rating fields, we MUST return an index, not the text value
                                if (in_array($sourceFieldType, ['radio', 'rating'])) {
                                    if ($optionIndex !== null) {
                                        return [
                                            'value' => (string)($optionIndex + 1)
                                        ];
                                    }
                                    // If not found by en/ar, try to find by single value
                                    $optionIndex = $this->findOptionIndexBySingleValueForTargetField($enValue, $sourceFieldId);
                                    if ($optionIndex !== null) {
                                        return [
                                            'value' => (string)($optionIndex + 1)
                                        ];
                                    }
                                    // Try case-insensitive search
                                    $optionIndex = $this->findOptionIndexBySingleValueForTargetFieldCaseInsensitive($enValue, $sourceFieldId);
                                    if ($optionIndex !== null) {
                                        return [
                                            'value' => (string)($optionIndex + 1)
                                        ];
                                    }
                                    // Last resort: return the English value as-is
                                    return [
                                        'value' => $enValue
                                    ];
                                }

                                // Return as string to ensure consistent type comparison
                                return [
                                    'value' => $optionIndex !== null ? (string)($optionIndex + 1) : $selectedValue
                                ];
                            }

                            // For other field types, return the text value
                            return [
                                'value' => $selectedValue
                            ];
                        }

                        // If value doesn't contain comma, check if it's an option value
                        if (in_array($sourceFieldType, ['dropdown', 'radio', 'rating'])) {
                            // First check if value is already a numeric index (1, 2, 3, etc.)
                            if (is_numeric($value)) {
                                $numericValue = (int)$value;
                                // Validate that the index is within valid range
                                if ($this->isValidOptionIndex($numericValue, $sourceFieldId)) {
                                    return [
                                        'value' => (string)$numericValue
                                    ];
                                }
                            }

                            // If not a valid index, try to find by text value
                            $optionIndex = $this->findOptionIndexBySingleValueForTargetField($value, $sourceFieldId);

                            // For radio and rating fields, we MUST return an index, not the text value
                            if (in_array($sourceFieldType, ['radio', 'rating'])) {
                                if ($optionIndex !== null) {
                                    return [
                                        'value' => (string)($optionIndex + 1)
                                    ];
                                }
                                // Try case-insensitive search as fallback
                                $optionIndex = $this->findOptionIndexBySingleValueForTargetFieldCaseInsensitive($value, $sourceFieldId);
                                if ($optionIndex !== null) {
                                    return [
                                        'value' => (string)($optionIndex + 1)
                                    ];
                                }
                                // Last resort: return the value as-is (shouldn't happen in normal cases)
                                return [
                                    'value' => (string)$value
                                ];
                            }

                            // For dropdown, return index if found, otherwise return the value
                            return [
                                'value' => $optionIndex !== null ? (string)($optionIndex + 1) : (string)$value
                            ];
                        }

                        return [
                            'value' => (string)$value
                        ];
                    }

                    // If value has en/ar format, handle it
                    if (is_array($val) && isset($val['en']) && isset($val['ar'])) {
                        $selectedValue = $currentLang === 'ar' ? $val['ar'] : $val['en'];

                        // For dropdown/radio/rating/multi_select/checkbox fields, return the option index
                        if (in_array($sourceFieldType, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'])) {
                            $optionIndex = $this->findOptionIndexByValueForTargetField($val['en'], $val['ar'], $sourceFieldId);
                            return [
                                'value' => $optionIndex !== null ? (string)($optionIndex + 1) : $selectedValue
                            ];
                        }

                        // For other field types, return the text value
                        return [
                            'value' => $selectedValue
                        ];
                    }

                    // If value is a string or simple value, check if it's an option value
                    if (is_string($val) || is_numeric($val)) {
                        if (in_array($sourceFieldType, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'])) {
                            // First check if value is already a numeric index (1, 2, 3, etc.)
                            if (is_numeric($val)) {
                                $numericValue = (int)$val;
                                // Validate that the index is within valid range
                                if ($this->isValidOptionIndex($numericValue, $sourceFieldId)) {
                                    return [
                                        'value' => (string)$numericValue
                                    ];
                                }
                            }

                            // If not a valid index, try to find by text value
                            $optionIndex = $this->findOptionIndexBySingleValueForTargetField($val, $sourceFieldId);

                            // For radio and rating fields, we MUST return an index, not the text value
                            if (in_array($sourceFieldType, ['radio', 'rating'])) {
                                if ($optionIndex !== null) {
                                    return [
                                        'value' => (string)($optionIndex + 1)
                                    ];
                                }
                                // If we can't find the index, try case-insensitive search
                                $optionIndex = $this->findOptionIndexBySingleValueForTargetFieldCaseInsensitive($val, $sourceFieldId);
                                if ($optionIndex !== null) {
                                    return [
                                        'value' => (string)($optionIndex + 1)
                                    ];
                                }
                                // Last resort: return the value as-is (shouldn't happen in normal cases)
                                return [
                                    'value' => (string)$val
                                ];
                            }

                            // For other option-based fields, return index if found, otherwise return the value
                            return [
                                'value' => $optionIndex !== null ? (string)($optionIndex + 1) : (string)$val
                            ];
                        }

                        return [
                            'value' => (string)$val
                        ];
                    }

                    return $val;
                })->toArray();
            }
            return $rule;
        })->toArray();
    }

    /**
     * Get target field type by field ID
     */
    protected function getTargetFieldType($fieldId)
    {
        // Get the target field from database
        $targetField = \App\Models\FormField::where('slug', $fieldId)->first();

        if (!$targetField) {
            return 'text'; // Default to text if field not found
        }

        return $targetField->type;
    }


    /**
     * Check if a numeric index is valid for a field's options
     */
    protected function isValidOptionIndex($index, $fieldId)
    {
        // Index must be 1-based and positive
        if ($index < 1) {
            return false;
        }

        // Get the field from database
        $field = \App\Models\FormField::where('slug', $fieldId)->first();

        if (!$field || !$field->options) {
            return false;
        }

        // Check if field has options (dropdown, radio, rating, multi_select, checkbox)
        if (!in_array($field->type, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'])) {
            return false;
        }

        // Count the number of options
        $optionCount = 0;
        if (isset($field->options['en']) && isset($field->options['ar']) &&
            is_string($field->options['en']) && is_string($field->options['ar'])) {
            // Convert string format to array
            $enOptions = \App\Models\FormField::parseOptionsString($field->options['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($field->options['ar']);
            $optionCount = max(count($enOptions), count($arOptions));
        } elseif (is_array($field->options)) {
            // Handle array format - could be ['en' => [...], 'ar' => [...]] or direct array
            if (isset($field->options['en']) && is_array($field->options['en'])) {
                $optionCount = max(count($field->options['en']), count($field->options['ar'] ?? []));
            } else {
                $optionCount = count($field->options);
            }
        }

        // Check if index is within valid range (1 to optionCount)
        return $index <= $optionCount;
    }

    /**
     * Find option index by English and Arabic values for target field
     */
    protected function findOptionIndexByValueForTargetField($enValue, $arValue, $targetFieldId)
    {
        // Get the target field from database
        $targetField = \App\Models\FormField::where('slug', $targetFieldId)->first();

        if (!$targetField || !$targetField->options) {
            return null;
        }

        // Check if field has options (dropdown, radio, rating, multi_select, checkbox)
        if (!in_array($targetField->type, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'])) {
            return null;
        }

        // Process options to handle both string and array formats
        $processedOptions = [];
        $rawOptions = $targetField->options;

        if (isset($rawOptions['en']) && isset($rawOptions['ar']) &&
            is_string($rawOptions['en']) && is_string($rawOptions['ar'])) {
            // Convert string format to array
            $enOptions = \App\Models\FormField::parseOptionsString($rawOptions['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($rawOptions['ar']);
            $maxLength = max(count($enOptions), count($arOptions));

            for ($i = 0; $i < $maxLength; $i++) {
                $processedOptions[] = [
                    'en' => trim($enOptions[$i] ?? ''),
                    'ar' => trim($arOptions[$i] ?? '')
                ];
            }
        } elseif (is_array($rawOptions)) {
            // Handle different array formats
            if (isset($rawOptions[0]) && is_array($rawOptions[0]) && isset($rawOptions[0]['value'])) {
                // Format: [{"id": 1, "value": "a", "label": "a"}, ...]
                foreach ($rawOptions as $opt) {
                    $processedOptions[] = [
                        'en' => trim($opt['value'] ?? ''),
                        'ar' => trim($opt['value'] ?? '')
                    ];
                }
            }
            elseif (isset($rawOptions[0]) && is_array($rawOptions[0]) && isset($rawOptions[0]['en'])) {
                $processedOptions = $rawOptions;
            }
            else {
                $processedOptions = $rawOptions;
            }
        }

        // Search for the value in options
        foreach ($processedOptions as $index => $option) {
            if (is_array($option)) {
                $optionEn = trim($option['en'] ?? '');
                $optionAr = trim($option['ar'] ?? '');

                // Exact match
                if (($optionEn === $enValue && $optionAr === $arValue) ||
                    ($optionEn === $enValue) || ($optionAr === $arValue)) {
                    return $index;
                }

                // Partial match - check if the value is contained in the option
                // This handles cases like: searching for "b" in "22b" or "ب" in "22ب"
                if ((!empty($enValue) && (strpos($optionEn, $enValue) !== false || strpos($optionAr, $enValue) !== false)) ||
                    (!empty($arValue) && (strpos($optionEn, $arValue) !== false || strpos($optionAr, $arValue) !== false))) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Find option index by single value for target field
     */
    protected function findOptionIndexBySingleValueForTargetField($value, $targetFieldId)
    {
        // Get the target field from database
        $targetField = \App\Models\FormField::where('slug', $targetFieldId)->first();

        if (!$targetField || !$targetField->options) {
            return null;
        }

        // Check if field has options (dropdown, radio, rating, multi_select, checkbox)
        if (!in_array($targetField->type, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'])) {
            return null;
        }

        // Process options to handle both string and array formats
        $processedOptions = [];
        $rawOptions = $targetField->options;

        if (isset($rawOptions['en']) && isset($rawOptions['ar']) &&
            is_string($rawOptions['en']) && is_string($rawOptions['ar'])) {
            // Convert string format to array
            $enOptions = \App\Models\FormField::parseOptionsString($rawOptions['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($rawOptions['ar']);
            $maxLength = max(count($enOptions), count($arOptions));

            for ($i = 0; $i < $maxLength; $i++) {
                $processedOptions[] = [
                    'en' => trim($enOptions[$i] ?? ''),
                    'ar' => trim($arOptions[$i] ?? '')
                ];
            }
        } elseif (is_array($rawOptions)) {
            // Handle different array formats
            // Check if it's in formatOptions format: [{"id": 1, "value": "a", "label": "a"}, ...]
            if (isset($rawOptions[0]) && is_array($rawOptions[0]) && isset($rawOptions[0]['value'])) {
                // This is the formatted options format from formatOptions()
                foreach ($rawOptions as $opt) {
                    $processedOptions[] = [
                        'value' => trim($opt['value'] ?? ''),
                        'label' => trim($opt['label'] ?? ''),
                        'en' => trim($opt['value'] ?? ''), // Use value as en for compatibility
                        'ar' => trim($opt['value'] ?? '')  // Use value as ar for compatibility
                    ];
                }
            }
            // Check if it's in en/ar array format: [{"en": "a", "ar": "..."}, ...]
            elseif (isset($rawOptions[0]) && is_array($rawOptions[0]) && isset($rawOptions[0]['en'])) {
                $processedOptions = $rawOptions;
            }
            // Check if it's a simple indexed array: ["a", "b", "c"]
            elseif (isset($rawOptions[0]) && is_string($rawOptions[0])) {
                foreach ($rawOptions as $opt) {
                    $processedOptions[] = [
                        'en' => trim($opt),
                        'ar' => trim($opt)
                    ];
                }
            }
            // Default: use as is
            else {
                $processedOptions = $rawOptions;
            }
        }

        // Search for the value in options
        $searchValue = trim($value);
        foreach ($processedOptions as $index => $option) {
            if (is_array($option)) {
                // Check both English and Arabic values (case-insensitive for better matching)
                if (isset($option['en']) && isset($option['ar'])) {
                    $enValue = trim($option['en']);
                    $arValue = trim($option['ar']);

                    // Exact match
                    if (strcasecmp($enValue, $searchValue) === 0 || strcasecmp($arValue, $searchValue) === 0 ||
                        $enValue === $searchValue || $arValue === $searchValue) {
                        return $index;
                    }

                    // Partial match - check if the value is contained in the option
                    if (!empty($searchValue) &&
                        (strpos($enValue, $searchValue) !== false || strpos($arValue, $searchValue) !== false)) {
                        return $index;
                    }
                }
                // Check if it's a simple array with values
                if (isset($option['value'])) {
                    $optionValue = trim($option['value']);
                    if (strcasecmp($optionValue, $searchValue) === 0 || $optionValue === $searchValue) {
                        return $index;
                    }
                    // Partial match
                    if (!empty($searchValue) && strpos($optionValue, $searchValue) !== false) {
                        return $index;
                    }
                }
                // Check if it's in formatOptions format (id, value, label)
                if (isset($option['label'])) {
                    $labelValue = trim($option['label']);
                    if (strcasecmp($labelValue, $searchValue) === 0 || $labelValue === $searchValue) {
                        return $index;
                    }
                    // Partial match
                    if (!empty($searchValue) && strpos($labelValue, $searchValue) !== false) {
                        return $index;
                    }
                }
                // Also check if the option itself is the value (for simple arrays)
                if (count($option) === 1 && isset($option[0])) {
                    $optionValue = trim($option[0]);
                    if (strcasecmp($optionValue, $searchValue) === 0 || $optionValue === $searchValue) {
                        return $index;
                    }
                }
            } elseif (is_string($option)) {
                $optionValue = trim($option);
                if (strcasecmp($optionValue, $searchValue) === 0 || $optionValue === $searchValue) {
                    return $index;
                }
                // Partial match
                if (!empty($searchValue) && strpos($optionValue, $searchValue) !== false) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Find option index by single value for target field (case-insensitive search)
     */
    protected function findOptionIndexBySingleValueForTargetFieldCaseInsensitive($value, $targetFieldId)
    {
        // Get the target field from database
        $targetField = \App\Models\FormField::where('slug', $targetFieldId)->first();

        if (!$targetField || !$targetField->options) {
            return null;
        }

        // Check if field has options (dropdown, radio, rating, multi_select, checkbox)
        if (!in_array($targetField->type, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'])) {
            return null;
        }

        // Process options to handle both string and array formats (same logic as findOptionIndexBySingleValueForTargetField)
        $processedOptions = [];
        if (isset($targetField->options['en']) && isset($targetField->options['ar']) &&
            is_string($targetField->options['en']) && is_string($targetField->options['ar'])) {
            // Convert string format to array
            $enOptions = \App\Models\FormField::parseOptionsString($targetField->options['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($targetField->options['ar']);
            $maxLength = max(count($enOptions), count($arOptions));

            for ($i = 0; $i < $maxLength; $i++) {
                $processedOptions[] = [
                    'en' => trim($enOptions[$i] ?? ''),
                    'ar' => trim($arOptions[$i] ?? '')
                ];
            }
        } elseif (is_array($targetField->options)) {
            // Handle different array formats (same as findOptionIndexBySingleValueForTargetField)
            if (isset($targetField->options[0]) && is_array($targetField->options[0]) && isset($targetField->options[0]['value'])) {
                foreach ($targetField->options as $opt) {
                    $processedOptions[] = [
                        'value' => trim($opt['value'] ?? ''),
                        'label' => trim($opt['label'] ?? ''),
                        'en' => trim($opt['value'] ?? ''),
                        'ar' => trim($opt['value'] ?? '')
                    ];
                }
            }
            elseif (isset($targetField->options[0]) && is_array($targetField->options[0]) && isset($targetField->options[0]['en'])) {
                $processedOptions = $targetField->options;
            }
            elseif (isset($targetField->options[0]) && is_string($targetField->options[0])) {
                foreach ($targetField->options as $opt) {
                    $processedOptions[] = [
                        'en' => trim($opt),
                        'ar' => trim($opt)
                    ];
                }
            }
            else {
                $processedOptions = $targetField->options;
            }
        }

        // Search for the value in options (case-insensitive)
        $valueLower = mb_strtolower(trim($value));
        foreach ($processedOptions as $index => $option) {
            if (is_array($option)) {
                // Check both English and Arabic values (case-insensitive)
                if (isset($option['en']) && isset($option['ar'])) {
                    $enLower = mb_strtolower(trim($option['en']));
                    $arLower = mb_strtolower(trim($option['ar']));
                    if ($enLower === $valueLower || $arLower === $valueLower) {
                        return $index;
                    }
                }
                // Check if it's a simple array with values
                elseif (isset($option['value'])) {
                    $optionValueLower = mb_strtolower(trim($option['value']));
                    if ($optionValueLower === $valueLower) {
                        return $index;
                    }
                }
                // Check if it's in formatOptions format (id, value, label)
                elseif (isset($option['label'])) {
                    $labelLower = mb_strtolower(trim($option['label']));
                    if ($labelLower === $valueLower) {
                        return $index;
                    }
                }
            } elseif (is_string($option)) {
                $optionLower = mb_strtolower(trim($option));
                if ($optionLower === $valueLower) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Find option index by English and Arabic values
     */
    protected function findOptionIndexByValue($enValue, $arValue)
    {
        if (!$this->options) {
            return null;
        }

        // If options is stored as strings (en/ar), convert to array format
        if (isset($this->options['en']) && isset($this->options['ar']) &&
            is_string($this->options['en']) && is_string($this->options['ar'])) {
            $enOptions = \App\Models\FormField::parseOptionsString($this->options['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($this->options['ar']);

            // Find the index where both en and ar values match
            for ($i = 0; $i < max(count($enOptions), count($arOptions)); $i++) {
                if (($enOptions[$i] ?? '') === $enValue && ($arOptions[$i] ?? '') === $arValue) {
                    return $i;
                }
            }
        }

        // If options is already in array format
        if (is_array($this->options)) {
            foreach ($this->options as $index => $option) {
                if (is_array($option) && isset($option['en']) && isset($option['ar'])) {
                    if ($option['en'] === $enValue && $option['ar'] === $arValue) {
                        return $index;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Find option index by single value (for cases where we only have one language)
     */
    protected function findOptionIndexBySingleValue($value)
    {
        if (!$this->options) {
            return null;
        }

        // If options is stored as strings (en/ar), check both languages
        if (isset($this->options['en']) && isset($this->options['ar']) &&
            is_string($this->options['en']) && is_string($this->options['ar'])) {
            $enOptions = \App\Models\FormField::parseOptionsString($this->options['en']);
            $arOptions = \App\Models\FormField::parseOptionsString($this->options['ar']);

            // Check English options first
            foreach ($enOptions as $index => $option) {
                if ($option === $value) {
                    return $index;
                }
            }

            // Check Arabic options
            foreach ($arOptions as $index => $option) {
                if ($option === $value) {
                    return $index;
                }
            }
        }

        // If options is already in array format
        if (is_array($this->options)) {
            foreach ($this->options as $index => $option) {
                if (is_array($option)) {
                    // Check if it matches any of the option values
                    if (isset($option['value']) && $option['value'] === $value) {
                        return $index;
                    }
                    if (isset($option['en']) && $option['en'] === $value) {
                        return $index;
                    }
                    if (isset($option['ar']) && $option['ar'] === $value) {
                        return $index;
                    }
                } elseif (is_string($option) && $option === $value) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Find option ID by value
     */
    protected function findOptionIdByValue($value)
    {
        if (!$this->options) {
            return null;
        }

        // If options is an array of objects, search through them
        if (is_array($this->options)) {
            foreach ($this->options as $option) {
                if (is_array($option) && isset($option['value']) && $option['value'] === $value) {
                    return $option['id'];
                }
            }
        }

        // If options is a string, parse and search
        if (is_string($this->options)) {
            $options = \App\Models\FormField::parseOptionsString($this->options);
            foreach ($options as $index => $option) {
                if ($option === $value) {
                    return $index + 1;
                }
            }
        }

        return null;
    }

    /**
     * Check if the field has valid conditional logic rules
     */
    protected function hasValidConditionalLogicRules(): bool
    {
        if (!is_array($this->conditional_logic_rules)) {
            return false;
        }

        foreach ($this->conditional_logic_rules as $rule) {
            if (empty($rule['field_id'])) {
                return false;
            }
        }

        return true;
    }
}
