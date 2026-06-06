<?php

namespace App\Notifications\Channels;

use App\Services\Msg91Service;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class Msg91SmsChannel
{
    protected Msg91Service $msg91;

    public function __construct(Msg91Service $msg91)
    {
        $this->msg91 = $msg91;
    }

    /**
     * Send the given notification via MSG91 SMS.
     *
     * The notification class must implement a `toMsg91($notifiable)` method
     * returning an array with:
     *   - 'template_id' => string (MSG91 Flow template ID)
     *   - 'variables'   => array  (template variables, e.g. ['VAR1' => 'value'])
     *
     * The mobile number is taken from $notifiable->phone.
     *
     * @param mixed        $notifiable
     * @param Notification $notification
     */
    public function send($notifiable, Notification $notification)
    {
        $phone = $notifiable->phone ?? null;

        if (!$phone) {
            Log::warning('Msg91SmsChannel: No phone number on notifiable', [
                'notifiable_id' => $notifiable->id ?? null,
                'notification'  => get_class($notification),
            ]);
            return;
        }

        // Skip SMS in local/testing unless explicitly enabled
        if (app()->environment('local', 'testing') && !config('services.msg91.force_sms_in_dev', false)) {
            Log::info('Msg91SmsChannel: Skipping SMS in dev mode', [
                'phone'        => $phone,
                'notification' => get_class($notification),
            ]);
            return;
        }

        if (!method_exists($notification, 'toMsg91')) {
            Log::error('Msg91SmsChannel: Notification missing toMsg91() method', [
                'notification' => get_class($notification),
            ]);
            return;
        }

        $data = $notification->toMsg91($notifiable);

        $templateId = $data['template_id'] ?? \App\Models\AppSetting::get('msg91_sms_template_id') ?: config('services.msg91.sms_template_id');
        $variables  = $data['variables'] ?? [];

        if (!$templateId) {
            Log::error('Msg91SmsChannel: No template_id provided', [
                'phone'        => $phone,
                'notification' => get_class($notification),
            ]);
            return;
        }

        $result = $this->msg91->sendSms($phone, $templateId, $variables);

        if (!$result['success']) {
            Log::warning('Msg91SmsChannel: SMS send failed', [
                'phone'        => $phone,
                'notification' => get_class($notification),
                'error'        => $result['message'],
            ]);
        }
    }
}
