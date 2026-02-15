<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        $projectId = $this->route('project_id') ?? $this->input('project_id');

        return $user &&
            $projectId &&
            $user->projects()
                ->where('projects.id', $projectId)
                ->exists();
    }

    /**
     * Prepare the data for validation.
     * Normalize values close to 100 to exactly 100 to handle floating point precision issues.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('answers')) {
            $answers = $this->input('answers', []);
            $tolerance = 0.0001;
            
            foreach ($answers as $key => $value) {
                // Skip special keys that are not numeric answers
                if (in_array($key, ['final_comment']) || 
                    str_ends_with($key, '_questions') || 
                    str_ends_with($key, '_comment')) {
                    continue;
                }
                
                // Normalize numeric values to handle floating point precision
                if (is_numeric($value) || (is_string($value) && is_numeric($value))) {
                    $numericValue = (float) $value;
                    
                    // If value is between 99.9999 and 100.0001, normalize to exactly 100
                    if ($numericValue >= (100 - $tolerance) && $numericValue <= (100 + $tolerance)) {
                        $answers[$key] = 100;
                    }
                    // If value is between -0.0001 and 0.0001, normalize to exactly 0
                    elseif ($numericValue >= (0 - $tolerance) && $numericValue <= (0 + $tolerance)) {
                        $answers[$key] = 0;
                    }
                }
            }
            
            $this->merge(['answers' => $answers]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'project_id' => 'required|integer|exists:projects,id',
            'form_id' => 'required|integer|exists:forms,id',
            'stage_id' => 'required|integer|exists:stages,id',
            'answers' => ['required', 'array'],
        ];

        // Dynamically add validation rules for answer values
        // Validate that all numeric answer values are between 0 and 100
        // Multiple choice answers can be arrays (for multiple selections) or integers (for single selection)
        if ($this->has('answers')) {
            $answers = $this->input('answers', []);
            
            foreach ($answers as $key => $value) {
                // Skip special keys that are not numeric answers
                if (in_array($key, ['final_comment']) || 
                    str_ends_with($key, '_questions') || 
                    str_ends_with($key, '_comment')) {
                    continue;
                }
                
                // Validate numeric answer values to be between 0 and 100
                // Use 'gte:0' and 'lte:100' instead of 'min' and 'max' to handle decimal values properly
                if (is_numeric($value) || (is_string($value) && is_numeric($value))) {
                    $rules["answers.{$key}"] = ['required', 'numeric', 'gte:0', 'lte:100'];
                } elseif (is_array($value)) {
                    // Multiple choice answers can be arrays (for multiple selections)
                    $rules["answers.{$key}"] = ['required', 'array'];
                    $rules["answers.{$key}.*"] = ['integer', 'min:0'];
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];
        
        if ($this->has('answers')) {
            $answers = $this->input('answers', []);
            
            foreach ($answers as $key => $value) {
                if (in_array($key, ['final_comment']) || 
                    str_ends_with($key, '_questions') || 
                    str_ends_with($key, '_comment')) {
                    continue;
                }
                
                if (is_numeric($value) || (is_string($value) && is_numeric($value))) {
                    $messages["answers.{$key}.gte"] = 'The evaluation score must be at least 0%. / يجب أن تكون درجة التقييم على الأقل 0٪.';
                    $messages["answers.{$key}.lte"] = 'The evaluation score must not exceed 100%. / يجب ألا تتجاوز درجة التقييم 100٪.';
                    $messages["answers.{$key}.numeric"] = 'The evaluation score must be a valid number. / يجب أن تكون درجة التقييم رقماً صحيحاً.';
                }
            }
        }
        
        return $messages;
    }
}
