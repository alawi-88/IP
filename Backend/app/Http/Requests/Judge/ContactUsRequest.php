<?php

namespace App\Http\Requests\Judge;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactUsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{N}\s]+$/u'],
            'message' => ['required', 'string', 'max:500'],
            'attachments' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,docx'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.regex' => __('contact_us.title.regex'),
            'message.max' => __('contact_us.message.max'),
            'attachments.file' => __('contact_us.attachments.mimes'),
            'attachments.max' => __('contact_us.attachments.max'),
        ];
    }
}
