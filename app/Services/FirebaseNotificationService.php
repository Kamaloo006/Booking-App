<?php

namespace App\Services;

// تأكد من استدعاء المكتبات الضرورية
use Google\Client;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationService
{
    /**
     * إرسال إشعار عبر Firebase V1 API
     */
    public static function sendNotification($token, $title, $body)
    {
        try {
            $client = new Client();
            // المسار الذي وضعت فيه ملف الـ JSON
            $client->setAuthConfig(storage_path('app/firebase/bookit-bb53b-firebase-adminsdk-fbsvc-9f3776bf66.json'));
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            
            // جلب توكن الوصول من جوجل
            $client->fetchAccessTokenWithAssertion();
            $accessToken = $client->getAccessToken()['access_token'];

            // رابط الإرسال الخاص بمشروعك
            $projectId = 'bookit-bb53b'; 
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $message = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => $title,
                        "body" => $body
                    ]
                ]
            ];

            $response = Http::withToken($accessToken)->post($url, $message);

            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}