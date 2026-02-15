<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class FormField extends Model
{
    use HasTranslations,LogsActivity;

    public array $translatable = ['label', 'placeholder', 'hint','conditional_logic_rules.values'];

    protected $fillable = [
        'form_id',
        'section_id',
        'label',
        'type',
        'required',
        'options',
        'mandatory_options',
        'placeholder',
        'hint',
        'validation_rules',
        'sort',
        'section_id',
        'slug',
        'conditional_logic',
        'conditional_logic_rules'
    ];

    protected $casts = [
        'options' => 'array',
        'mandatory_options' => 'array',
        'conditional_logic_rules' => 'array',
        'validation_rules' => 'array',
        'required' => 'boolean',
        'conditional_logic' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['form_id', 'section_id', 'label','type','required','options','placeholder','validation_rules','conditional_logic_rules'])
            ->logOnlyDirty()
            ->useLogName('form')
            ->setDescriptionForEvent(fn(string $eventName) => "FormField was {$eventName}");
    }

    protected static function boot(): void
    {
        parent::boot();
    }

    public static function getDefaultFieldsForType($type): array
    {
        return match ($type) {
            'registration' => [
                [
                    'label' => ['en' => 'Participant Name', 'ar' => 'اسم المشارك'],
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'This field is required and auto-fetched from the participant’s profile. It cannot be deleted.',
                    'deletable' => false,
                ],
                [
                    'label' => ['en' => 'Participant Email', 'ar' => 'البريد الإلكتروني'],
                    'type' => 'email',
                    'required' => true,
                    'hint' => 'This field is required and auto-fetched from the participant’s profile. It cannot be deleted.',
                    'deletable' => false,
                ],
            ],
            'project' => [
                [
                    'label' => ['en' => 'Project Name', 'ar' => 'اسم المشروع'],
                    'type' => 'text',
                    'required' => true,
                    'hint' => 'This field is required and cannot be deleted.',
                    'deletable' => false,
                ],
            ],
            default => [],
        };
    }


    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class);
    }

    public function setConditionalLogicAttribute($value)
    {
        $this->attributes['conditional_logic'] = $value ?? false;
    }

    public function setConditionalLogicRulesAttribute($value)
    {
        $value = collect($value)->map(function ($rule) {
            if (isset($rule['field_id'])) {
                $rule['field_id'] = $this->toSnakeCase($rule['field_id']);
            }
            
            // Handle values array - keep as is for storage
            if (isset($rule['values']) && is_array($rule['values'])) {
                $rule['values'] = collect($rule['values'])->map(function ($valueItem) {
                    // Keep the value as is - no conversion needed
                    return $valueItem;
                })->toArray();
            }
            
            return $rule;
        })->toArray();
        
        $this->attributes['conditional_logic_rules'] = json_encode($value);
        
        // Check if there are valid values in the rules
        $hasValidValues = false;
        foreach ($value as $rule) {
            if (isset($rule['values']) && is_array($rule['values'])) {
                foreach ($rule['values'] as $valueItem) {
                    if (is_array($valueItem) && isset($valueItem['value']) && !empty($valueItem['value'])) {
                        $hasValidValues = true;
                        break 2;
                    }
                }
            }
        }
        
        // Update conditional_logic based on whether there are valid values
        $this->attributes['conditional_logic'] = $hasValidValues;
    }

    public function setSlugAttribute($value)
    {
        $label = json_decode($this->attributes['label'], true);
        $value = $label['en'] ?? '';

        $slug = $this->toSnakeCase($value);

        $type = $this->type ?? null;

        if ($type === 'file') {
            $slug = 'file_' . $slug;
        }


        $this->attributes['slug'] = $slug;
    }

    // --- Helpers ---
    protected function toSnakeCase(string $value): string
    {
        $value = preg_replace('/\s+/', '_', trim($value));               // spaces to underscores
        $value = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);     // camelCase to snake_case
        return strtolower($value);
    }

    public function hasValidConditionalLogicRules(): bool
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

    public function getConditionalLogicRulesAttribute($value)
    {
        $rules = json_decode($value, true) ?? [];
        
        // Convert back to editing format for Filament
        $rules = collect($rules)->map(function ($rule) {
            if (isset($rule['values']) && is_array($rule['values'])) {
                $rule['values'] = collect($rule['values'])->map(function ($valueItem) {
                    // Always return the value as stored - let Filament handle the display
                    return $valueItem;
                })->toArray();
            }
            return $rule;
        })->toArray();
        
        return $rules;
    }



    /**
     * Get processed options for conditional logic
     */
    public function getProcessedOptionsAttribute()
    {
        $options = $this->options ?? [];
        
        // If options is stored as strings (en/ar), convert to array format
        if (isset($options['en']) && isset($options['ar']) && is_string($options['en']) && is_string($options['ar'])) {
            $enOptions = self::parseOptionsString($options['en']);
            $arOptions = self::parseOptionsString($options['ar']);
            $processedOptions = [];
            $maxLength = max(count($enOptions), count($arOptions));
            for ($i = 0; $i < $maxLength; $i++) {
                $processedOptions[] = [
                    'en' => $enOptions[$i] ?? '',
                    'ar' => $arOptions[$i] ?? ''
                ];
            }
            return $processedOptions;
        }

        // If options is en/ar as arrays (e.g. ['en' => ['Opt1','Opt2'], 'ar' => [...]])
        if (isset($options['en']) && is_array($options['en']) && isset($options['ar']) && is_array($options['ar'])) {
            $enOpts = array_values($options['en']);
            $arOpts = array_values($options['ar']);
            $processedOptions = [];
            $maxLength = max(count($enOpts), count($arOpts));
            for ($i = 0; $i < $maxLength; $i++) {
                $processedOptions[] = [
                    'en' => $enOpts[$i] ?? '',
                    'ar' => $arOpts[$i] ?? ''
                ];
            }
            return $processedOptions;
        }

        // If options is indexed array of objects with 'value'/'label' (e.g. [['id'=>1,'value'=>'Opt1','label'=>'Opt1'], ...])
        if (isset($options[0]) && is_array($options[0]) && (isset($options[0]['label']) || isset($options[0]['value']))) {
            $processedOptions = [];
            foreach ($options as $opt) {
                if (! is_array($opt)) {
                    continue;
                }
                $labelEn = $opt['en'] ?? $opt['label']['en'] ?? null;
                $labelAr = $opt['ar'] ?? $opt['label']['ar'] ?? null;
                if ($labelEn === null && $labelAr === null) {
                    $fallback = is_string($opt['label'] ?? null) ? $opt['label'] : (isset($opt['value']) ? (string) $opt['value'] : '');
                    $labelEn = $labelAr = $fallback;
                }
                $processedOptions[] = [
                    'en' => $labelEn ?? '',
                    'ar' => $labelAr ?? ''
                ];
            }
            return $processedOptions;
        }

        // If options has numeric string keys (e.g. {"1": {...}, "2": {...}} from JSON) — normalize to 0-based
        if (is_array($options) && ! isset($options['en']) && ! isset($options['ar'])) {
            $keys = array_keys($options);
            $allNumeric = ! empty($keys) && array_reduce($keys, fn ($carry, $k) => $carry && (is_numeric($k) || ctype_digit((string) $k)), true);
            if ($allNumeric) {
                return array_values($options);
            }
        }
        
        // If already in array format (indexed list of options with en/ar), return as is
        return is_array($options) ? $options : [];
    }

    /**
     * Parse options string that can contain both English and Arabic commas
     */
    public static function parseOptionsString(string $optionsString): array
    {
        if (empty($optionsString)) {
            return [];
        }

        // Split by both English comma (,) and Arabic comma (،)
        // Use preg_split to handle both separators
        $options = preg_split('/[,،]/u', $optionsString);
        
        // Filter out empty options and trim whitespace
        return array_filter(array_map('trim', $options), function($option) {
            return !empty($option);
        });
    }

    /**
     * Get the assessment criteria that assess this field.
     */
    public function assessmentCriteria(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            FormAssessmentCriterion::class,
            'form_assessment_criterion_form_field',
            'form_field_id',
            'form_assessment_criterion_id'
        )->withTimestamps();
    }

    /**
     * Get mandatory option indices (1-based) for checkbox fields.
     * 
     * @return array Array of mandatory option indices (1-based)
     */
    public function getMandatoryOptionIndices(): array
    {
        if ($this->type !== 'checkbox' || empty($this->mandatory_options)) {
            return [];
        }

        return array_map('intval', $this->mandatory_options);
    }

    /**
     * Check if a specific option index is mandatory.
     * 
     * @param int $optionIndex 1-based option index
     * @return bool
     */
    public function isOptionMandatory(int $optionIndex): bool
    {
        return in_array($optionIndex, $this->getMandatoryOptionIndices());
    }

    /**
     * Validate that all mandatory checkbox options are checked.
     * 
     * @param mixed $selectedValues The values selected by the user (can be array, string, or comma-separated)
     * @return array Array of unchecked mandatory option indices (1-based), empty if all are checked
     */
    public function validateMandatoryOptions($selectedValues): array
    {
        if ($this->type !== 'checkbox') {
            return [];
        }

        $mandatoryIndices = $this->getMandatoryOptionIndices();
        
        if (empty($mandatoryIndices)) {
            return [];
        }

        // Normalize selected values to array of integers
        $selectedIndices = $this->normalizeSelectedCheckboxValues($selectedValues);

        // Find which mandatory options are not checked
        $uncheckedMandatory = array_diff($mandatoryIndices, $selectedIndices);

        return array_values($uncheckedMandatory);
    }

    /**
     * Normalize selected checkbox values to array of 1-based indices.
     * 
     * @param mixed $selectedValues
     * @return array
     */
    protected function normalizeSelectedCheckboxValues($selectedValues): array
    {
        if (empty($selectedValues)) {
            return [];
        }

        // Handle string (comma-separated values)
        if (is_string($selectedValues)) {
            $selectedValues = array_filter(array_map('trim', explode(',', $selectedValues)));
        }

        if (!is_array($selectedValues)) {
            $selectedValues = [$selectedValues];
        }

        // Convert all values to integers
        return array_map('intval', array_filter($selectedValues, function ($val) {
            return is_numeric($val) && $val > 0;
        }));
    }

    /**
     * Get labels for mandatory options that are not checked.
     * 
     * @param array $uncheckedIndices Array of 1-based indices that are not checked
     * @param string $locale Language code ('en' or 'ar')
     * @return array Array of option labels
     */
    public function getUncheckedMandatoryLabels(array $uncheckedIndices, string $locale = 'en'): array
    {
        if (empty($uncheckedIndices)) {
            return [];
        }

        $processedOptions = $this->processed_options;
        $labels = [];

        foreach ($uncheckedIndices as $index) {
            $arrayIndex = $index - 1; // Convert to 0-based index
            if (isset($processedOptions[$arrayIndex])) {
                $option = $processedOptions[$arrayIndex];
                if (is_array($option)) {
                    $labels[] = $option[$locale] ?? $option['en'] ?? $option['ar'] ?? '';
                } else {
                    $labels[] = $option;
                }
            }
        }

        return array_filter($labels);
    }

    /**
     * Resolve raw field value (ID or IDs) to display label(s) for AI context.
     * Used when sending option-based field values (dropdown, multi_select, radio, checkbox, rating)
     * to the AI enhancement endpoint so the AI receives human-readable labels instead of numeric IDs.
     *
     * @param mixed $value Raw value from form: numeric ID, comma-separated IDs, or array of IDs
     * @param string $locale Language code ('en' or 'ar')
     * @return string|null Display label(s), comma-separated for multi-select, or null if not applicable
     */
    public function resolveValueToLabel($value, string $locale = 'en'): ?string
    {
        if (! in_array($this->type, ['dropdown', 'radio', 'rating', 'multi_select', 'checkbox'], true)) {
            return null;
        }

        $processedOptions = $this->processed_options;
        if (empty($processedOptions)) {
            return null;
        }

        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'en';

        // Normalize value to array of IDs
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            $ids = array_filter($value, fn ($v) => $v !== null && $v !== '');
        } else {
            $ids = preg_match('/^\d+(\s*,\s*\d+)*$/', trim((string) $value))
                ? array_map('trim', explode(',', (string) $value))
                : [trim((string) $value)];
        }
        $ids = array_filter($ids);
        if (empty($ids)) {
            return null;
        }

        $labels = [];
        foreach ($ids as $id) {
            if (! is_numeric($id)) {
                $labels[] = $id;
                continue;
            }
            $index = (int) $id - 1; // 1-based to 0-based
            if (isset($processedOptions[$index])) {
                $option = $processedOptions[$index];
                if (is_array($option)) {
                    $label = $option[$locale] ?? $option['en'] ?? $option['ar'] ?? null;
                    // Support option format with 'label' or 'value' (e.g. from API formatOptions)
                    if ($label === null || $label === '') {
                        $label = (is_string($option['label'] ?? null) ? $option['label'] : null)
                            ?? (is_string($option['value'] ?? null) ? $option['value'] : null)
                            ?? (isset($option['value']) ? (string) $option['value'] : '');
                    }
                } else {
                    $label = (string) $option;
                }
                if ($label !== '' && $label !== null) {
                    $labels[] = $label;
                }
            }
        }

        return empty($labels) ? null : implode(', ', $labels);
    }
}
