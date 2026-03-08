<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnhanceFormRequest;
use App\Http\Requests\ListFormRequest;
use App\Http\Requests\ProjectFormRequest;
use App\Http\Resources\EvaluationsFormResource;
use App\Http\Resources\ProjectsFormResource;
use App\Http\Resources\RegistrationFormResource;
use App\Http\Resources\StageEvaluationResource;
use App\Http\Resources\StageResource;
use App\Models\Program;
use App\Models\Form;
use App\Models\ProjectFormConfig;
use App\Models\Stage;
use App\Models\TeamFormConfig;
use App\Services\AiEnhancementService;
use Illuminate\Http\JsonResponse;
use Spatie\SchemalessAttributes\SchemalessAttributes;

class FormController extends Controller
{
    public function registration(ListFormRequest $request): JsonResponse
    {
        // Check if program exists and is not closed
        $program = Program::where('id', $request->program_id)
            ->published()
            ->active()
            ->first();

        if (!$program || $program->isClosed()) {
            return response()->json([]);
        }

        $form = Form::with('FormSteps')->where('program_id', $request->program_id)
            ->registrationType()
            ->published()
            ->active()
            ->first();

        if (!$form) return response()->json([]);
        
        // Get participant's answers if authenticated (only non-archived applications)
        $answers = [];
        $submitType = 'submission';
        $applicationId = null;
        if (auth()->check()) {
            $application = auth()->user()->programApplications()
                ->where('form_id', $form->id)
                ->where('is_archived', false)
                ->first();
            
            if ($application) {
                $applicationId = $application->id;
                $submitType = $application->type ?? 'submission';
                if ($application->form_submissions) {
                    $answers = $application->form_submissions instanceof SchemalessAttributes
                        ? $application->form_submissions->toArray()
                        : (array) $application->form_submissions;
                }
            }
        }
        
        return response()->json(new RegistrationFormResource($form, $answers, $submitType, $applicationId));
    }

    public function projects(ListFormRequest $request): JsonResponse
    {
        $form = Form::with('ProjectSteps')->where('program_id', $request->program_id)
            ->projectType()
            ->published()
            ->active()
            ->get();

        if (!$form) return response()->json([]);
        $metadata = [
            "project_name" => null,
            "participant_name" => $request->user()->name ?? null
        ];
        return response()->json(ProjectsFormResource::collection($form, [], null, null, $metadata));
    }

    public function projects_form(ProjectFormRequest $request): JsonResponse
    {
        $application = null;
        $participant = request()->user();

        $form = Form::with('ProjectSteps')->where('id', $request->form_id)
            ->projectType()
            ->published()
            ->active()
            ->first();

        if ($participant) {
            $application = $participant->programApplications()->where('form_id', $form->id)->first();
        }

        if (!$form) {
            return response()->json([]);
        } 
        $project_id = $request->draft_project_id ?? null;
        
        $answers = [];
        $submitType = 'submission';
        $metadata = [
            "project_name" => null,
            "participant_name" => $request->user()->name ?? null
        ];      
        if ($project_id) {
            $project = \App\Models\Project::where('id', $project_id)
            ->first();
            if ($project) {
                $metadata = [
                    "project_name" => $project->form_submissions['project_name'] ?? null,
                    "participant_name" => $request->user()->name ?? null
                ]; 
                $project_id = $project->id;
                $submitType = $project->type ?? 'submission';
                if ($project->form_submissions) {
                    $answers = $project->form_submissions instanceof SchemalessAttributes
                        ? $project->form_submissions->toArray()
                        : (array) $project->form_submissions;
                }
            }
        } elseif ($application) {
            // Automatically find existing project (including archived) for this form and application
            $project = \App\Models\Project::where('form_id', $form->id)
                ->where('application_id', $application->id)
                ->latest()
                ->first();
            
            if ($project) {
                $metadata = [
                    "project_name" => $project->form_submissions['project_name'] ?? null,
                    "participant_name" => $request->user()->name ?? null
                ]; 
                $project_id = $project->id;
                $submitType = $project->type ?? 'submission';
                if ($project->form_submissions) {
                    $answers = $project->form_submissions instanceof SchemalessAttributes
                        ? $project->form_submissions->toArray()
                        : (array) $project->form_submissions;
                }
            }
        }
        return response()->json(new ProjectsFormResource($form, $answers, $application?->id, $submitType, $metadata, $project_id));
    }

    public function team_form_config(ListFormRequest $request): JsonResponse
    {
        $form = TeamFormConfig::where('program_id', $request->program_id)
            ->active()
            ->notArchived() // Only show non-archived Team Form Configurations
            ->first();

        return response()->json($form);
    }

    public function projects_form_config(ProjectFormRequest $request): JsonResponse
    {
        $form = ProjectFormConfig::where('form_id', $request->form_id)
            ->active() // Only show non-archived Project Form Configurations
            ->first();

        if (!$form) {
            return response()->json([
                'message' => 'No active project form configuration found for this form.',
            ], 404);
        }

        return response()->json($form);
    }
    public function evaluations(ListFormRequest $request): JsonResponse
    {
        $form = Form::where('program_id', $request->program_id)
            ->evaluationType()
            ->published()
            ->active()
            ->get();

        if (!$form) return response()->json([]);
        return response()->json(EvaluationsFormResource::collection($form));
    }

    public function evaluations_form(ProjectFormRequest $request): JsonResponse
    {
        $form = Form::where('id', $request->form_id)
            ->evaluationType()
            ->published()
            ->active()
            ->first();

        if (!$form) return response()->json([]);
        return response()->json(new EvaluationsFormResource($form));
    }

    public function fieldTypes(): JsonResponse
    {
        return response()->json(array_keys(Form::FIELD_TYPES));
    }

    public function evaluations_stages(): JsonResponse
    {
        $form = Stage::where('slug', 'evaluation')
            ->where('is_visible', true)
            ->get();

        if (!$form) return response()->json([]);
        return response()->json(StageEvaluationResource::collection($form));
    }

    /**
     * Enhance form data using AI.
     */
    public function enhance(EnhanceFormRequest $request, AiEnhancementService $enhancementService): JsonResponse
    {
        $formId = (int) $request->input('formId');
        $fieldsData = $request->input('fields', []);

        // Validate that form exists and is accessible
        $form = Form::where('id', $formId)
            ->published()
            ->active()
            ->first();

        if (!$form) {
            return response()->json([
                'success' => false,
                'message' => trans('forms.form_not_found'),
            ], 404);
        }

        // Check if AI enhancement is enabled for this form
        $config = $form->aiEnhancementConfig;
        if (!$config || !$config->isEnhancementEnabled()) {
            return response()->json([
                'success' => false,
                'message' => trans('forms.ai_enhancement_not_enabled'),
            ], 400);
        }

        // Ensure form fields are loaded for resolving option-based values to labels
        $form->load('fields');
        $fieldsBySlug = $form->fields->keyBy('slug');

        // Locale for resolving option labels (dropdown, radio, checkbox, etc.)
        $locale = request()->header('Accept-Language', app()->getLocale());
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'en';

        // Build a map of field values for context lookup
        $fieldValuesMap = [];
        foreach ($fieldsData as $field) {
            if (isset($field['slug']) && isset($field['value'])) {
                $fieldValuesMap[$field['slug']] = $field['value'];
            }
        }

        // Helper function to normalize field values to strings
        $normalizeValue = function ($value) {
            if (is_array($value)) {
                // Filter out empty values and convert to comma-separated string
                $filteredValues = array_filter($value, fn($v) => !empty($v) && $v !== '');
                return implode(', ', $filteredValues) ?: '';
            }
            return (string) ($value ?? '');
        };

        // Resolve option-based field value to display label for AI context (backend has field config and options)
        $resolveValueForAi = function ($slug, $rawValue) use ($fieldsBySlug, $locale, $normalizeValue) {
            $formField = $fieldsBySlug->get($slug);
            if ($formField) {
                $label = $formField->resolveValueToLabel($rawValue, $locale);
                if ($label !== null && $label !== '') {
                    return $label;
                }
            }
            return $normalizeValue($rawValue);
        };

        // Build payload directly from request (same format as AI API expects)
        // Include context value from the selected context field; send display labels for option-based fields
        $payload = [
            'formId' => (string) $formId,
            'fields' => array_map(function ($field) use ($config, $fieldValuesMap, $normalizeValue, $resolveValueForAi, $fieldsBySlug, $locale) {
                $fieldSlug = $field['slug'] ?? '';
                $rawFieldValue = $field['value'] ?? '';

                // Send display labels for dropdown/multi_select/radio/checkbox/rating so AI gets readable context
                $fieldValue = $resolveValueForAi($fieldSlug, $rawFieldValue);

                $fieldPayload = [
                    'fieldId' => $field['fieldId'] ?? '',
                    'slug' => $fieldSlug,
                    'label' => $field['label'] ?? '',
                    'type' => $field['type'] ?? '',
                    'value' => $fieldValue,
                    'instructions' => $field['instructions'] ?? null,
                ];

                // Add context value: get the context field slug from config, then get its value (resolved to label if option-based)
                $contextValue = null;
                $contextFieldSlug = null;
                if (isset($field['context']) && $field['context'] !== '' && $field['context'] !== null) {
                    $contextValue = $field['context'];
                } elseif ($config && $fieldSlug) {
                    $contextFieldSlug = $config->getFieldContext($fieldSlug);
                    if ($contextFieldSlug && array_key_exists($contextFieldSlug, $fieldValuesMap)) {
                        $contextValue = $fieldValuesMap[$contextFieldSlug];
                    }
                }

                if ($contextValue !== null && $contextValue !== '') {
                    // Resolve context field value to display label when we know the context field slug
                    $fieldPayload['context'] = $contextFieldSlug !== null
                        ? $resolveValueForAi($contextFieldSlug, $contextValue)
                        : $normalizeValue($contextValue);
                } else {
                    $fallbackContext = $fieldValue !== '' ? $fieldValue : (string) ($field['label'] ?? '');
                    $fieldPayload['context'] = $fallbackContext;
                }

                return $fieldPayload;
            }, $fieldsData),
        ];

        // Call enhancement service with direct payload
        $result = $enhancementService->enhanceWithPayload($payload);

        if (!$result['success']) {
            $status = $result['status'] ?? 500;

            // Map downstream AI errors to a 4xx client error, keep 5xx for real server issues
            if ($status >= 400 && $status < 500) {
                $httpStatus = 400;
            } else {
                $httpStatus = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? trans('forms.ai_enhancement_failed'),
            ], $httpStatus);
        }

        // Get suggestions as key-value array (slug => suggestedValue)
        $suggestionsMap = [];
        
        foreach ($result['suggestions'] ?? [] as $suggestion) {
            if (!is_array($suggestion)) {
                continue;
            }
            
            $fieldSlug = $suggestion['fieldSlug'] ?? $suggestion['slug'] ?? null;
            $suggestedValue = $suggestion['suggestedValue'] ?? $suggestion['value'] ?? null;
            
            if ($fieldSlug && $suggestedValue !== null && $suggestedValue !== '') {
                $suggestionsMap[$fieldSlug] = $suggestedValue;
            }
        }

        // Convert fields array to formData format (slug => value) for form resource
        $formData = [];
        foreach ($fieldsData as $field) {
            if (isset($field['slug']) && isset($field['value'])) {
                $formData[$field['slug']] = $field['value'];
            }
        }

        // Get participant's answers if authenticated
        $answers = $formData;
        $submitType = 'submission';
        $applicationId = null;
        if (auth()->check()) {
            $application = auth()->user()->programApplications()
                ->where('form_id', $form->id)
                ->where('is_archived', false)
                ->first();
            
            if ($application) {
                $applicationId = $application->id;
                $submitType = $application->type ?? 'submission';
                if ($application->form_submissions) {
                    $existingAnswers = $application->form_submissions instanceof SchemalessAttributes
                        ? $application->form_submissions->toArray()
                        : (array) $application->form_submissions;
                    // Merge with formData (formData takes precedence)
                    $answers = array_merge($existingAnswers, $formData);
                }
            }
        }

        // Determine form type and return appropriate resource
        $formType = $form->type;
        
        if ($formType === 'registration') {
            $formResource = new RegistrationFormResource($form, $answers, $submitType, $applicationId);
        } elseif ($formType === 'project') {
            // For project forms, we need to determine application_id and project_id
            $projectId = null;
            $application = null;
            if (auth()->check()) {
                $application = auth()->user()->programApplications()
                    ->where('form_id', $form->id)
                    ->first();
                if ($application) {
                    $project = \App\Models\Project::where('form_id', $form->id)
                        ->where('application_id', $application->id)
                        ->latest()
                        ->first();
                    if ($project) {
                        $projectId = $project->id;
                    }
                }
            }
            $formResource = new ProjectsFormResource($form, $answers, $application?->id, $submitType, [], $projectId);
        } else {
            $formResource = new EvaluationsFormResource($form);
        }

        $formArray = $formResource->toArray($request);

        // Helper function to add suggestedValue to a field array
        $addSuggestedValue = function (&$field) use ($suggestionsMap) {
            if (is_array($field) && isset($field['slug'])) {
                $field['suggestedValue'] = $suggestionsMap[$field['slug']] ?? null;
            } elseif ($field instanceof \Illuminate\Http\Resources\Json\JsonResource) {
                $fieldArray = $field->toArray(request());
                if (isset($fieldArray['slug'])) {
                    $fieldArray['suggestedValue'] = $suggestionsMap[$fieldArray['slug']] ?? null;
                }
                $field = $fieldArray;
            }
        };

        // Convert fields collection to array and add suggestedValue
        if (isset($formArray['fields'])) {
            if (is_object($formArray['fields']) && method_exists($formArray['fields'], 'map')) {
                $formArray['fields'] = $formArray['fields']->map(function ($field) use ($suggestionsMap) {
                    if ($field instanceof \Illuminate\Http\Resources\Json\JsonResource) {
                        $fieldArray = $field->toArray(request());
                        if (isset($fieldArray['slug'])) {
                            $fieldArray['suggestedValue'] = $suggestionsMap[$fieldArray['slug']] ?? null;
                        }
                        return $fieldArray;
                    } elseif (is_array($field)) {
                        if (isset($field['slug'])) {
                            $field['suggestedValue'] = $suggestionsMap[$field['slug']] ?? null;
                        }
                        return $field;
                    }
                    return (array) $field;
                })->toArray();
            } elseif (is_array($formArray['fields'])) {
                $formArray['fields'] = array_map(function ($field) use ($suggestionsMap) {
                    if ($field instanceof \Illuminate\Http\Resources\Json\JsonResource) {
                        $fieldArray = $field->toArray(request());
                        if (isset($fieldArray['slug'])) {
                            $fieldArray['suggestedValue'] = $suggestionsMap[$fieldArray['slug']] ?? null;
                        }
                        return $fieldArray;
                    } elseif (is_array($field)) {
                        if (isset($field['slug'])) {
                            $field['suggestedValue'] = $suggestionsMap[$field['slug']] ?? null;
                        }
                        return $field;
                    }
                    return (array) $field;
                }, $formArray['fields']);
            }
        }

        // Also add to steps if they exist
        if (isset($formArray['steps'])) {
            // Convert steps to array if it's a collection
            if (is_object($formArray['steps']) && method_exists($formArray['steps'], 'toArray')) {
                $formArray['steps'] = $formArray['steps']->toArray();
            }
            
            if (is_array($formArray['steps'])) {
                foreach ($formArray['steps'] as &$step) {
                    if (!is_array($step)) {
                        continue;
                    }
                    
                    if (isset($step['fields'])) {
                        // Convert fields to array if it's a collection
                        if (is_object($step['fields']) && method_exists($step['fields'], 'toArray')) {
                            $step['fields'] = $step['fields']->toArray();
                        }
                        
                        if (is_array($step['fields'])) {
                            foreach ($step['fields'] as &$field) {
                                if (is_array($field) && isset($field['slug'])) {
                                    $field['suggestedValue'] = $suggestionsMap[$field['slug']] ?? null;
                                }
                            }
                        }
                    }
                }
            }
        }

        return response()->json($formArray);
    }
}
