import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function PartnerRegister({ states }) {
    const { data, setData, post, processing, errors } = useForm({
        full_name: '',
        phone: '',
        email: '',
        business_name: '',
        aadhaar_number: '',
        pan_number: '',
        gst_number: '',
        address: '',
        city: '',
        state: '',
        pincode: '',
        opening_location_name: '',
        latitude: '',
        longitude: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('partner.register'));
    };

    return (
        <GuestLayout>
            <Head title="Channel Partner Registration" />

            <div className="mb-8 text-center">
                <h1 className="text-2xl font-bold text-gray-900">Become a Channel Partner</h1>
                <p className="text-gray-600 mt-2">Join Scrapify and start your sustainable e-waste business today.</p>
            </div>

            <form onSubmit={submit} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Personal & Business Info */}
                    <div className="space-y-4">
                        <h3 className="font-semibold text-indigo-600 border-b pb-1">Primary Details</h3>
                        
                        <div>
                            <InputLabel htmlFor="full_name" value="Full Name" />
                            <TextInput id="full_name" className="mt-1 block w-full" value={data.full_name} onChange={(e) => setData('full_name', e.target.value)} required />
                            <InputError message={errors.full_name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="business_name" value="Business / Firm Name" />
                            <TextInput id="business_name" className="mt-1 block w-full" value={data.business_name} onChange={(e) => setData('business_name', e.target.value)} required />
                            <InputError message={errors.business_name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="email" value="Email Address" />
                            <TextInput id="email" type="email" className="mt-1 block w-full" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="phone" value="Phone Number" />
                            <TextInput
                                id="phone"
                                type="tel"
                                inputMode="numeric"
                                maxLength={10}
                                pattern="[6-9][0-9]{9}"
                                className="mt-1 block w-full"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value.replace(/\D/g, '').slice(0, 10))}
                                required
                            />
                            <InputError message={errors.phone} className="mt-2" />
                        </div>
                    </div>

                    {/* Identity & Tax */}
                    <div className="space-y-4">
                        <h3 className="font-semibold text-indigo-600 border-b pb-1">KYC & Tax Details</h3>
                        
                        <div>
                            <InputLabel htmlFor="aadhaar_number" value="Aadhaar Card Number" />
                            <TextInput id="aadhaar_number" className="mt-1 block w-full" value={data.aadhaar_number} onChange={(e) => setData('aadhaar_number', e.target.value)} required maxLength="12" />
                            <InputError message={errors.aadhaar_number} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="pan_number" value="PAN Card Number" />
                            <TextInput id="pan_number" className="mt-1 block w-full uppercase" value={data.pan_number} onChange={(e) => setData('pan_number', e.target.value.toUpperCase())} required maxLength="10" />
                            <InputError message={errors.pan_number} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="gst_number" value="GST Number (Optional)" />
                            <TextInput id="gst_number" className="mt-1 block w-full uppercase" value={data.gst_number} onChange={(e) => setData('gst_number', e.target.value.toUpperCase())} />
                            <InputError message={errors.gst_number} className="mt-2" />
                        </div>
                    </div>
                </div>

                <div className="space-y-4 pt-4 border-t">
                    <h3 className="font-semibold text-indigo-600 border-b pb-1">Business Location</h3>
                    
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel htmlFor="state" value="State" />
                            <select 
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                value={data.state}
                                onChange={(e) => setData('state', e.target.value)}
                                required
                            >
                                <option value="">Select State</option>
                                {states.map(s => <option key={s.id} value={s.name}>{s.name}</option>)}
                            </select>
                        </div>

                        <div>
                            <InputLabel htmlFor="city" value="City" />
                            <TextInput id="city" className="mt-1 block w-full" value={data.city} onChange={(e) => setData('city', e.target.value)} required />
                        </div>

                        <div>
                            <InputLabel htmlFor="pincode" value="Pincode" />
                            <TextInput id="pincode" className="mt-1 block w-full" value={data.pincode} onChange={(e) => setData('pincode', e.target.value)} required maxLength="6" />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="address" value="Business address" />
                        <textarea 
                            id="address"
                            className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="3"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            required
                        ></textarea>
                        <InputError message={errors.address} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="opening_location_name" value="Proposed Service Area / Main Landmark" />
                        <TextInput id="opening_location_name" className="mt-1 block w-full" value={data.opening_location_name} onChange={(e) => setData('opening_location_name', e.target.value)} required placeholder="e.g. Sector 12, Main Market" />
                        <InputError message={errors.opening_location_name} className="mt-2" />
                    </div>
                </div>

                <div className="flex items-center justify-between pt-6">
                    <Link href={route('login')} className="text-sm text-gray-600 hover:text-indigo-600 transition-colors">
                        Back to Login
                    </Link>

                    <PrimaryButton className="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 transition-all rounded-full" disabled={processing}>
                        Submit Registration
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
