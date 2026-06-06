import AdminLayout from '@/Layouts/AdminLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

export default function Form({ customer }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: customer?.name || '',
        mobile: customer?.mobile || '',
        address: customer?.address || '',
        city: customer?.city || '',
        pincode: customer?.pincode || '',
        landmark: customer?.landmark || '',
        latitude: customer?.latitude || '',
        longitude: customer?.longitude || '',
    });

    const submit = (e) => {
        e.preventDefault();
        customer
            ? put(route('admin.partner-customers.update', customer.id))
            : post(route('admin.partner-customers.store'));
    };

    const field = (name, label, type = 'text') => (
        <div>
            <InputLabel htmlFor={name} value={label} />
            <TextInput
                id={name}
                type={name === 'mobile' ? 'tel' : type}
                inputMode={name === 'mobile' ? 'numeric' : undefined}
                maxLength={name === 'mobile' ? 10 : undefined}
                pattern={name === 'mobile' ? '[6-9][0-9]{9}' : undefined}
                className="mt-1 block w-full"
                value={data[name] || ''}
                onChange={(e) => setData(name, name === 'mobile' ? e.target.value.replace(/\D/g, '').slice(0, 10) : e.target.value)}
            />
            <InputError message={errors[name]} className="mt-2" />
        </div>
    );

    return (
        <AdminLayout>
            <Head title={customer ? 'Edit Customer' : 'Add Customer'} />
            <h1 className="text-3xl font-semibold text-gray-800 mb-6">{customer ? 'Edit Customer' : 'Add Customer'}</h1>

            <form onSubmit={submit} className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                {field('name', 'Name')}
                {field('mobile', 'Mobile')}
                <div className="md:col-span-2">
                    <InputLabel htmlFor="address" value="Address" />
                    <textarea id="address" className="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="3" value={data.address || ''} onChange={(e) => setData('address', e.target.value)} />
                    <InputError message={errors.address} className="mt-2" />
                </div>
                {field('city', 'City')}
                {field('pincode', 'Pincode')}
                {field('landmark', 'Landmark')}
                {field('latitude', 'Latitude', 'number')}
                {field('longitude', 'Longitude', 'number')}
                <div className="md:col-span-2">
                    <PrimaryButton disabled={processing}>{customer ? 'Update Customer' : 'Create Customer'}</PrimaryButton>
                </div>
            </form>
        </AdminLayout>
    );
}
