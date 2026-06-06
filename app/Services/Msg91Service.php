<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Service
{
    protected string $authKey;
    protected string $otpTemplateId;
    protected string $smsTemplateId;
    protected string $senderId;
    protected string $countryCode;
    protected string $baseUrl = 'https://control.msg91.com/api/v5';

    public function __construct()
    {
        $this->authKey = (string) \App\Models\AppSetting::get('msg91_auth_key', config('services.msg91.auth_key', ''));
        $this->otpTemplateId = (string) \App\Models\AppSetting::get('msg91_otp_template_id', config('services.msg91.otp_template_id', ''));
        $this->smsTemplateId = (string) \App\Models\AppSetting::get('msg91_sms_template_id', config('services.msg91.sms_template_id', ''));
        $this->senderId = (string) \App\Models\AppSetting::get('msg91_sender_id', config('services.msg91.sender_id', 'SCRPI'));
        $this->countryCode = (string) \App\Models\AppSetting::get('msg91_country_code', config('services.msg91.country_code', '91'));
    }

    /**
     * Format a 10-digit mobile number with country code.
     * e.g. '9876543210' -> '919876543210'
     */
    protected function formatMobile(string $mobile): string
    {
        // Strip any leading '+' or '0'
        $mobile = ltrim($mobile, '+0');

        // If already has country code (length > 10), return as-is
        if (strlen($mobile) > 10) {
            return $mobile;
        }

        return $this->countryCode . $mobile;
    }

    /**
     * Send OTP to a mobile number via MSG91 OTP API.
     *
     * MSG91 generates, delivers, and manages the OTP internally.
     *
     * @param string $mobile    10-digit mobile number
     * @param array  $variables Optional template variables (e.g. ['name' => 'John'])
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function sendOtp(string $mobile, array $variables = []): array
    {
        $formattedMobile = $this->formatMobile($mobile);

        try {
            // Trim to ensure no accidental spaces from DB
            $templateId = trim($this->otpTemplateId);
            
            // MSG91 v5 OTP API often requires template_id and mobile as query parameters
            $url = "{$this->baseUrl}/otp?template_id={$templateId}&mobile={$formattedMobile}&sender=" . trim($this->senderId);

            $response = Http::withHeaders([
                'authkey' => trim($this->authKey),
                'Content-Type' => 'application/json',
            ])->post($url, $variables);

            $body = $response->json();

            Log::info('MSG91 Send OTP Response', [
                'mobile' => $formattedMobile,
                'status' => $response->status(),
                'response' => $body,
            ]);

            if ($response->successful() && isset($body['type']) && $body['type'] === 'success') {
                return [
                    'success' => true,
                    'message' => $body['message'] ?? 'OTP sent successfully',
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'message' => $body['message'] ?? 'Failed to send OTP',
                'data' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 Send OTP Error', [
                'mobile' => $formattedMobile,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service unavailable. Please try again later.',
                'data' => null,
            ];
        }
    }

    /**
     * Verify OTP via MSG91 OTP Verify API.
     *
     * @param string $mobile 10-digit mobile number
     * @param string $otp    The OTP entered by user
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function verifyOtp(string $mobile, string $otp): array
    {
        $formattedMobile = $this->formatMobile($mobile);

        try {
            $response = Http::withHeaders([
                'authkey' => $this->authKey,
            ])->get("{$this->baseUrl}/otp/verify", [
                        'otp' => $otp,
                        'mobile' => $formattedMobile,
                    ]);

            $body = $response->json();

            Log::info('MSG91 Verify OTP Response', [
                'mobile' => $formattedMobile,
                'status' => $response->status(),
                'response' => $body,
            ]);

            if ($response->successful() && isset($body['type']) && $body['type'] === 'success') {
                return [
                    'success' => true,
                    'message' => $body['message'] ?? 'OTP verified successfully',
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'message' => $body['message'] ?? 'Invalid OTP',
                'data' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 Verify OTP Error', [
                'mobile' => $formattedMobile,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service unavailable. Please try again later.',
                'data' => null,
            ];
        }
    }

    /**
     * Resend OTP via MSG91 Retry API.
     *
     * @param string $mobile 10-digit mobile number
     * @param string $retryType 'text' for SMS, 'voice' for voice call
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function resendOtp(string $mobile, string $retryType = 'text'): array
    {
        $formattedMobile = $this->formatMobile($mobile);

        try {
            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/otp/retry", [
                        'mobile' => $formattedMobile,
                        'retrytype' => $retryType, // 'text' or 'voice'
                    ]);

            $body = $response->json();

            Log::info('MSG91 Resend OTP Response', [
                'mobile' => $formattedMobile,
                'retryType' => $retryType,
                'status' => $response->status(),
                'response' => $body,
            ]);

            if ($response->successful() && isset($body['type']) && $body['type'] === 'success') {
                return [
                    'success' => true,
                    'message' => $body['message'] ?? 'OTP resent successfully',
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'message' => $body['message'] ?? 'Failed to resend OTP',
                'data' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 Resend OTP Error', [
                'mobile' => $formattedMobile,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service unavailable. Please try again later.',
                'data' => null,
            ];
        }
    }

    /**
     * Send a transactional SMS via MSG91 Flow API.
     *
     * @param string      $mobile     10-digit mobile number
     * @param string      $templateId Flow template ID from MSG91 dashboard
     * @param array       $variables  Template variables (e.g. ['VAR1' => 'value1'])
     * @return array ['success' => bool, 'message' => string, 'data' => mixed]
     */
    public function sendSms(string $mobile, string $templateId, array $variables = []): array
    {
        $formattedMobile = $this->formatMobile($mobile);

        $recipient = array_merge(['mobiles' => $formattedMobile], $variables);

        try {
            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/flow", [
                        'template_id' => $templateId,
                        'short_url' => '0',
                        'recipients' => [$recipient],
                    ]);

            $body = $response->json();

            Log::info('MSG91 Send SMS Response', [
                'mobile' => $formattedMobile,
                'templateId' => $templateId,
                'status' => $response->status(),
                'response' => $body,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $body,
                ];
            }

            return [
                'success' => false,
                'message' => $body['message'] ?? 'Failed to send SMS',
                'data' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 Send SMS Error', [
                'mobile' => $formattedMobile,
                'templateId' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS service unavailable. Please try again later.',
                'data' => null,
            ];
        }
    }

    /**
     * Send a transactional SMS using the default SMS template ID.
     *
     * @param string $mobile    10-digit mobile number
     * @param array  $variables Template variables
     * @return array
     */
    public function sendDefaultSms(string $mobile, array $variables = []): array
    {
        return $this->sendSms($mobile, $this->smsTemplateId, $variables);
    }
}
