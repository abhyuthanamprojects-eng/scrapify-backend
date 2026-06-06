<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReferralSettingRequest;
use App\Models\ReferralSetting;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralSettingController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $settings = ReferralSetting::latest()
            ->paginate($request->input('per_page', 20));
        return $this->successResponse('referral.settings_list', $settings);
    }

    public function store(ReferralSettingRequest $request)
    {
        $user = Auth::user();
        $role = $user->hasRole('admin') ? 'admin' : 'channel_partner';

        if ($request->boolean('is_active', true)) {
            ReferralSetting::where('is_active', true)->update(['is_active' => false]);
        }

        $setting = ReferralSetting::create(array_merge(
            $request->validated(),
            [
                'managed_by_user_id' => $user->id,
                'managed_by_role'    => $role,
                'is_active'          => $request->boolean('is_active', true),
            ]
        ));

        return $this->successResponse('referral.setting_created', $setting, 201);
    }

    public function update(ReferralSettingRequest $request, $id)
    {
        $setting = ReferralSetting::find($id);
        if (!$setting) return $this->errorResponse('referral.setting_not_found', 404);

        if ($request->boolean('is_active') === true && !$setting->is_active) {
            ReferralSetting::where('is_active', true)->update(['is_active' => false]);
        }

        $setting->update($request->validated());
        return $this->successResponse('referral.setting_updated', $setting);
    }
}
