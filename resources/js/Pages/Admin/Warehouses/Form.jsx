import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import GoogleMapPicker from '@/Components/GoogleMapPicker';

export default function Form({ warehouse, cities, maxServicePincodes = 10 }) {
    const { config } = usePage().props;
    const apiKey = config?.google_maps_key;
    const pincodeLimit = Math.max(1, Number(maxServicePincodes || 10));

    const { data, setData, post, put, processing, errors } = useForm({
        name: warehouse?.name || '',
        city_id: warehouse?.city_id || '',
        city_name: warehouse?.city?.name || '',
        address: warehouse?.address || '',
        capacity: warehouse?.capacity || '',
        area: warehouse?.area || '',
        zone: warehouse?.zone || '',
        latitude: warehouse?.latitude || '',
        longitude: warehouse?.longitude || '',
        service_pincodes: Array.isArray(warehouse?.service_pincodes) ? warehouse.service_pincodes : [],
        service_types: warehouse?.service_types || ['scrap_pickup'],
        status: warehouse?.status ?? true,
        accepts_corporate: warehouse?.accepts_corporate ?? true,
        accepts_donation: warehouse?.accepts_donation ?? true,
    });

    const [resolveError, setResolveError] = useState(null);
    const [resolving, setResolving] = useState(false);
    const [pincodeInput, setPincodeInput] = useState('');
    const [pincodeError, setPincodeError] = useState(null);
    const pincodeCount = data.service_pincodes.length;

    const onMapChange = async ({ lat, lng, address }) => {
        setData((prev) => ({
            ...prev,
            latitude: lat,
            longitude: lng,
            ...(address ? { address } : {}),
        }));
        setResolving(true);
        setResolveError(null);
        try {
            const { data: resp } = await axios.post(route('admin.warehouses.reverseGeocode') || '/api/admin/location/reverse-geocode', {
                latitude: lat,
                longitude: lng,
            });
            const d = resp?.data || {};
            setData((prev) => ({
                ...prev,
                latitude: lat,
                longitude: lng,
                city_id: d.city_id || prev.city_id,
                city_name: d.city_name || prev.city_name,
                zone: d.zone || prev.zone,
                address: address || d.formatted_address || prev.address,
            }));
            if (!d.city_id) {
                setResolveError('City not found in system. Pick manually below.');
            }
        } catch {
            setResolveError('Reverse-geocode failed. Pick city manually.');
        } finally {
            setResolving(false);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (warehouse) {
            put(route('admin.warehouses.update', warehouse.id));
        } else {
            post(route('admin.warehouses.store'));
        }
    };

    const toggleService = (val, checked) => {
        const next = checked
            ? [...new Set([...data.service_types, val])]
            : data.service_types.filter((t) => t !== val);
        setData('service_types', next);
    };

    const addPincode = () => {
        const normalized = String(pincodeInput || '').replace(/\D/g, '').slice(0, 6);

        if (!/^\d{6}$/.test(normalized)) {
            setPincodeError('Enter a valid 6-digit pincode.');
            return;
        }

        if (data.service_pincodes.includes(normalized)) {
            setPincodeError('This pincode is already added.');
            return;
        }

        if (data.service_pincodes.length >= pincodeLimit) {
            setPincodeError(`You can add up to ${pincodeLimit} pincodes only.`);
            return;
        }

        setData('service_pincodes', [...data.service_pincodes, normalized]);
        setPincodeInput('');
        setPincodeError(null);
    };

    const removePincode = (value) => {
        setData(
            'service_pincodes',
            data.service_pincodes.filter((p) => p !== value),
        );
        setPincodeError(null);
    };

    return (
        <AdminLayout>
            <Head title={warehouse ? 'Edit Warehouse' : 'Create Warehouse'} />

            <div className="max-w-3xl">
                <h1 className="text-3xl font-semibold text-gray-800 mb-6">
                    {warehouse ? 'Edit Warehouse' : 'Create New Warehouse'}
                </h1>

                <div className="bg-white shadow-md rounded-lg p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="name" value="Warehouse Name" />
                            <TextInput
                                id="name"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        {warehouse?.code && (
                            <div>
                                <InputLabel value="Warehouse Code" />
                                <div className="mt-1 inline-flex items-center px-3 py-2 bg-gray-100 rounded-md text-sm font-mono text-gray-800">
                                    {warehouse.code}
                                    <span className="ml-2 text-xs text-gray-500">(auto-generated, immutable)</span>
                                </div>
                            </div>
                        )}

                        <div>
                            <InputLabel value="Pick Location on Map" />
                            <p className="text-xs text-gray-500 mb-2">
                                Search or click on map to set warehouse location. City &amp; zone auto-fill.
                            </p>
                            <GoogleMapPicker
                                apiKey={apiKey}
                                initialLat={data.latitude}
                                initialLng={data.longitude}
                                onChange={onMapChange}
                            />
                            <div className="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-600">
                                <div>Lat: <span className="font-mono">{data.latitude || '—'}</span></div>
                                <div>Lng: <span className="font-mono">{data.longitude || '—'}</span></div>
                            </div>
                            {resolving && <div className="text-xs text-blue-600 mt-1">Resolving city/zone…</div>}
                            {resolveError && <div className="text-xs text-orange-600 mt-1">{resolveError}</div>}
                            <InputError message={errors.latitude || errors.longitude} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel value="City (auto)" />
                                {data.city_id ? (
                                    <div className="mt-1 inline-flex items-center px-3 py-2 bg-green-50 border border-green-200 rounded-md text-sm">
                                        ✓ {data.city_name || cities.find((c) => c.id == data.city_id)?.name}
                                    </div>
                                ) : (
                                    <select
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                        value={data.city_id}
                                        onChange={(e) => setData('city_id', e.target.value)}
                                    >
                                        <option value="">Select City (manual fallback)</option>
                                        {cities.map((c) => (
                                            <option key={c.id} value={c.id}>{c.name}, {c.state?.name}</option>
                                        ))}
                                    </select>
                                )}
                                <InputError message={errors.city_id} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel value="Zone (auto)" />
                                <TextInput
                                    type="text"
                                    className="mt-1 block w-full bg-gray-50"
                                    value={data.zone}
                                    onChange={(e) => setData('zone', e.target.value)}
                                    placeholder="Auto-filled from city"
                                />
                                <InputError message={errors.zone} className="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="address" value="Address" />
                            <textarea
                                id="address"
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="3"
                                value={data.address}
                                onChange={(e) => setData('address', e.target.value)}
                                required
                            />
                            <InputError message={errors.address} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="area" value="Area / Sector" />
                                <TextInput
                                    id="area"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={data.area}
                                    onChange={(e) => setData('area', e.target.value)}
                                    placeholder="e.g. Sector 48"
                                />
                                <InputError message={errors.area} className="mt-2" />
                            </div>

                            <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 shadow-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <InputLabel value="Service Pincodes" className="text-base" />
                                        <p className="mt-1 text-sm text-gray-500">
                                            Add {pincodeCount}/{pincodeLimit} pincodes for this warehouse.
                                        </p>
                                    </div>
                                    <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 border border-slate-200">
                                        {pincodeLimit} max
                                    </span>
                                </div>

                                <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                    <div className="flex-1">
                                        <TextInput
                                            type="text"
                                            inputMode="numeric"
                                            maxLength={6}
                                            className="block w-full bg-white"
                                            value={pincodeInput}
                                            onChange={(e) => {
                                                setPincodeInput(e.target.value.replace(/\D/g, '').slice(0, 6));
                                                if (pincodeError) setPincodeError(null);
                                            }}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    addPincode();
                                                }
                                            }}
                                            placeholder="Enter 6-digit pincode"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={addPincode}
                                        disabled={pincodeCount >= pincodeLimit}
                                        className="inline-flex h-10 items-center justify-center rounded-md bg-primary px-5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        Add Pincode
                                    </button>
                                </div>

                                <div className="mt-4 min-h-16 rounded-xl border border-dashed border-slate-300 bg-white px-3 py-3">
                                    {data.service_pincodes.length > 0 ? (
                                        <div className="flex flex-wrap gap-2">
                                            {data.service_pincodes.map((pincode) => (
                                                <span
                                                    key={pincode}
                                                    className="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700 border border-indigo-100"
                                                >
                                                    {pincode}
                                                    <button
                                                        type="button"
                                                        onClick={() => removePincode(pincode)}
                                                        className="text-indigo-500 transition hover:text-indigo-700"
                                                        aria-label={`Remove ${pincode}`}
                                                    >
                                                        ×
                                                    </button>
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="px-1 py-2 text-sm text-slate-400">
                                            No service pincodes added yet.
                                        </p>
                                    )}
                                </div>

                                <div className="mt-3 flex items-center justify-between gap-3">
                                    <div>
                                        {pincodeError ? (
                                            <p className="text-sm text-red-600">{pincodeError}</p>
                                        ) : (
                                            <p className="text-xs text-slate-500">
                                                Only 6-digit pincodes are accepted.
                                            </p>
                                        )}
                                    </div>
                                    <InputError message={errors.service_pincodes} className="mt-0 text-right" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Supported Service Types" />
                            <div className="mt-2 flex gap-4">
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        checked={data.service_types.includes('scrap_pickup')}
                                        onChange={(e) => toggleService('scrap_pickup', e.target.checked)}
                                    />
                                    <span className="ml-2 text-sm text-gray-600">Scrap Pickup</span>
                                </label>
                                <label className="flex items-center">
                                    <input
                                        type="checkbox"
                                        className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        checked={data.service_types.includes('donation')}
                                        onChange={(e) => toggleService('donation', e.target.checked)}
                                    />
                                    <span className="ml-2 text-sm text-gray-600">Donation</span>
                                </label>
                            </div>
                            <InputError message={errors.service_types} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="capacity" value="Capacity (kg)" />
                            <TextInput
                                id="capacity"
                                type="number"
                                step="0.01"
                                className="mt-1 block w-full"
                                value={data.capacity}
                                onChange={(e) => setData('capacity', e.target.value)}
                            />
                            <InputError message={errors.capacity} className="mt-2" />
                        </div>

                        <div className="flex items-center">
                            <input
                                id="status"
                                type="checkbox"
                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                checked={data.status}
                                onChange={(e) => setData('status', e.target.checked)}
                            />
                            <label htmlFor="status" className="ml-2 block text-sm text-gray-900">Active</label>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label className="flex items-center">
                                <input
                                    id="accepts_corporate"
                                    type="checkbox"
                                    className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    checked={data.accepts_corporate}
                                    onChange={(e) => setData('accepts_corporate', e.target.checked)}
                                />
                                <span className="ml-2 text-sm text-gray-900">Accept Corporate Bookings</span>
                            </label>
                            <label className="flex items-center">
                                <input
                                    id="accepts_donation"
                                    type="checkbox"
                                    className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    checked={data.accepts_donation}
                                    onChange={(e) => setData('accepts_donation', e.target.checked)}
                                />
                                <span className="ml-2 text-sm text-gray-900">Accept Donation Bookings</span>
                            </label>
                        </div>
                        <InputError message={errors.accepts_corporate || errors.accepts_donation} className="mt-2" />

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                {warehouse ? 'Update' : 'Create'} Warehouse
                            </PrimaryButton>
                            <SecondaryButton type="button" onClick={() => window.history.back()}>
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
