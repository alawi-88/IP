<?php

namespace App\Traits\Notifications;


use Modules\ConfigModule\Entities\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\UserModule\Entities\User;


trait SendingNotificationsService
{
    public function sendNotification($user_id,$title,$body,$type,$redirect_id){

        if (empty($user_id)) return 404;

        $user = User::find($user_id);

        if (empty($user)) return 404;

        // Convert single user to array format for consistent handling

            $fcmToken = $user->fcm_token;
            $title = $title;
            $content = $body;
            $notification_id = $user_id;
            $click_action = $type;
            $deviceType = $user->device_type;


            // Check FCM token validity
            if (empty($fcmToken[0])) {
                Log::error('No FCM token provided.');
                return 'No FCM token provided.';
            }


            try {
                // Get the access token
                $fcmTokenManager = new FCMTokenManager();
                $accessToken = $fcmTokenManager->getAccessToken();
                if (!$accessToken) {
                    Log::error('Failed to obtain FCM access token.');
                    return 'Failed to obtain access token.';
                }


                // Build the notification payload
                $info = $this->buildNotificationPayload($title, $content, $redirect_id, $notification_id, $click_action, $fcmToken, $deviceType);

                // Set cURL headers
                $headers = [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ];

                // Send the notification
                return $this->sendCurlRequest('https://fcm.googleapis.com/v1/projects/innovation-demo-staging/messages:send', json_encode($info), $headers);

            } catch (\Exception $exception) {
                Log::error('FCM Error: ' . $exception->getMessage());
                return 'An error occurred while sending through Firebase.';
            }

    }

    private function buildNotificationPayload($title, $content, $redirect_id, $notification_id, $click_action, $fcmToken, $deviceType, $badge = 1)
    {
        // Ensure $fcmToken is a single string, not an array or object

        $fcmToken = is_array($fcmToken) ? $fcmToken : $fcmToken;

        // Optional android-specific APNs payload
        if ($deviceType == 'ios') {
            $payload = [
                'message' => [
                "token" => $fcmToken,
                "data" => [
                    "title" => $title,
                    "body" => $content,
                    "action" => (string) $click_action,
                    "redirect_id" => (string) $redirect_id,
                    "notification_id" => (string) $notification_id,
                    "extraData" => "1"

                ],
            ]
            ];
            // Optional ios-specific APNs payload
        } elseif ($deviceType == 'android') {
            $payload = [
                "message" => [
                    "token" => $fcmToken,
                    "notification" => [
                        "title" => $title,
                        "body" => $content,

                    ],
                    "data" => [
                        "action" => (string) $click_action,
                        "redirect_id" => (string) $redirect_id,
                        "notification_id" => (string) $notification_id,
                        "extraData" => "1"
                    ],
                    "apns" => [
                        "headers" => [
                            "apns-priority" => "10"
                        ],
                        "payload" => [
                            "aps" => [
                                "badge" => $badge,
                                "sound" => "default"
                            ],
                            "mutable-content" => 1,
                            "content-available" => 1,
                        ]
                    ]
                ]

            ];
        } else {
            $payload = [
                'message' => [
                    "token" => $fcmToken,
                    'notification' => [
                        "title" => $title,
                        "body" => $content,
                    ]
                ]

            ];
        }

        return $payload;
    }

    private function sendCurlRequest($url, $postData, $headers)
    {
        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => true,
                    'http_version' => 1.1,
                ])
                ->post($url, $postData);

            if ($response->failed()) {
                Log::error('HTTP Request Failed: ' . $response->body());
                return 'An error occurred while sending through Firebase.';
            }

            return true;
        } catch (\Exception $e) {
            Log::error('HTTP Exception: ' . $e->getMessage());
            return 'An error occurred while sending through Firebase.';
        }
    }


}
