@component('mail::message')
@php
use App\Models\EmailTemplate;
$lang = app()->getLocale(); // أو من user->lang
$template = EmailTemplate::where('key', 'judge.otp_login')->first();
$body    = $template?->body[$lang] ?? 'Your OTP code is: {{otpCode}}';
$body = str_replace('{{otpCode}}', $otpCode, $body ?? 'Your OTP code is: {{otpCode}}');
@endphp
{!! $body !!} 
@endcomponent
