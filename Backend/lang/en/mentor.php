<?php

return [
    // Registration Messages
    'registration_successful' => 'Registration successful',
    'registration_failed' => 'Registration failed. Please try again later.',
    'admin_registration_subject' => 'New Mentor Registration - Requires Approval',
    'admin_registration_message' => 'A new mentor has registered and requires your approval.',
    'admin_registration_details' => 'Mentor Details: Name: :name, Email: :email, Phone: :phone, Profession: :profession, Experience: :experience, Registration Date: :date',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'profession' => 'Profession',
    'experience' => 'Experience',
    'date' => 'Registration Date',
    // Login Messages
    'login_successful' => 'Login successful',
    'login_failed' => 'Login failed. Please check your credentials.',
    'logged_out_successfully' => 'Logged out successfully',
    
    // OTP Messages
    'otp_code_sent' => 'OTP Code sent successfully',
    'invalid_otp_code' => 'Invalid OTP code',
    'otp_resend_successful' => 'OTP code resent successfully',
    
    // Account Status Messages
    'account_not_activated' => 'Please activate your account via the activation link sent to your email.',
    'account_activated' => 'Account activated successfully',
    'account_not_approved' => 'Your account is pending approval from admin. Please wait for approval before logging in.',
    'account_rejected' => 'Your account request has been rejected by the admin.',
    'account_not_visible' => 'Your account has been deactivated and you cannot access the system.',
    'archived_account' => 'Your account has been archived and you cannot access the system.',
    
    // Validation Messages
    'invalid_credentials' => 'The email or password is not correct.',
    'email_not_found' => 'Email not found.',
    'email_already_exists' => 'This email is already registered.',
    
    // Form Labels
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'password' => 'Password',
    'profession' => 'Profession',
    'experience' => 'Experience',
    'brief' => 'Brief',
    'track' => 'Track',
    'remember_me' => 'Remember Me',
    
    // Placeholders
    'enter_name' => 'Enter your name',
    'enter_email' => 'Enter your email',
    'enter_phone' => 'Enter your phone number',
    'enter_password' => 'Enter your password',
    'enter_profession' => 'Enter your profession',
    'enter_experience' => 'Enter your experience',
    'enter_brief' => 'Enter your brief',
    'select_track' => 'Select a track',
    'enter_otp' => 'Enter OTP code',
    
    // Admin Approval Messages
    'approved_successfully' => 'Mentor approved successfully',
    'rejected_successfully' => 'Mentor rejected successfully',
    'already_processed' => 'This mentor has already been processed',
    'approved_subject' => 'Mentor Registration Approved',
    'approved_message' => 'Dear :name,',
    'approved_details' => 'Congratulations! Your mentor registration has been approved. You can now access all mentorship features and start helping participants.',
    'rejected_subject' => 'Mentor Registration Rejected',
    'rejected_message' => 'Dear :name,',
    'rejected_details' => 'We regret to inform you that your mentor registration has been rejected.',
    'no_reason_provided' => 'No specific reason provided',
    'deactivated_subject' => 'Mentor Account Deactivated',
    'deactivated_message' => 'Dear :name,',
    'deactivated_details' => 'We inform you that your mentor account has been deactivated. You will no longer be visible to participants and cannot access the system. If you have any questions, please contact the administration.',
    
    // Password Reset Messages
    'code_sent' => 'Password reset code sent successfully',
    'password_changed' => 'Password changed successfully',
    'invalid_code' => 'Invalid reset code',
    'code_expired' => 'The reset code has expired. Please request a new code.',
    'reset_password' => 'Reset Password',
    'reset_password_message' => 'You are receiving this email because we received a password reset request for your account.',
    'reset_password_code' => 'Your password reset code is: :code',
    
    // Email Notifications
    'otp_email_subject' => 'Mentor Login Verification Code',
    'otp_email_greeting' => 'Hello!',
    'otp_email_message' => 'You are logging in as a mentor using: :email',
    'otp_email_code' => 'Your verification code is: **:code**',
    'otp_email_expires' => 'This code will expire in 10 minutes.',
    'otp_email_signature' => 'Best regards, :app Platform Team',
    
    // Registration Pending
    'registration_pending_subject' => 'Mentor Registration Pending Approval',
    'registration_pending_message' => 'Dear :name,',
    'registration_pending_details' => 'Thank you for registering as a mentor. Your registration is pending approval. You will receive a notification once your account has been reviewed by our admin team.',
    
    // Auto Credentials
    'auto_credentials_subject' => 'Your Mentor Account Credentials',
    'auto_credentials_greeting' => 'Hello :name!',
    'auto_credentials_intro' => 'A mentor account has been created for you. Please use the following credentials to login:',
    'auto_credentials_email_label' => 'Email',
    'auto_credentials_password_label' => 'Password',
    'auto_credentials_footer' => 'Please keep these credentials secure and do not share them with anyone. We recommend changing your password after your first login.',
    'login_button' => 'Login Now',
    
    // Profile Update Messages
    'profile_updated_successfully' => 'Profile updated successfully',
    'profile_update_failed' => 'Failed to update profile. Please try again.',
    
    // Teams/Participants Messages
    'no_teams_assigned' => 'No teams or participants assigned',
    'failed_to_load_teams' => 'Failed to load assigned teams. Please try again later.',
    'failed_to_load_team_details' => 'Failed to load team details. Please try again later.',
    'failed_to_load_participants' => 'Failed to load participants. Please try again later.',
    'failed_to_load_summary' => 'Failed to load summary. Please try again later.',
    'team_not_found' => 'Team not found or not assigned to you',
    
    // Projects Messages
    'project_not_found' => 'Project not found or not assigned to your teams',
    'failed_to_load_project' => 'Failed to load project details. Please try again later.',
    'failed_to_load_projects' => 'Failed to load projects. Please try again later.',
    
    // Individual Participants Messages
    'no_individual_participants_assigned' => 'No individual participants assigned',
    'failed_to_load_individual_participants' => 'Failed to load individual participants. Please try again later.',
    'participant_not_found' => 'Participant not found or not assigned to you',
    'failed_to_load_participant_details' => 'Failed to load participant details. Please try again later.',
    
    // Team Assignment Messages
    'you_have_been_assigned_to_team' => 'You have been assigned to team: :name',
    'a_mentor_has_been_assigned_to_your_team' => 'A mentor has been assigned to your team: :name',
    'mentor_assigned_to_your_team' => 'Mentor assigned to your team: :name',
    'mentor' => 'Mentor: :name',
    'view_team_details' => 'View Team Details',
    'thank_you_for_being_a_mentor' => 'Thank you for being a mentor!',
    'good_luck_with_your_project' => 'Good luck with your project!',
    'view_mentor' => 'View Mentor',
    'view_mentor_details' => 'View Mentor Details',
    'hello' => 'Hello :name,',
    'a_mentor_has_been_assigned_to_guide_you' => 'A mentor has been assigned to guide you: :name',
    'no_answer' => 'No Answer',
    
    // Participant Assignment Notifications (for mentor)
    'participant_assigned_subject' => 'New Participant Assigned - تم تعيين مشارك جديد',
    'participant_assigned_to_you' => 'A new participant has been assigned to you: :name (:email)',
    'you_can_now_guide_participant' => 'You can now start guiding this participant through their journey.',
    'participant_assigned_title' => 'New Participant Assigned / تم تعيين مشارك جديد',
    'participant_assigned_body' => 'Participant :name (:email) has been assigned to you / تم تعيين المشارك :name (:email) لك',
    'view_participants' => 'View Participants / عرض المشاركين',
    
    // Participant Assignment Notifications (for participant)
    'mentor_assigned_subject' => 'Mentor Assigned - تم تعيين مرشد',
    'mentor_assigned_title' => 'Mentor Assigned / تم تعيين مرشد',
    'mentor_assigned_body' => 'Mentor :name has been assigned to guide you / تم تعيين المرشد :name لتوجيهك',
    'view_dashboard' => 'View Dashboard / عرض لوحة التحكم',
];
