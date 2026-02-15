<?php

use App\Http\Controllers\Api\NafathIamController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CompetitionApplicationController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CompetitionTabController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\GuidelineController;
use App\Http\Controllers\JudgePasswordRecoveryController;
use App\Http\Controllers\JudgeProjectController;
use App\Http\Controllers\Mentor\AvailabilityController;
use App\Http\Controllers\Mentor\ProfileController;
use App\Http\Controllers\Mentor\VideoToolAuthController;
use App\Http\Controllers\Mentor\SessionController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\NationalityController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Participant\ApplicationCommentController;
use App\Http\Controllers\Participant\AuthController as ParticipantAuthController;
use App\Http\Controllers\Participant\LeaderboardController;
use App\Http\Controllers\Participant\ProfileController as ParticipantProfileController;
use App\Http\Controllers\Participant\SessionController as ParticipantSessionController;
use App\Http\Controllers\Judge\AuthController as JudgeAuthController;
use App\Http\Controllers\Judge\NotificationController as JudgeNotificationController;
use App\Http\Controllers\Judge\ProfileController as JudgeProfileController;
use App\Http\Controllers\Mentor\AuthController as MentorAuthController;
use App\Http\Controllers\Mentor\PasswordController as MentorPasswordController;
use App\Http\Controllers\Mentor\TeamController as MentorTeamController;
use App\Http\Controllers\Participant\EmailVerificationController;
use App\Http\Controllers\Participant\NotificationController;
use App\Http\Controllers\Participant\PasswordController;
use App\Http\Controllers\Participant\ProjectCommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegistrationConfigController;
use App\Http\Controllers\SatisfactionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JudgeDisclaimerController;
use App\Http\Controllers\WinnerController;
use App\Models\BrandingSetting;
use App\Filament\Pages\BrandingSettings;
use App\Filament\Pages\LandingPage;
use Illuminate\Http\Request;

Route::middleware(['throttle:api'])->group(function () {

Route::prefix('participants')->group(function () {

    Route::middleware('throttle:auth')->group(function () {
        // Authentication
        Route::post('auth/register', [ParticipantAuthController::class, 'register']);
        Route::post('activate-account', [ParticipantAuthController::class, 'activateAccount']);
        Route::post('auth/login', [ParticipantAuthController::class, 'login']);
        Route::post('resend-otp', [ParticipantAuthController::class, 'resendOtp'])->middleware('throttle:4,1');

        // Password recovery
        Route::post('forgot-password', [PasswordController::class, 'forgot'])->middleware('guest');
        Route::post('reset-password', [PasswordController::class, 'reset'])->middleware('guest')->name('password.reset');
        Route::post('check-password-reset-code', [PasswordController::class, 'checkPasswordResetCode']);

    });

    Route::middleware(['jwt.auth'])->group(function () {
        // Email Verification
        Route::middleware(['verified'])->group(function () {

            Route::apiResource('notifications', NotificationController::class);
            Route::post('notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
            Route::post('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
            Route::post('activate', [ParticipantAuthController::class, 'activate']);
        });

    });

    Route::middleware(['jwt.auth', 'validate.user.type:participant'])->group(function () {
        // Email Verification
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
        Route::post('/email/verify/resend', [EmailVerificationController::class, 'resend']);
        Route::middleware(['verified'])->group(function () {
            Route::apiSingleton('profile', ParticipantProfileController::class);

            // Recovery email verification
            Route::post('profile/recovery-email/request-otp', [ParticipantProfileController::class, 'requestRecoveryEmailOtp']);
            Route::post('profile/recovery-email/verify-otp', [ParticipantProfileController::class, 'verifyRecoveryEmailOtp']);

            // Competition Applications
            Route::apiResource('competition-applications', CompetitionApplicationController::class)->missing(function () {
                return response()->json(['message' => 'Application not found'], 404);
            });
            Route::post('competition-applications/reset-draft', [CompetitionApplicationController::class, 'resetDraft'])->name('competition-applications.reset-draft');

                Route::get('projects/{project}/comments', [ProjectCommentController::class, 'index']);
                Route::post('projects/{project}/comments', [ProjectCommentController::class, 'store']);
                Route::post('projects/{project}/comments/mark-read', [ProjectCommentController::class, 'markRead']);

                Route::get('applications/{application}/comments', [ApplicationCommentController::class, 'index']);
                Route::post('applications/{application}/comments', [ApplicationCommentController::class, 'store']);
                Route::post('applications/{application}/comments/mark-read', [ApplicationCommentController::class, 'markRead']);

            // For Approved Competition Applications
            Route::middleware(['approved_competition_application', 'approved_competition_tab'])->group(function () {
                Route::apiResource('events', EventController::class)->only(['index', 'show']);

                // Mentor Routes
                Route::apiResource('mentors', MentorController::class)->only(['index', 'show']);

                // Alias route for mentor sessions (must be before parameterized routes)
                Route::get('mentors/{mentorId}/available-slots', [ParticipantSessionController::class, 'getAvailableSlots'])->name('mentors.available-slots');
                Route::get('mentors/{mentorId}/mentor-sessions', [ParticipantSessionController::class, 'indexByMentor'])->name('mentors.mentor-sessions.index');
                Route::post('mentors/{mentorId}/mentor-sessions', [ParticipantSessionController::class, 'store'])->name('mentors.mentor-sessions.store');

                // Mentor Session Routes (global access by session ID)
                Route::prefix('mentor-sessions')->name('mentor-sessions.')->group(function () {
                    Route::get('/', [ParticipantSessionController::class, 'index'])->name('index');
                    Route::get('{session}', [ParticipantSessionController::class, 'show'])->name('show');
                    Route::put('{session}/reschedule', [ParticipantSessionController::class, 'reschedule'])->name('reschedule');
                    Route::post('{session}/cancel', [ParticipantSessionController::class, 'cancel'])->name('cancel');
                })
                ->where('session', '[0-9]+')
                ->missing(function () {
                    return response()->json([
                        'success' => false,
                        'message' => __('sessions.session_not_found'),
                    ], 404);
                });
                Route::put('teams/{team}/mark-as-completed', [TeamController::class, 'markAsCompleted'])->name('teams.mark-as-completed');
                Route::apiResource('teams', TeamController::class)
                    ->missing(fn() => response()->json(['message' => 'Team not found'], 404));

                Route::put('teams/{team}/members', [TeamController::class, 'updateTeamMembers'])->name('teams.members.update');
                Route::delete('teams/{team}/members', [TeamController::class, 'deleteTeamMembers'])->name('teams.members.delete');

                Route::get('projects/is-submitted', [ProjectController::class, 'isSubmitted'])->name('projects.is-submitted');
                Route::post('projects/reset-draft', [ProjectController::class, 'resetDraft'])->name('projects.reset-draft');
                Route::apiResource('projects', ProjectController::class)->only(['index', 'store', 'show'])
                    ->missing(fn() => response()->json(['message' => 'Project not found'], 404));

                Route::apiSingleton('my-team', TeamController::class);
                Route::get('my-competition-applications', [CompetitionApplicationController::class, 'myApplications']);
                Route::apiResource('guidelines', GuidelineController::class)->only(['index', 'show']);
                Route::get('winners', [WinnerController::class, 'index'])->name('winners.index');
                Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
            });
        });

        Route::get('satisfactions/is-submitted', [SatisfactionController::class, 'isSubmitted']);
        Route::apiResource('satisfactions', SatisfactionController::class)->only(['index', 'store']);
        Route::get('competition-tabs', CompetitionTabController::class);

        Route::post('auth/logout', [ParticipantAuthController::class, 'logout']);
    });
});

// Contact Us routes
Route::middleware(['jwt.auth'])->group(function () {
    Route::get('contact-us/is-submitted', [ContactUsController::class, 'isSubmitted']);
    Route::middleware('throttle:contact')->group(function () {
        Route::apiResource('contact-us', ContactUsController::class)->only(['index', 'store', 'show']);
    });
});

Route::middleware(['jwt.auth', 'validate.user.type:judge'])->group(function () {
    Route::prefix('judge')->group(function () {
        Route::get('/disclaimer-status', [JudgeDisclaimerController::class, 'checkStatus']);
        Route::post('/accept-disclaimer', [JudgeDisclaimerController::class, 'acceptDisclaimer']);
    });
});

Route::prefix('judges')->group(function () {

    Route::middleware('throttle:auth')->group(function () {
        Route::post('auth/login', [JudgeAuthController::class, 'login']);
        Route::post('auth/register', [JudgeAuthController::class, 'register']);
        Route::post('activate-account', [JudgeAuthController::class, 'activateAccount']);
        Route::post('resend-activation', [JudgeAuthController::class, 'resendActivation'])
            ->name('verification.resend');
        Route::post('forget-password', [JudgePasswordRecoveryController::class, 'forgetPassword']);
        Route::post('reset-password', [JudgePasswordRecoveryController::class, 'resetPassword']);
        Route::post('check-password-reset-code', [JudgePasswordRecoveryController::class, 'checkPasswordResetCode']);

    });

    Route::post('resend-otp', [JudgeAuthController::class, 'resendOtp'])->middleware('throttle:4,1');

    Route::middleware(['jwt.auth', 'validate.user.type:judge'])->group(function () {
        Route::apiSingleton('profile', JudgeProfileController::class);
        Route::apiResource('projects', JudgeProjectController::class)->only(['index', 'show']);
        Route::apiResource('evaluations', EvaluationController::class)->only(['index', 'store']);

        Route::get('notifications', [JudgeNotificationController::class, 'index']);
        Route::post('notifications/{notification}/mark-as-read', [JudgeNotificationController::class, 'markAsRead']);
        Route::post('notifications/mark-all-as-read', [JudgeNotificationController::class, 'markAllAsRead']);

        // contact us
        Route::post('auth/logout', [JudgeAuthController::class, 'logout']);
    });
});

Route::prefix('mentors')->group(function () {

    Route::middleware('throttle:auth')->group(function () {
        Route::post('auth/login', [MentorAuthController::class, 'login']);
        Route::post('auth/register', [MentorAuthController::class, 'register']);
    });

    Route::post('resend-otp', [MentorAuthController::class, 'resendOtp'])->middleware('throttle:4,1');
    Route::post('forgot-password', [MentorPasswordController::class, 'forgot'])->middleware('throttle:forgot-password');
    Route::post('reset-password', [MentorPasswordController::class, 'reset'])->middleware('guest')->name('mentor.password.reset');

    Route::post('check-password-reset-code', [MentorPasswordController::class, 'checkPasswordResetCode']);

    Route::middleware(['jwt.auth', 'validate.user.type:mentor'])->group(function () {
        Route::post('auth/logout', [MentorAuthController::class, 'logout']);

        // Profile management (use POST for multipart/form-data compatibility)
        Route::get('profile', [ProfileController::class, 'show']);
        Route::post('profile', [ProfileController::class, 'update']);

        // Availability management
        Route::get('availability', [AvailabilityController::class, 'index']);
        Route::get('availability/date/{date}', [AvailabilityController::class, 'showForDate']);
        Route::post('availability', [AvailabilityController::class, 'store']);
        Route::put('availability/{availability}', [AvailabilityController::class, 'update']);
        Route::delete('availability/{availability}', [AvailabilityController::class, 'destroy']);

        // Video Tool Integration Routes
        Route::prefix('video-tools')->group(function () {
            Route::get('integrations', [VideoToolAuthController::class, 'getIntegrations']);
            Route::post('authorize', [VideoToolAuthController::class, 'getAuthorizationUrl']);
            Route::post('set-default', [VideoToolAuthController::class, 'setDefault']);
            Route::post('disconnect', [VideoToolAuthController::class, 'disconnect']);
            Route::post('refresh-token', [VideoToolAuthController::class, 'refreshToken']);
        });

        // Session Management Routes
        Route::prefix('sessions')->group(function () {
            Route::get('/', [SessionController::class, 'index']);
            Route::get('history', [SessionController::class, 'history']);
            Route::get('pending-requests', [SessionController::class, 'pendingRequests']);
            Route::get('available-slots', [SessionController::class, 'getAvailableSlots']);
            Route::post('/', [SessionController::class, 'store']);
            Route::get('{session}', [SessionController::class, 'show']);
            Route::put('{session}', [SessionController::class, 'update']);
            Route::post('{session}/accept', [SessionController::class, 'accept']);
            Route::post('{session}/decline', [SessionController::class, 'decline']);
            Route::post('{session}/propose-new-time', [SessionController::class, 'proposeNewTime']);
            Route::post('{session}/cancel', [SessionController::class, 'cancel']);
            Route::post('{session}/start', [SessionController::class, 'start']);
            Route::post('{session}/end', [SessionController::class, 'end']);
            Route::post('{session}/feedback', [SessionController::class, 'feedback']);
            Route::post('{session}/mark-no-show', [SessionController::class, 'markNoShow']);
        });

        // Assigned Teams/Participants Management Routes
        Route::prefix('teams')->name('mentor.teams.')->group(function () {
            Route::get('/', [MentorTeamController::class, 'index'])->name('index');
            Route::get('summary', [MentorTeamController::class, 'summary'])->name('summary');
            Route::get('participants', [MentorTeamController::class, 'participants'])->name('participants');
            Route::get('projects', [MentorTeamController::class, 'projects'])->name('projects');
            Route::get('projects/{project}', [MentorTeamController::class, 'showProject'])->name('projects.show');
            Route::get('{team}', [MentorTeamController::class, 'show'])->name('show');
        });
    });
});


// Public routes
Route::get('social-links', SocialController::class);
Route::get('pages/{page:slug}', [PageController::class, 'show']);
Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{id}', [ServiceController::class, 'show']);

// Nafath SSO routes
Route::prefix('nafath')->group(function () {
    Route::get('status', [App\Http\Controllers\NafathAuthController::class, 'status']);
    Route::post('login', [App\Http\Controllers\NafathAuthController::class, 'initiateLogin']);
    Route::get('callback', [App\Http\Controllers\NafathAuthController::class, 'callback']);
    Route::get('login-methods', function () {
        $settings = \App\Models\NafathSettings::current();
        return response()->json([
            'nafath_available' => $settings->isNafathLoginAvailable(),
            'regular_available' => $settings->isRegularLoginAvailable(),
            'login_method' => $settings->login_method ?? 'both',
        ]);
    });
});

// Video Tool OAuth Callback Routes (Public)
Route::prefix('mentor/video-tools')->group(function () {
    Route::get('zoom/callback', [VideoToolAuthController::class, 'handleCallback']);
    Route::get('teams/callback', [VideoToolAuthController::class, 'handleCallback']);
    Route::get('google/callback', [VideoToolAuthController::class, 'handleCallback']);
});


Route::middleware(['jwt.auth'])->group(function () {

    Route::prefix('forms')->group(function () {
        Route::get('registration', [FormController::class, 'registration']);
        Route::get('projects', [FormController::class, 'projects']);
        Route::get('projects/form', [FormController::class, 'projects_form']);
        Route::get('evaluations', [FormController::class, 'evaluations']);
        Route::get('evaluations/form', [FormController::class, 'evaluations_form']);
        Route::get('field-types', [FormController::class, 'fieldTypes']);
        Route::get('registration-config', RegistrationConfigController::class);
        Route::get('team-form-config', [FormController::class, 'team_form_config']);
        Route::get('projects-form-config', [FormController::class, 'projects_form_config']);
        Route::get('evaluations/stages', [FormController::class, 'evaluations_stages']);
        Route::post('enhance', [FormController::class, 'enhance']);
    });

    Route::apiResource('competitions', CompetitionController::class)->only(['index', 'show'])->missing(function () {
        return response()->json(['message' => 'Competition not found'], 404);
    });
});
// countries
Route::get('countries', CountryController::class);
Route::get('nationalities', NationalityController::class);
Route::get('cities', CityController::class);


Route::get('/branding-settings', [BrandingSettings::class, 'get']);
Route::get('/landing-page', [LandingPage::class, 'show']);

// Nafath IAM API routes
Route::prefix('nafath-iam')->group(function () {
    Route::get('/test-connection', [NafathIamController::class, 'testConnection']);
    Route::get('/token', [NafathIamController::class, 'getToken']);
    Route::post('/clear-cache', [NafathIamController::class, 'clearTokenCache']);
    Route::post('/test-request', [NafathIamController::class, 'testApiRequest']);
});

});
