<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;

class NotificationService
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = app('firebase.messaging');
    }

    /**
     * Send a notification to a single device
     *
     * @param string $fcmToken
     * @param string $title
     * @param string $body
     * @param array $data Optional key-value data payload
     * @return bool
     */
    public function send(string $fcmToken, string $title, string $body, array $data = [])
    {
        // Create notification
        $notification = Notification::create($title, $body);

        // Build the message array
        $messageArray = [
            'token' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        // Attach data payload if present
        if (!empty($data)) {
            $messageArray['data'] = $data;
        }

        // Create CloudMessage from array
        $message = CloudMessage::fromArray($messageArray);

        try {
            $this->messaging->send($message);
            return true;

        } catch (MessagingException $e) {
            return response()->json(['error'=>$e->getMessage()],500);

        } catch (FirebaseException $e) {
            
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }
}
