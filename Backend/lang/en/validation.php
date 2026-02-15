<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute field must be accepted.',
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.',
    'active_url' => 'The :attribute field must be a valid URL.',
    'after' => 'The :attribute field must be a date after :date.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
    'alpha' => 'The :attribute field must only contain letters.',
    'alpha_dash' => 'The :attribute field must only contain letters, numbers, dashes, and underscores.',
    'alpha_num' => 'The :attribute field must only contain letters and numbers.',
    'array' => 'The :attribute field must be an array.',
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
    'before' => 'The :attribute field must be a date before :date.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute field must have between :min and :max items.',
        'file' => 'The :attribute field must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute field must be between :min and :max.',
        'string' => 'The :attribute field must be between :min and :max characters.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'can' => 'The :attribute field contains an unauthorized value.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'contains' => 'The :attribute field is missing a required value.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute field must be a valid date.',
    'date_equals' => 'The :attribute field must be a date equal to :date.',
    'date_format' => 'The :attribute field must match the format :format.',
    'decimal' => 'The :attribute field must have :decimal decimal places.',
    'declined' => 'The :attribute field must be declined.',
    'declined_if' => 'The :attribute field must be declined when :other is :value.',
    'different' => 'The :attribute field and :other must be different.',
    'digits' => 'The :attribute field must be :digits digits.',
    'digits_between' => 'The :attribute field must be between :min and :max digits.',
    'dimensions' => 'The :attribute field has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.',
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.',
    'ends_with' => 'The :attribute field must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.',
    'file' => 'The :attribute field must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.',
        'file' => 'The :attribute field must be greater than :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than :value.',
        'string' => 'The :attribute field must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.',
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than or equal to :value.',
        'string' => 'The :attribute field must be greater than or equal to :value characters.',
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.',
    'image' => 'The :attribute field must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field must exist in :other.',
    'integer' => 'The :attribute field must be an integer.',
    'ip' => 'The :attribute field must be a valid IP address.',
    'ipv4' => 'The :attribute field must be a valid IPv4 address.',
    'ipv6' => 'The :attribute field must be a valid IPv6 address.',
    'json' => 'The :attribute field must be a valid JSON string.',
    'list' => 'The :attribute field must be a list.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.',
        'file' => 'The :attribute field must be less than :value kilobytes.',
        'numeric' => 'The :attribute field must be less than :value.',
        'string' => 'The :attribute field must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute field must not have more than :value items.',
        'file' => 'The :attribute field must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be less than or equal to :value.',
        'string' => 'The :attribute field must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.',
    'max_digits' => 'The :attribute field must not have more than :max digits.',
    'mimes' => 'The :attribute field must be a file of type: :values.',
    'mimetypes' => 'The :attribute field must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute field must have at least :min items.',
        'file' => 'The :attribute field must be at least :min kilobytes.',
        'numeric' => 'The :attribute field must be at least :min.',
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.',
    'missing' => 'The :attribute field must be missing.',
    'missing_if' => 'The :attribute field must be missing when :other is :value.',
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.',
    'missing_with' => 'The :attribute field must be missing when :values is present.',
    'missing_with_all' => 'The :attribute field must be missing when :values are present.',
    'multiple_of' => 'The :attribute field must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute field format is invalid.',
    'numeric' => 'The :attribute field must be a number.',
    'present' => 'The :attribute field must be present.',
    'present_if' => 'The :attribute field must be present when :other is :value.',
    'present_unless' => 'The :attribute field must be present unless :other is :value.',
    'present_with' => 'The :attribute field must be present when :values is present.',
    'present_with_all' => 'The :attribute field must be present when :values are present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.',
    'required_if_declined' => 'The :attribute field is required when :other is declined.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute field must match :other.',
    'size' => [
        'array' => 'The :attribute field must contain :size items.',
        'file' => 'The :attribute field must be :size kilobytes.',
        'numeric' => 'The :attribute field must be :size.',
        'string' => 'The :attribute field must be :size characters.',
    ],
    'starts_with' => 'The :attribute field must start with one of the following: :values.',
    'timezone' => 'The :attribute field must be a valid timezone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'uppercase' => 'The :attribute field must be uppercase.',
    'url' => 'The :attribute field must be a valid URL.',
    'ulid' => 'The :attribute field must be a valid ULID.',
    'uuid' => 'The :attribute field must be a valid UUID.',

    'required' => 'The :attribute field is required.',
    'string' => 'This :attribute field must be a string.',
    'max' => [
        'numeric' => 'The :attribute field must not be greater than :max.',
        'string' => 'The maximum length for this :attribute field is :max characters.',
    ],
    'regex' => 'The format is incorrect.',

    'name' => [
        'regex' => 'The name field does not accept symbols or numbers.',
        'min' => 'The name field must be at least 2 characters long.',
    ],

    'email' => [
        'format' => 'The email format is incorrect.',
        'unique' => 'Email already registered. Try logging in or use another email.',
        'exists' => 'If this email is registered, you will receive an OTP',
    ],

    'phone' => [
        'regex' => 'The phone number entered is not correct.',
        'unique' => 'The phone number is already registered.',
        'numeric' => 'The phone number must be a number.',
        'digits_between' => 'The phone number must be between 8 and 15 digits.',
    ],
    'experience_field' => [
        'regex' => 'The professional background field does not accept symbols or numbers.',
        'min' => 'The professional background field must be at least 2 characters long.',
    ],
    'date_of_birth' => [
        'format' => 'The date format is incorrect.',
        'age' => 'To register on the platform, the age must be at least 10 years old.',
    ],

    'password' => [
        'regex' => 'The password must contain at least 12 characters, including an uppercase letter, a lowercase letter, a number, and a symbol.',
        'confirmed' => 'Password does not match',
    ],

    'gender' => [
        'in' => 'The gender must be either male or female.',
    ],

    'educational_background' => [
        'in' => 'The selected educational background is invalid.',
    ],

    'current_role' => [
        'in' => 'The selected current role is invalid.',
    ],

    'place_of_work_study' => [
        'string' => 'The place of work/study must be a string.',
        'max' => 'The place of work/study must not exceed 255 characters.',
    ],

    'years_of_experience' => [
        'in' => 'The selected years of experience are invalid.',
    ],

    'experience_or_skills' => [
        'string' => 'The experience or skills must be a string.',
        'max' => 'The experience or skills must not exceed 300 characters.',
    ],

    'key_achievements' => [
        'string' => 'The key achievements must be a string.',
        'max' => 'The key achievements must not exceed 300 characters.',
    ],

    'summary' => [
        'max' => 'Maximum summary length is 150 characters',
    ],

    'description' => [
        'max' => 'Maximum description length is 800 characters',
    ],

    'presentation_file' => [
        'mimes' => 'The attached file must be in PDF or PowerPoint format',
        'max' => 'Uploaded files must not exceed 30 MB'
    ],

    'link' => [
        'url' => 'Please enter only a URL.',
    ],

    'references' => [
        'url' => 'Please enter only a URL.',
    ],

    'documents' => [
        'max' => 'Uploaded files must not exceed 100 MB',
    ],

    'recaptcha' => [
        'required' => 'The reCAPTCHA code is required.',
        'failed' => 'Failed to validate reCAPTCHA.',
        'score_too_low' => 'The reCAPTCHA score is too low.',
    ],
    'linkedin' => [
        'url' => 'The LinkedIn field must be a valid URL.',
        'max' => 'The LinkedIn field must not exceed 255 characters.',
    ],
    'facebook' => [
        'url' => 'The Facebook field must be a valid URL.',
        'max' => 'The Facebook field must not exceed 255 characters.',
    ],
    'instagram' => [
        'url' => 'The Instagram field must be a valid URL.',
        'max' => 'The Instagram field must not exceed 255 characters.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'evaluation_criteria.*.weight' => [
            'max' => 'The main criterion weight cannot exceed 100%.',
        ],
        'evaluation_criteria.*.subcriteria.*.weight' => [
            'max' => 'The sub-criterion weight cannot exceed 100%.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'evaluation_criteria.*.weight' => 'Main Criterion Weight',
        'evaluation_criteria.*.subcriteria.*.weight' => 'Sub-criterion Weight',
    ],

];
