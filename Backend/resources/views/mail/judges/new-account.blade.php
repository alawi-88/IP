@component('mail::message')

    @php
        $name = is_array($judge->name) ? $judge->name[app()->getLocale()] ?? reset($judge->name) : $judge->name;
    @endphp

    # Hello {{ $name }},<br>

    Your account has been created successfully with the following details:

    - <strong>Email:</strong> {{ $judge->email }}
    - <strong>Password:</strong> {{ $password }}

    You can login to your account using the email and password provided above.

    @component('mail::button', ['url' => config('app.url') . '/' . app()->getLocale() . '/judge/login'])
        Filmathon - Judge Login
    @endcomponent

@endcomponent
