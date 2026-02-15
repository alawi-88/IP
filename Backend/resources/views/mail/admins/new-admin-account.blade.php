<x-mail::message>
# Hello {{ $admin->name }},<br>

Your account has been created successfully with the following details:

- <strong>Email:</strong> {{ $admin->email }}
- <strong>Password:</strong> {{ $password }}
- <strong> Role: </strong> {{ $admin->roles_list ?: '-'}}


    You can login to your account using the email and password provided above. You can change your password from login page using the "Forgot Password" link.

@component('mail::button', ['url' => config('app.url').'/admin/login'])
    Dashboard Link
@endcomponent

</x-mail::message>
