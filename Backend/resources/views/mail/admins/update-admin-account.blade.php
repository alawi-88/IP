@php use Illuminate\Support\Str; @endphp
<x-mail::message>
# Hello {{ $admin->name }},<br>

Your account has been updated successfully, here are the updated details:

- <strong>Name:</strong> {{ $admin->name }}
- <strong>Email:</strong> {{ $admin->email }}
- <strong>Password:</strong> {{ $password }}
- <strong> Role: </strong> {{ $admin->roles_list ?: '-'}}


@component('mail::button', ['url' => config('app.url').'/admin/login'])
Dashboard Link
@endcomponent

</x-mail::message>
