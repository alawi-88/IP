<?php

return [
    // API Messages
    'otp_sent' => 'OTP sent to your recovery email. Please check your inbox.',
    'send_failed' => 'Failed to send OTP. Please try again.',
    'successfully_added' => 'Recovery email has been successfully added to your profile.',
    'invalid_otp' => 'Invalid OTP code. Please try again.',
    
    // Validation Messages
    'already_in_use' => 'This email is already in use.',
    'must_be_different' => 'Recovery email cannot be the same as primary email.',
    
    // Email Notification Messages
    'email_subject' => 'Recovery Email Verification Code',
    'greeting' => 'Hello!',
    'adding_recovery_email' => 'You are adding a recovery email to your account.',
    'verification_code' => 'Your verification code is: **:code**',
    'confirm_email' => 'Please enter this code to confirm your recovery email: :email',
    'code_expires' => 'This code will expire in 1 minute.',
    'ignore_if_not_requested' => 'If you did not request this verification, please ignore this email.',
    'signature' => 'Best regards, :app Platform Team',
];
