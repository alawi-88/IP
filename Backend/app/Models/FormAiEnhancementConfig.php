<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasActivityLog;

class FormAiEnhancementConfig extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'form_id',
        'ai_enhancement_enabled',
        'ai_enhancement_fields',
    ];

    protected $casts = [
        'ai_enhancement_enabled' => 'boolean',
        'ai_enhancement_fields' => 'array',
    ];

    protected array $logFields = [
        'form_id',
        'ai_enhancement_enabled',
        'ai_enhancement_fields',
    ];

    protected string $moduleName = 'Form AI Enhancement Config';
    protected string $logName = 'form_ai_enhancement_config';

    /**
     * Get the form that owns this config.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Check if AI enhancement is enabled for this form.
     */
    public function isEnhancementEnabled(): bool
    {
        return (bool) $this->ai_enhancement_enabled;
    }

    /**
     * Get the fields that should be enhanced.
     * Returns array of field slugs.
     */
    public function getEnhancementFields(): array
    {
        $fields = $this->ai_enhancement_fields ?? [];
        
        // If fields is array of objects with slug and instructions
        if (!empty($fields) && is_array($fields) && isset($fields[0]) && is_array($fields[0]) && isset($fields[0]['slug'])) {
            return array_column($fields, 'slug');
        }
        
        // Legacy format: array of slugs
        return $fields;
    }

    /**
     * Get instructions for a specific field.
     */
    public function getFieldInstructions(string $fieldSlug): ?string
    {
        $fields = $this->ai_enhancement_fields ?? [];
        
        if (empty($fields)) {
            return null;
        }

        // If fields is array of objects with slug and instructions
        if (isset($fields[0]) && is_array($fields[0]) && isset($fields[0]['slug'])) {
            foreach ($fields as $field) {
                if (isset($field['slug']) && $field['slug'] === $fieldSlug) {
                    return $field['instructions'] ?? null;
                }
            }
        }
        
        return null;
    }

    /**
     * Get context for a specific field.
     */
    public function getFieldContext(string $fieldSlug): ?string
    {
        $fields = $this->ai_enhancement_fields ?? [];
        
        if (empty($fields)) {
            return null;
        }

        // If fields is array of objects with slug, instructions, and context
        if (isset($fields[0]) && is_array($fields[0]) && isset($fields[0]['slug'])) {
            foreach ($fields as $field) {
                if (isset($field['slug']) && $field['slug'] === $fieldSlug) {
                    return $field['context'] ?? null;
                }
            }
        }
        
        return null;
    }

    /**
     * Check if a specific field should be enhanced.
     */
    public function shouldEnhanceField(string $fieldSlug): bool
    {
        if (!$this->isEnhancementEnabled()) {
            return false;
        }

        $fields = $this->getEnhancementFields();
        
        // If no fields specified, enhance all text/textarea fields
        if (empty($fields)) {
            return true;
        }

        return in_array($fieldSlug, $fields);
    }
}
