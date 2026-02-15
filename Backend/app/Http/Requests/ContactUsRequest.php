<?php

namespace App\Http\Requests;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'regex:/^[\pL\s\d]+$/u'],
            'message' => ['required', 'string', 'max:500'],
            'attachments' => ['array'],
            'attachments.*' => ['file','mimes:pdf,jpg,png,docx','max:5120'],
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
            'title.alpha_num' => __('contact_us.title.alpha_num'),
            'message.max' => __('contact_us.message.max'),
            'attachments.*.file' => __('contact_us.attachments.mimes'),
            'attachments.*.max' => __('contact_us.attachments.max'),
        ];
    }
}
