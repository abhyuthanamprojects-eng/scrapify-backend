<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartner;
use App\Models\City;
use App\Models\State;
// use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class PartnerRegistrationController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/PartnerRegister', [
            'states' => State::with('cities')->where('status', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/|unique:channel_partners,phone',
            'email' => 'required|email|unique:channel_partners,email',
            'business_name' => 'required|string|max:255',
            'aadhaar_number' => 'required|string|size:12|unique:channel_partners,aadhaar_number',
            'pan_number' => 'required|string|size:10|unique:channel_partners,pan_number',
            'gst_number' => 'nullable|string|max:15',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'pincode' => 'required|string|size:6',
            'opening_location_name' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        ChannelPartner::create($request->all());

        return Inertia::render('Auth/PartnerRegisterSuccess');
    }
}
