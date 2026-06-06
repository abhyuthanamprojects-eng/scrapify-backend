<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartnerCustomer;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChannelPartnerCustomerController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $partnerId = $this->partnerId($request);
        $query = ChannelPartnerCustomer::where('channel_partner_id', $partnerId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%"));
        }

        return $this->paginatedResponse(
            'partner.customers_fetched',
            $query->latest()->paginate($request->per_page ?? 20)
        );
    }

    public function store(Request $request)
    {
        $partnerId = $this->partnerId($request);
        $validator = Validator::make($request->all(), $this->rules($partnerId));

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $customer = ChannelPartnerCustomer::create($validator->validated() + [
            'channel_partner_id' => $partnerId,
        ]);

        return $this->successResponse('partner.customer_created', $customer, 201);
    }

    public function show(Request $request, $id)
    {
        return $this->successResponse('partner.customer_fetched', $this->findForPartner($request, $id));
    }

    public function update(Request $request, $id)
    {
        $partnerId = $this->partnerId($request);
        $customer = $this->findForPartner($request, $id);
        $validator = Validator::make($request->all(), $this->rules($partnerId, $customer->id));

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $customer->update($validator->validated());

        return $this->successResponse('partner.customer_updated', $customer->fresh());
    }

    public function destroy(Request $request, $id)
    {
        $this->findForPartner($request, $id)->delete();

        return $this->successResponse('partner.customer_deleted');
    }

    private function rules(int $partnerId, ?int $ignoreId = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'mobile' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('channel_partner_customers', 'mobile')
                    ->where('channel_partner_id', $partnerId)
                    ->ignore($ignoreId),
            ],
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'landmark' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }

    private function findForPartner(Request $request, $id): ChannelPartnerCustomer
    {
        return ChannelPartnerCustomer::where('channel_partner_id', $this->partnerId($request))->findOrFail($id);
    }

    private function partnerId(Request $request): int
    {
        abort_unless($request->user()->channel_partner_id, 403, 'Channel partner profile not found.');

        return (int) $request->user()->channel_partner_id;
    }
}
