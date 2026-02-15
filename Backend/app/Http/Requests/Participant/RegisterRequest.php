<?php

namespace App\Http\Requests\Participant;

use App\Rules\ReCaptcha;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'g-recaptcha-response' => ['bail', 'required', new ReCaptcha()],
            'name' => ['required', 'regex:/^[\p{L} ]+$/u', 'min:2'],
            'email' => ['required', 'email', 'max:255', 'unique:participants,email'],
            'password' => ['required', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*_-]).{12,}$/'],
            'phone' => ['required', 'numeric', 'unique:participants,phone'],
            'gender' => ['required', 'in:male,female,Male,Female'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . now()->subYears(10)->format('Y-m-d')],
            'nationality_id' => ['required', 'exists:nationalities,id'],
            'country_id' => ['required', 'exists:countries,id'],
            'residence_city_id' => ['required', 'exists:cities,id'],
            'educational_background' => ['required', 'in:high_school,diploma,bachelor,master,phd'],
            'current_role' => ['required', 'in:high_school_student,university_student,recently_graduated,private_sector_employee,government_sector_employee,non_profit_sector_employee,freelancer,unemployed'],
            'place_of_work_study' => ['nullable', 'string', 'max:255'],
            'years_of_experience' => ['required', 'in:less_than_one,one_to_three,three_to_five,five_to_ten,more_than_ten,no_experience'],
            'experience_or_skills' => ['nullable', 'string', 'max:300'],
            'key_achievements' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => __('validation.required'),
            'string' => __('validation.string'),
            'max' => __('validation.max', ['max' => ':max']),
            'regex' => __('validation.regex'),

            'name.regex' => __('validation.name.regex'),
            'name.min' => __('validation.name.min'),

            'email' => __('validation.email.format'),
            'unique' => __('validation.email.unique'),

            'phone.regex' => __('validation.phone.regex'),
            'phone.unique' => __('validation.phone.unique'),

            'date_of_birth.date_format' => __('validation.date_of_birth.format'),
            'date_of_birth.before_or_equal' => __('validation.date_of_birth.age'),

            'password.regex' => __('validation.password.regex'),
            'password.min' => __('validation.password.min'),
            'password.confirmed' => __('validation.password.confirmed'),
        ];
    }
}
