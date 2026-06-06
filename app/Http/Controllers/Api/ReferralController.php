<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralCoupon;
use App\Services\ReferralService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ReferralController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected ReferralService $service) {}

    #[OA\Get(
        path: "/api/referral/my-code",
        operationId: "getMyReferralCode",
        tags: ["Referral"],
        summary: "Get logged-in customer's referral code",
        security: [["apiAuth" => []]],
        responses: [new OA\Response(response: 200, description: "OK")]
    )]
    public function myCode()
    {
        $user = Auth::user();
        if (!$user->hasRole('customer')) {
            return $this->errorResponse('referral.customer_only', 403);
        }
        $code = $this->service->ensureReferralCode($user);
        return $this->successResponse('referral.my_code', ['referral_code' => $code]);
    }

    #[OA\Get(
        path: "/api/referral/my-rewards",
        operationId: "getMyReferralRewards",
        tags: ["Referral"],
        summary: "List my referral coupons (active/used/expired)",
        security: [["apiAuth" => []]],
        responses: [new OA\Response(response: 200, description: "OK")]
    )]
    public function myRewards(Request $request)
    {
        $coupons = ReferralCoupon::where('user_id', Auth::id())
            ->latest()
            ->paginate($request->input('per_page', 20));

        return $this->successResponse('referral.my_rewards', $coupons);
    }

    #[OA\Post(
        path: "/api/referral/validate-code",
        operationId: "validateReferralCode",
        tags: ["Referral"],
        summary: "Validate a referral code (pre/during signup)",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["referral_code"],
                properties: [new OA\Property(property: "referral_code", type: "string", example: "AMIT12")]
            )
        ),
        responses: [new OA\Response(response: 200, description: "OK"), new OA\Response(response: 422, description: "Invalid")]
    )]
    public function validateCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|size:6',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $result = $this->service->validateReferralCode($request->referral_code, Auth::user());
        if (!$result['ok']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['message'], [
            'referrer_name' => $result['referrer']->name,
        ]);
    }

    #[OA\Post(
        path: "/api/referral/validate-coupon",
        operationId: "validateReferralCoupon",
        tags: ["Referral"],
        summary: "Validate a coupon before booking",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["coupon_code", "booking_amount"],
                properties: [
                    new OA\Property(property: "coupon_code", type: "string", example: "REFAB12CD3"),
                    new OA\Property(property: "booking_amount", type: "number", example: 1500),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: "OK"), new OA\Response(response: 422, description: "Invalid")]
    )]
    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code'    => 'required|string',
            'booking_amount' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $result = $this->service->validateCoupon($request->coupon_code, Auth::user(), (float) $request->booking_amount);
        if (!$result['ok']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result['message'], [
            'coupon'   => $result['coupon'],
            'discount' => $result['discount'],
        ]);
    }
}
