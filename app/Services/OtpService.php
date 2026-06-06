<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected Msg91Service $msg91;

    /**
     * Test/dummy phone numbers that always use fixed OTP '123456'.
     */
    protected array $testNumbers = [
        '1111111111', // Admin
        '9999999999', // Customer
        '8888888888', // Warehouse
        '8888888889', // Delhi Warehouse
        '7777777777', // Channel Partner
        '6666666666', // Pickup Boy
    ];

    public function __construct(Msg91Service $msg91)
    {
        $this->msg91 = $msg91;
    }

    /**
     * Determine if the mobile number should bypass MSG91 (dev/test).
     */
    protected function shouldBypassMsg91(string $mobile): bool
    {
        return in_array($mobile, $this->testNumbers)
            || app()->environment('local', 'testing');
    }

    /**
     * Send OTP to the mobile number.
     *
     * - In local/testing or for test numbers: returns fixed '123456' (no SMS sent).
     * - In production: delegates to MSG91 which generates and delivers the OTP.
     *
     * @param string $mobile
     * @param array  $variables Optional variables for the template (e.g. ['name' => 'John'])
     * @return array ['otp' => string|null, 'msg91' => bool, 'success' => bool, 'message' => string]
     */
    public function sendOtp(string $mobile, array $variables = []): array
    {
        // Dev/test bypass — use fixed OTP, no SMS sent
        if ($this->shouldBypassMsg91($mobile)) {
            $otp = '123456';
            Log::info("OTP SENT (LOCAL) -> Mobile: {$mobile} | Code: {$otp}");

            return [
                'otp' => $otp,
                'msg91' => false,
                'success' => true,
                'message' => 'OTP sent successfully (dev mode)',
            ];
        }

        $variables['OTP'] = rand(100000, 999999);

        // Production — send via MSG91
        $result = $this->msg91->sendOtp($mobile, $variables);

        if ($result['success']) {
            Log::info("OTP SENT (MSG91) -> Mobile: {$mobile}");

            return [
                'otp' => null, // MSG91 manages the OTP server-side
                'msg91' => true,
                'success' => true,
                'message' => 'OTP sent successfully',
            ];
        }

        Log::warning("OTP SEND FAILED (MSG91) -> Mobile: {$mobile}", $result);

        return [
            'otp' => null,
            'msg91' => true,
            'success' => false,
            'message' => $result['message'] ?? 'Failed to send OTP',
        ];
    }

    /**
     * Validate the OTP.
     *
     * - In local/testing: validates against user's DB-stored OTP.
     * - In production: verifies via MSG91 API.
     *
     * @param User   $user
     * @param string $otp
     * @return bool
     */
    public function validateOtp(User $user, string $otp): bool
    {
        // Dev/test bypass — validate against DB
        if ($this->shouldBypassMsg91($user->phone)) {
            if (app()->environment('local') && $otp === '123456') {
                return true;
            }

            if ($user->otp !== $otp) {
                return false;
            }

            if (Carbon::now()->greaterThan($user->otp_expires_at)) {
                return false;
            }

            return true;
        }

        // Production — verify via MSG91
        $result = $this->msg91->verifyOtp($user->phone, $otp);

        return $result['success'];
    }

    /**
     * Resend OTP via MSG91 retry API.
     *
     * @param string $mobile
     * @param string $retryType 'text' for SMS, 'voice' for voice call
     * @return array ['success' => bool, 'message' => string]
     */
    public function resendOtp(string $mobile, string $retryType = 'text'): array
    {
        // Dev/test bypass
        if ($this->shouldBypassMsg91($mobile)) {
            Log::info("OTP RESEND (LOCAL) -> Mobile: {$mobile} | Code: 123456");

            return [
                'success' => true,
                'message' => 'OTP resent successfully (dev mode)',
            ];
        }

        // Production — resend via MSG91
        $result = $this->msg91->resendOtp($mobile, $retryType);

        return [
            'success' => $result['success'],
            'message' => $result['message'],
        ];
    }
}
