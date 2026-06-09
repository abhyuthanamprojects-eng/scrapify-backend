<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ChannelPartner;
use App\Models\User;
use App\Services\OtpService;
use App\Services\ReferralService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Services\ActivityLogger;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected $otpService;
    protected $referralService;

    public function __construct(OtpService $otpService, ReferralService $referralService)
    {
        $this->otpService = $otpService;
        $this->referralService = $referralService;
    }

    #[OA\Post(
        path: "/api/auth/send-otp",
        operationId: "sendOtp",
        tags: ["Auth"],
        summary: "Send OTP to mobile number",
        description: "Sends a 6-digit OTP to the provided mobile number via MSG91.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["phone"],
                properties: [
                    new OA\Property(property: "phone", type: "string", example: "9876543210"),
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "role", type: "string", example: "customer", enum: ["customer", "pickup_boy", "warehouse", "channel_partner"])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP sent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "OTP sent successfully"),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "phone", type: "string")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 503, description: "SMS Service Unavailable")
        ]
    )]
    public function sendOtp(Request $request)
    {
        $requestedRole = $request->input('role');
        if ($requestedRole === null || $requestedRole === 'customer') {
            return $this->errorResponse('auth.customer_flow_moved', 410);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
            'role' => 'required|string|in:pickup_boy,warehouse,channel_partner,admin',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:120',
            'pincode' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $phone = $request->phone;
        $role = (string) $request->role;
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return $this->errorResponse('auth.user_not_found', 404, [
                'message' => 'This mobile number is not registered for this role.',
            ]);
        }

        $roleCheck = $this->canUseProtectedRoleLogin($user, $role);
        if (!$roleCheck['ok']) {
            return $this->errorResponse('auth.unauthorized_role', 403, [
                'message' => $roleCheck['message'],
            ]);
        }

        // Sync location including pincode
        $this->syncUserLocationFromRequest($user, $request);

        // Send OTP via OtpService (delegates to MSG91 in production)
        $otpResult = $this->otpService->sendOtp($phone, [
            'name' => $user->name ?? 'Customer'
        ]);

        // If MSG91 send failed, return error
        if (!$otpResult['success']) {
            ActivityLogger::log('otp_send_failed', 'auth', 'Failed to send OTP to ' . $phone, ['phone' => $phone, 'error' => $otpResult['message']], $user);
            return $this->errorResponse('auth.otp_send_failed', 503, ['message' => $otpResult['message']]);
        }

        // Store OTP in DB only for local/dev mode (when MSG91 is bypassed)
        if (!$otpResult['msg91'] && $otpResult['otp']) {
            $user->otp = $otpResult['otp'];
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->save();
        }

        ActivityLogger::log('otp_sent', 'auth', 'OTP sent to ' . $phone, ['phone' => $phone, 'via' => $otpResult['msg91'] ? 'msg91' : 'local'], $user);

        return $this->successResponse('auth.otp_sent', ['phone' => $phone], 200);
    }

    public function sendRegistrationOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'referral_code' => 'nullable|string|size:6',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:120',
            'pincode' => 'nullable|string|max:10',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $phone = $request->phone;
        $existing = User::where('phone', $phone)->first();
        if ($existing && $existing->hasRole('customer')) {
            return $this->errorResponse('auth.customer_already_registered', 409);
        }

        $normalizedEmail = strtolower(trim((string) $request->email));
        $emailUsed = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->when($existing, fn($q) => $q->where('id', '!=', $existing->id))
            ->exists();
        if ($emailUsed) {
            return $this->validationErrorResponse([
                'email' => ['This email is already registered with another account.'],
            ]);
        }

        $domain = env('APP_ENV') === 'production' ? 'scrapi5.com' : 'test.com';
        try {
            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'name'     => $request->name,
                    'email'    => $normalizedEmail ?: ($phone . '@' . $domain),
                    'password' => Hash::make($phone),
                    'status'   => true,
                ]
            );
        } catch (QueryException $e) {
            // Two scenarios trigger SQLSTATE 23000 (unique constraint violation):
            // 1. Race condition – another concurrent request inserted the same phone.
            // 2. The user previously soft-deleted their account; the row (and its
            //    unique index entry) still exists, blocking a fresh INSERT.
            // In both cases we fetch the existing row (including trashed), restore
            // it if needed, and continue as if firstOrCreate had found it.
            if ($e->getCode() === '23000') {
                $user = User::withTrashed()->where('phone', $phone)->first();
                if (!$user) {
                    throw $e; // truly unexpected – re-throw
                }
                if ($user->trashed()) {
                    $user->restore(); // bring the soft-deleted row back to life
                }
            } else {
                throw $e;
            }
        }
        $user->update(['name' => $request->name, 'email' => $normalizedEmail]);
        $this->syncUserLocationFromRequest($user, $request);

        if ($request->filled('referral_code')) {
            Cache::put('referral_pending:' . $phone, strtoupper($request->referral_code), now()->addMinutes(15));
        }

        $otpResult = $this->otpService->sendOtp($phone, ['name' => $user->name ?? 'Customer']);
        if (!$otpResult['success']) {
            return $this->errorResponse('auth.otp_send_failed', 503, ['message' => $otpResult['message']]);
        }
        if (!$otpResult['msg91'] && $otpResult['otp']) {
            $user->otp = $otpResult['otp'];
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->save();
        }

        return $this->successResponse('auth.otp_sent', ['phone' => $phone], 200);
    }

    public function verifyRegistrationOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
            'otp' => 'required|string|digits:6',
            'device_name' => 'required|string',
            'referral_code' => 'nullable|string|size:6',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:120',
            'pincode' => 'nullable|string|max:10',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user || !$this->otpService->validateOtp($user, $request->otp)) {
            return $this->errorResponse('auth.invalid_otp', 400);
        }
        if (!$user->hasRole('customer')) {
            $user->assignRole('customer');
        }
        if (!$user->referral_code) {
            $this->referralService->ensureReferralCode($user);
        }

        $user->otp = null;
        $user->otp_expires_at = null;
        $this->syncUserLocationFromRequest($user, $request);
        $user->save();

        $referralApplied = null;
        $cacheKey = 'referral_pending:' . $request->phone;
        $pendingCode = $request->input('referral_code') ?: Cache::get($cacheKey);
        if ($pendingCode && !$user->referralReceived) {
            $referralApplied = $this->referralService->applyReferral($pendingCode, $user);
            Cache::forget($cacheKey);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;
        return $this->successResponse('auth.registration_success', [
            'user' => $user->load('roles'),
            'token' => $token,
            'referral_applied' => $referralApplied ? true : false,
        ], 200);
    }

    public function sendLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:120',
            'pincode' => 'nullable|string|max:10',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user || !$user->hasRole('customer')) {
            return $this->errorResponse('auth.customer_not_registered', 404);
        }
        $this->syncUserLocationFromRequest($user, $request);

        $otpResult = $this->otpService->sendOtp($request->phone, ['name' => $user->name ?? 'Customer']);
        if (!$otpResult['success']) {
            return $this->errorResponse('auth.otp_send_failed', 503, ['message' => $otpResult['message']]);
        }
        if (!$otpResult['msg91'] && $otpResult['otp']) {
            $user->otp = $otpResult['otp'];
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->save();
        }

        return $this->successResponse('auth.otp_sent', ['phone' => $request->phone], 200);
    }

    public function verifyLoginOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
            'otp' => 'required|string|digits:6',
            'device_name' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:120',
            'pincode' => 'nullable|string|max:10',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user || !$user->hasRole('customer')) {
            return $this->errorResponse('auth.customer_not_registered', 404);
        }
        if (!$this->otpService->validateOtp($user, $request->otp)) {
            return $this->errorResponse('auth.invalid_otp', 400);
        }

        $user->otp = null;
        $user->otp_expires_at = null;
        $this->syncUserLocationFromRequest($user, $request);
        $user->save();

        if (!$user->referral_code) {
            $this->referralService->ensureReferralCode($user);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;
        return $this->successResponse('auth.login_success', [
            'user' => $user->load('roles'),
            'token' => $token,
            'referral_applied' => false,
        ], 200);
    }

    #[OA\Post(
        path: "/api/auth/verify-otp",
        operationId: "verifyOtp",
        tags: ["Auth"],
        summary: "Verify OTP and Login",
        description: "Verifies the OTP and returns an authentication token.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["phone", "otp", "device_name"],
                properties: [
                    new OA\Property(property: "phone", type: "string", example: "9876543210"),
                    new OA\Property(property: "otp", type: "string", example: "123456"),
                    new OA\Property(property: "device_name", type: "string", example: "My Device")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Login successful"),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "token", type: "string"),
                            new OA\Property(property: "user", type: "object")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid OTP")
        ]
    )]
    public function verifyOtp(Request $request)
    {
        $requestedRole = $request->input('role');
        if ($requestedRole === null || $requestedRole === 'customer') {
            return $this->errorResponse('auth.customer_flow_moved', 410);
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
            'otp' => 'required|string|digits:6',
            'device_name' => 'required|string',
            'role' => 'required|string|in:pickup_boy,channel_partner,warehouse,admin',
            'referral_code' => 'nullable|string|size:6',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:120',
            'pincode' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !$this->otpService->validateOtp($user, $request->otp)) {
            ActivityLogger::log('login_failed', 'auth', 'Invalid OTP or Phone', ['phone' => $request->phone], null);
            return $this->errorResponse('auth.invalid_otp', 400);
        }

        // Role Management Logic
        if ($request->has('role')) {
            $requestedRole = $request->role;
            $roleCheck = $this->canUseProtectedRoleLogin($user, $requestedRole);
            if (!$roleCheck['ok']) {
                ActivityLogger::log('login_failed', 'auth', "User tried to login as {$requestedRole} but lacks permission", ['phone' => $request->phone], $user);
                return $this->errorResponse('auth.unauthorized_role', 403, ['message' => $roleCheck['message']]);
            }
        }

        // Clear OTP (only relevant for local/dev mode)
        $user->otp = null;
        $user->otp_expires_at = null;

        // Sync location including pincode
        $this->syncUserLocationFromRequest($user, $request);
        $user->save();

        // Auto-generate referral code if customer + missing
        if ($user->hasRole('customer') && !$user->referral_code) {
            $this->referralService->ensureReferralCode($user);
        }

        // Apply pending referral (only for customers, only once)
        $referralApplied = null;
        if ($user->hasRole('customer')) {
            $cacheKey = 'referral_pending:' . $request->phone;
            $pendingCode = $request->input('referral_code') ?: Cache::get($cacheKey);
            if ($pendingCode && !$user->referralReceived) {
                $referralApplied = $this->referralService->applyReferral($pendingCode, $user);
                Cache::forget($cacheKey);
            }
        }

        // Create Token
        $token = $user->createToken($request->device_name)->plainTextToken;

        ActivityLogger::log('login', 'auth', "User logged in via OTP as " . ($request->role ?? 'user'), ['device' => $request->device_name, 'role_context' => $request->role], $user);

        return $this->successResponse('auth.login_success', [
            'user' => $user->load('roles'),
            'token' => $token,
            'referral_applied' => $referralApplied ? true : false,
        ], 200);
    }

    #[OA\Post(
        path: "/api/auth/resend-otp",
        operationId: "resendOtp",
        tags: ["Auth"],
        summary: "Resend OTP",
        description: "Resends OTP to the mobile number via SMS or voice call using MSG91.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["phone"],
                properties: [
                    new OA\Property(property: "phone", type: "string", example: "9876543210"),
                    new OA\Property(property: "retry_type", type: "string", example: "text", enum: ["text", "voice"])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP resent successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "OTP resent successfully"),
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "phone", type: "string")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 503, description: "SMS Service Unavailable")
        ]
    )]
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
            'retry_type' => 'nullable|string|in:text,voice',
            'role' => 'nullable|string|in:pickup_boy,warehouse,channel_partner,admin,customer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $phone = $request->phone;
        $retryType = $request->input('retry_type', 'text');

        // Verify user exists
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return $this->errorResponse('auth.user_not_found', 404);
        }

        $role = (string) $request->input('role', '');
        if ($role !== '' && $role !== 'customer') {
            $roleCheck = $this->canUseProtectedRoleLogin($user, $role);
            if (!$roleCheck['ok']) {
                return $this->errorResponse('auth.unauthorized_role', 403, [
                    'message' => $roleCheck['message'],
                ]);
            }
        }

        $result = $this->otpService->resendOtp($phone, $retryType);

        if (!$result['success']) {
            ActivityLogger::log('otp_resend_failed', 'auth', 'Failed to resend OTP to ' . $phone, ['phone' => $phone, 'error' => $result['message']], $user);
            return $this->errorResponse('auth.otp_send_failed', 503, ['message' => $result['message']]);
        }

        ActivityLogger::log('otp_resent', 'auth', 'OTP resent to ' . $phone . ' via ' . $retryType, ['phone' => $phone, 'retry_type' => $retryType], $user);

        return $this->successResponse('auth.otp_resent', ['phone' => $phone], 200);
    }

    #[OA\Get(
        path: "/api/auth/profile",
        operationId: "getProfile",
        tags: ["Auth"],
        summary: "Get User Profile",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Profile fetched successfully")
        ]
    )]
    public function profile(Request $request)
    {
        return $this->successResponse('profile.fetched', $request->user()->load('roles'));
    }

    #[OA\Post(
        path: "/api/auth/logout",
        operationId: "logout",
        tags: ["Auth"],
        summary: "Logout",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Logout successful")
        ]
    )]
    public function logout(Request $request)
    {
        ActivityLogger::log('logout', 'auth', 'User logged out');
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse('auth.logout_success');
    }

    protected function syncUserLocationFromRequest(User $user, Request $request): void
    {
        if (!$request->filled('latitude') && !$request->filled('longitude') && !$request->filled('location_name') && !$request->filled('pincode')) {
            return;
        }

        $updates = ['location_updated_at' => now()];

        if ($request->filled('latitude')) {
            $updates['latitude'] = $request->input('latitude');
        }
        if ($request->filled('longitude')) {
            $updates['longitude'] = $request->input('longitude');
        }
        if ($request->filled('pincode')) {
            $updates['pincode'] = $request->input('pincode');
        }

        if ($request->filled('location_name')) {
            $locationName = trim((string) $request->input('location_name'));
            $city = City::query()
                ->where('status', true)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($locationName) . '%'])
                ->first();
            if ($city) {
                $updates['city_id'] = $city->id;
            }
        }

        if (!empty($updates)) {
            $user->fill($updates)->save();
        }
    }

    protected function canUseProtectedRoleLogin(User $user, string $role): array
    {
        if (!$user->hasRole($role)) {
            return ['ok' => false, 'message' => "This mobile number is not allowed for {$role} login."];
        }

        if ((int) $user->status !== 1) {
            return ['ok' => false, 'message' => 'This account is inactive. Please contact admin.'];
        }

        if ($role === 'channel_partner') {
            if (!$user->channel_partner_id) {
                return ['ok' => false, 'message' => 'Channel partner profile is not linked to this mobile number.'];
            }
            $partner = ChannelPartner::find($user->channel_partner_id);
            if (!$partner || !$partner->login_enabled || $partner->registration_status !== 'approved') {
                return ['ok' => false, 'message' => 'Channel partner login is not enabled yet.'];
            }
        }

        if ($role === 'warehouse') {
            $hasWarehouse = $user->warehouse_id
                || $user->managedWarehouses()->exists()
                || \App\Models\Warehouse::where('manager_id', $user->id)->exists();
            if (!$hasWarehouse) {
                return ['ok' => false, 'message' => 'No warehouse is mapped to this mobile number.'];
            }
        }

        if ($role === 'pickup_boy') {
            $hasMapping = $user->warehouse_id || $user->activeWarehouses()->exists();
            if (!$hasMapping) {
                return ['ok' => false, 'message' => 'No pickup assignment mapping exists for this mobile number.'];
            }
        }

        return ['ok' => true, 'message' => 'ok'];
    }
}
