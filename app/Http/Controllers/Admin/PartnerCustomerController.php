<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartnerCustomer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PartnerCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = ChannelPartnerCustomer::query();

        if ($request->user()->hasRole('channel_partner')) {
            $query->where('channel_partner_id', $request->user()->channel_partner_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%"));
        }

        return Inertia::render('Admin/PartnerCustomers/Index', [
            'customers' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/PartnerCustomers/Form', ['customer' => null]);
    }

    public function store(Request $request)
    {
        $partnerId = $this->partnerId($request);
        $data = $request->validate($this->rules($partnerId));
        ChannelPartnerCustomer::create($data + ['channel_partner_id' => $partnerId]);

        return redirect()->route('admin.partner-customers.index')->with('success', 'Customer added successfully.');
    }

    public function edit(Request $request, ChannelPartnerCustomer $partnerCustomer)
    {
        $this->authorizePartner($request, $partnerCustomer);

        return Inertia::render('Admin/PartnerCustomers/Form', ['customer' => $partnerCustomer]);
    }

    public function update(Request $request, ChannelPartnerCustomer $partnerCustomer)
    {
        $this->authorizePartner($request, $partnerCustomer);
        $partnerCustomer->update($request->validate($this->rules($partnerCustomer->channel_partner_id, $partnerCustomer->id)));

        return redirect()->route('admin.partner-customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, ChannelPartnerCustomer $partnerCustomer)
    {
        $this->authorizePartner($request, $partnerCustomer);
        $partnerCustomer->delete();

        return back()->with('success', 'Customer deleted successfully.');
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

    private function partnerId(Request $request): int
    {
        abort_unless($request->user()->channel_partner_id, 403);

        return (int) $request->user()->channel_partner_id;
    }

    private function authorizePartner(Request $request, ChannelPartnerCustomer $customer): void
    {
        if ($request->user()->hasRole('channel_partner')) {
            abort_unless($customer->channel_partner_id === $request->user()->channel_partner_id, 403);
        }
    }
}
