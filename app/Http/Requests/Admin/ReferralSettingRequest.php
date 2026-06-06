<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReferralSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->hasAnyRole(['admin', 'channel_partner']);
    }

    public function rules(): array
    {
        return [
            'campaign_name'          => 'required|string|max:255',
            'is_active'              => 'sometimes|boolean',
            'reward_type'            => 'required|in:fixed,percentage,extra_value',
            'reward_value'           => 'required|numeric|min:0',
            'coupon_expiry_days'     => 'required|integer|min:1|max:365',
            'min_booking_value'      => 'nullable|numeric|min:0',
            'max_reward_value'       => 'nullable|numeric|min:0',
            'max_referrals_per_user' => 'nullable|integer|min:1|max:1000',
            'start_date'             => 'nullable|date',
            'end_date'               => 'nullable|date|after_or_equal:start_date',
        ];
    }
}
