<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class TestPushNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->token($notifiable->fcm_token)
            ->notification(FcmNotification::create()
                ->title('Test Notification')
                ->body('Hello! This is a test push notification from Scrapify.')
            )
            ->data([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'id' => '1',
                'status' => 'done',
            ]);
    }
}
