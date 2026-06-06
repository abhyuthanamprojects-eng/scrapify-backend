<?php

namespace App\Notifications;

use App\Models\PickupRequest;
use App\Notifications\Channels\Msg91SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class PickupStatusNotification extends Notification
{
    use Queueable;

    protected $pickupRequest;
    protected $status;
    protected $type; // 'pickup' or 'donation'

    public function __construct(PickupRequest $pickupRequest, string $status)
    {
        $this->pickupRequest = $pickupRequest;
        $this->status = $status;
        $this->type = ($pickupRequest->request_type === 'donation') ? 'donation' : 'pickup';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->fcm_token) {
            $channels[] = FcmChannel::class;
        }

        // Add SMS channel if user has a phone number and MSG91 is configured
        $authKey = \App\Models\AppSetting::get('msg91_auth_key') ?: config('services.msg91.auth_key');
        if ($notifiable->phone && $authKey) {
            $channels[] = Msg91SmsChannel::class;
        }


        return $channels;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'pickup_request_id' => $this->pickupRequest->id,
            'reference_type' => $this->pickupRequest->request_type . '_request',
            'reference_id' => $this->pickupRequest->id,
            'status_key' => $this->status,
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'payload' => $this->getPayload()
        ];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): FcmMessage
    {
        // Ensure all data values are strings for FCM
        $data = array_map(function ($value) {
            return is_null($value) ? '' : (string) $value;
        }, array_merge($this->getPayload(), [
                'type' => 'pickup_status_update',
            ]));

        return FcmMessage::create()
            ->token($notifiable->fcm_token)
            ->notification(
                FcmNotification::create()
                    ->title($this->getTitle())
                    ->body($this->getMessage())
            )
            ->data($data);
    }

    /**
     * Get notification title based on status and type.
     */
    protected function getTitle(): string
    {
        $key = "notifications.{$this->type}.{$this->status}.title";

        if (Lang::has($key)) {
            return Lang::get($key);
        }

        return $this->type === 'donation' ? 'Donation Status Update' : 'Pickup Status Update';
    }

    /**
     * Get notification message based on status and type.
     */
    protected function getMessage(): string
    {
        $key = "notifications.{$this->type}.{$this->status}.message";

        if (Lang::has($key)) {
            return Lang::get($key);
        }

        return 'Your request status has been updated.';
    }

    /**
     * Get rich payload for deep-linking.
     */
    protected function getPayload(): array
    {
        $payload = [
            'id' => $this->pickupRequest->id,
            'pickup_code' => $this->pickupRequest->pickup_code,
            'status' => $this->status,
            'request_type' => $this->pickupRequest->request_type,
            'scheduled_at' => $this->pickupRequest->scheduled_at ? $this->pickupRequest->scheduled_at->toDateTimeString() : null,
        ];

        // Add pickup boy details if assigned
        if ($this->pickupRequest->assignment && $this->pickupRequest->assignment->pickupBoy) {
            $payload['pickup_boy_name'] = $this->pickupRequest->assignment->pickupBoy->name;
            $payload['pickup_boy_phone'] = $this->pickupRequest->assignment->pickupBoy->phone;
        }

        return $payload;
    }

    /**
     * Get the MSG91 SMS representation of the notification.
     */
    public function toMsg91(object $notifiable): array
    {
        $templateId = match ($this->status) {
            'pending' => \App\Models\AppSetting::get('msg91_pickup_booked_template_id'),
            'completed' => \App\Models\AppSetting::get('msg91_pickup_completed_template_id'),
            'rescheduled' => \App\Models\AppSetting::get('msg91_pickup_rescheduled_template_id'),
            'feedback_request' => \App\Models\AppSetting::get('msg91_payment_feedback_template_id'),
            default => null
        };

        // Fallback to default SMS template if specific one is not set
        if (!$templateId) {
            $templateId = \App\Models\AppSetting::get('msg91_sms_template_id') ?: config('services.msg91.sms_template_id');
        }

        $variables = [
            'pickup_code' => $this->pickupRequest->pickup_code ?? '',
            'status' => $this->getTitle(),
            'message' => $this->getMessage(),
        ];

        // Add specific variables for Booking Confirmation if status is pending
        if ($this->status === 'pending') {
            $variables['pickup_id'] = $this->pickupRequest->pickup_code ?? '';
            $variables['date'] = $this->pickupRequest->scheduled_at ? $this->pickupRequest->scheduled_at->format('d-m-Y h:i A') : '';
            // Generic mappings for MSG91/DLT templates using var1, var2
            $variables['var1'] = $this->pickupRequest->pickup_code ?? '';
            $variables['var2'] = $this->pickupRequest->scheduled_at ? $this->pickupRequest->scheduled_at->format('d-m-Y h:i A') : '';
        }

        // Add specific variables for Completion
        if ($this->status === 'completed') {
            $variables['pickup_id'] = $this->pickupRequest->pickup_code ?? '';
            // Generic mappings for MSG91/DLT templates
            $variables['var1'] = $this->pickupRequest->pickup_code ?? '';
        }

        // Add specific variables for Feedback Request
        if ($this->status === 'feedback_request') {
            $variables['pickup_id'] = $this->pickupRequest->pickup_code ?? '';
            $variables['feedback_link'] = \App\Models\AppSetting::get('feedback_url', 'https://scrapify.in/feedback');
            $variables['app_name'] = config('app.name', 'Scrapify');
            // Generic mappings for MSG91/DLT templates
            $variables['var1'] = $this->pickupRequest->pickup_code ?? '';
            $variables['var2'] = \App\Models\AppSetting::get('feedback_url', 'https://scrapify.in/feedback');
            $variables['var3'] = config('app.name', 'Scrapify');
        }

        // Add specific variables for Rescheduling
        if ($this->status === 'rescheduled') {
            $variables['reason'] = $this->pickupRequest->reschedule_reason ?? 'unavoidable circumstances';
            $variables['rescheduled_reason'] = $this->pickupRequest->reschedule_reason ?? 'unavoidable circumstances';
            $variables['date'] = $this->pickupRequest->scheduled_at ? $this->pickupRequest->scheduled_at->format('d-m-Y h:i A') : '';
            // Generic mappings for MSG91/DLT templates
            $variables['var1'] = $this->pickupRequest->reschedule_reason ?? 'unavoidable circumstances';
            $variables['var2'] = $this->pickupRequest->scheduled_at ? $this->pickupRequest->scheduled_at->format('d-m-Y h:i A') : '';
        }

        return [
            'template_id' => $templateId,
            'variables' => $variables,
        ];
    }
}
