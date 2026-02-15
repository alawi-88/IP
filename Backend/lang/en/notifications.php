<?php

return [
    'hello' => 'Hello :name',
    'salutation' => 'Best regards, Foundry',
    // Session Notifications
    'session_scheduled_subject' => 'New Session Scheduled',
    'session_updated_subject' => 'Session Updated',
    'session_rescheduled_subject' => 'Session Rescheduled',
    'session_cancelled_subject' => 'Session Cancelled',
    'session_reminder_subject' => 'Session Reminder',
    'session_scheduled_message' => 'A new session  has been scheduled for :date at :time (:duration)',
    'session_updated_message' => 'Session  has been updated',
    'session_rescheduled_message' => 'Your session has been rescheduled to :new_date at :new_time',
    'session_rescheduled_message_new' => 'Your session has been rescheduled from ',
    'session_cancelled_message' => 'Session  scheduled for :date at :time has been cancelled',
    'session_reminder_message' => 'This is a friendly reminder that you have a session  scheduled for :date at :time (:duration)',
    'session_description' => 'Session Description:',
    'session_scheduled_footer' => 'Please make sure to join the session on time.',
    'session_updated_footer' => 'Please check the updated details.',
    'session_rescheduled_footer' => 'Please make sure to join the session at the new time.',
    'session_cancelled_footer' => 'If you have any questions, please contact support.',
    'session_reminder_footer' => 'This is a friendly reminder about your upcoming session.',
    'join_session' => 'Join Session',
    'cancellation_reason' => '',
    'session_time_changed' => 'Session time changed to :new_date',
    'session_duration_changed' => 'Session duration changed to :new_duration',
    'session_title_changed' => 'Session title changed to ":new_title"',
    'previous_time' => 'Previous Time',
    'new_time' => 'New Time',
    'previous_duration' => 'Previous Duration',
    'new_duration' => 'New Duration',
    'previous_title' => 'Previous Title',
    'new_title' => 'New Title',
    'session_details' => 'Session Details:',
    'session_with_participant' => 'Session with participant: :name',
    'session_with_mentor' => 'Session with mentor: :name',
    
    // Feedback Notifications
    'session_feedback_submitted_subject' => 'Session Feedback Submitted',
    'session_feedback_submitted_message' => 'Feedback has been submitted for your session.',
    'session_feedback_admin_subject' => 'Session Feedback Received',
    'session_feedback_admin_message' => 'Feedback has been submitted for a session.',
    'view_in_portal' => 'View in Portal',
    'view_in_panel' => 'View in Admin Panel',
    
    // New Booking Notifications
    'new_booking_subject' => 'New Session Booking Request',
    'new_booking_message' => ':participant_name has requested a session with you.',
    'new_booking_footer' => 'Please review the request and respond by accepting, declining, or proposing a new time.',
    'participant_information' => 'Participant Information:',
    'mentor_information' => 'Mentor Information:',
    'full_name_label' => 'Full Name',
    'email_label' => 'Email',
    'session_title_label' => 'Note',
    'competition_label' => 'Program',
    'program_label' => 'Program',
    'session_date_label' => 'Date',
    'session_time_label' => 'Time',
    'session_duration_label' => 'Duration',
    'booking_id_label' => 'Booking ID',
    'note_label' => 'Note:',
    
    // Session Accepted Notifications
    'session_accepted_subject' => 'Session Request Accepted',
    'session_accepted_message' => ':mentor_name has accepted your session request. Date: :date, Time: :time, Duration: :duration',
    'session_accepted_footer' => 'Your session has been confirmed. Please make sure to join on time.',
    
    // Session Declined Notifications
    'session_declined_subject' => 'Session Request Declined',
    'session_declined_message' => ':mentor_name has declined your session request. Title: ":title", Date: :date, Time: :time',
    'session_declined_footer' => 'You can try booking another session with this mentor or choose a different mentor.',
    'decline_reason_label' => 'Reason provided by mentor:',
    
    // New Time Proposed Notifications
    'new_time_proposed_subject' => 'New Time Proposed for Session',
    'new_time_proposed_message' => ':mentor_name has proposed a new time for your session request. Title: ":title", Original Time: :original_date at :original_time, Proposed Time: :proposed_date at :proposed_time, Duration: :duration',
    'new_time_proposed_instructions' => 'Please review the proposed time and respond by accepting or declining the new time.',
    'new_time_proposed_footer' => 'Please respond to the mentor\'s proposed time.',
    
    // Time Units
    'hour' => 'h',
    'minute' => 'm',
    'hour_full' => 'hour',
    'minute_full' => 'minute',
    'hours' => 'hours',
    'minutes' => 'minutes',
];

