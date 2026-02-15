<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'invalid_credentials' => 'The email or password is not correct.',
    'invalid_otp_code' => 'Invalid OTP code.',
    'invalid_code' => 'Invalid code.',
    'password_changed' => 'Password changed successfully.',
    'otp_code_sent' => 'OTP Code sent successfully.',
    'code_sent' => 'Code sent successfully.',
    'invalid_email' => 'The email address or password is incorrect.',
    'logged_out' => 'Logged out successfully.',
    'email_not_found' => 'Email not found.',
    'account_not_activated' => 'Please activate your account via the activation link sent to your email.',
    'invalid_activation_code' => 'Invalid activation code',
    'account_activated' => 'Account activated successfully',
    'activation_subject' => 'Welcome to :app - Account Activation',
    'activation_greeting' => 'Welcome :name,',
    'activation_intro' => 'Thank you for registering with the :app. To activate your account, please click the button below:',
    'activate_account' => 'Activate Account',
    'activation_copy_link' => 'Or copy and paste this link into your browser: :url',
    'activation_expiry' => 'This link will expire in 24 hours.',
    'activation_ignore' => "If you didn't create this account, please ignore this email.",
    'activation_signoff' => 'Best regards, :app Team',
    'registration_failed' => 'Registration failed. Please try again later.',
    'account_created' => 'Account created successfully! Please activate your account through the link sent to your email address.',
    'activation_link_resent' => 'Activation link resent successfully. Please check your email.',
    'already_verified' => 'Your email is already verified.',
    'activation_link_invalid' => 'The activation link is invalid or has expired. Please request a new activation link.',
    'archived_account' => 'Your account has been archived and you cannot access the system.',
    
    // Login OTP Messages
    'login_otp_subject' => 'Login Verification Code',
    'login_otp_greeting' => 'Hello!',
    'login_otp_message' => 'You are logging in using: :email',
    'login_otp_code' => 'Your verification code is: **:code**',
    'login_otp_expires' => 'This code will expire in 10 minutes.',
    'login_otp_signature' => 'Best regards, :app Team',
];
