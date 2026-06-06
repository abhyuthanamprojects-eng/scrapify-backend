import AdminLayout from '@/Layouts/AdminLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const steps = [
    'Customer Details',
    'Request Type',
    'Category & Subcategory',
    'Item Details',
    'Pickup Details',
    'Review & Submit',
];

const emptyItem = {
    category_id: '',
    subcategory_id: '',
    product_name: '',
    quantity: 1,
    unit: 'kg',
    weight: '',
    condition: '',
    estimated_price: '',
    remarks: '',
};

export default function Form({ customers, categories }) {
    const [step, setStep] = useState(0);
    const { data, setData, post, processing, errors } = useForm({
        customer_type: 'individual',
        request_type: 'basic_scrap',
        customer_id: customers[0]?.id || '',
        customer: { name: '', mobile: '', address: '', city: '', pincode: '', landmark: '', latitude: '', longitude: '' },
        address: customers[0]?.address || '',
        latitude: '',
        longitude: '',
        scheduled_at: '',
        notes: '',
        images: [],
        items: [emptyItem],
    });

    const selectedCustomer = customers.find((customer) => Number(customer.id) === Number(data.customer_id));
    const categoryById = useMemo(() => Object.fromEntries(categories.map((c) => [String(c.id), c])), [categories]);

    const setItem = (index, key, value) => {
        const items = [...data.items];
        items[index] = { ...items[index], [key]: value };
        if (key === 'category_id') items[index].subcategory_id = '';
        setData('items', items);
    };

    const addItem = () => setData('items', [...data.items, { ...emptyItem }]);
    const removeItem = (index) => {
        if (data.items.length === 1) return;
        setData('items', data.items.filter((_, i) => i !== index));
    };

    const validateStep = () => {
        if (step === 0 && !data.customer_id && (!data.customer.name || !data.customer.mobile)) return false;
        if (step === 2 && data.items.some((i) => !i.category_id)) return false;
        if (step === 3 && data.items.some((i) => !i.quantity || Number(i.quantity) <= 0 || !i.unit)) return false;
        if (step === 4 && (!data.address || !data.scheduled_at)) return false;
        return true;
    };

    const next = () => validateStep() && setStep((s) => Math.min(s + 1, steps.length - 1));
    const back = () => setStep((s) => Math.max(s - 1, 0));
    const submit = (e) => {
        e.preventDefault();
        post(route('admin.partner-pickups.store'), { forceFormData: true });
    };

    return (
        <AdminLayout>
            <Head title="Create Pickup Request" />
            <h1 className="text-3xl font-semibold text-gray-800 mb-2">Create Pickup Request</h1>
            <p className="text-sm text-gray-500 mb-6">Step {step + 1} of {steps.length}: {steps[step]}</p>

            <form onSubmit={submit} className="space-y-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div className="grid grid-cols-2 md:grid-cols-6 gap-2">
                        {steps.map((label, i) => (
                            <button key={label} type="button" onClick={() => i <= step && setStep(i)} className={`text-xs rounded-lg px-2 py-2 border ${i === step ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200'}`}>
                                {i + 1}. {label}
                            </button>
                        ))}
                    </div>
                </div>

                {step === 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <InputLabel value="Customer" />
                            <select className="mt-1 block w-full border-gray-300 rounded-md shadow-sm" value={data.customer_id} onChange={(e) => {
                                const customer = customers.find((c) => Number(c.id) === Number(e.target.value));
                                setData({
                                    ...data,
                                    customer_id: e.target.value,
                                    address: customer?.address || data.address,
                                    customer: { ...data.customer, name: customer?.name || '', mobile: customer?.mobile || '' },
                                });
                            }}>
                                <option value="">Select customer</option>
                                {customers.map((customer) => <option key={customer.id} value={customer.id}>{customer.name} - {customer.mobile}</option>)}
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Customer Type" />
                            <select className="mt-1 block w-full border-gray-300 rounded-md shadow-sm" value={data.customer_type} onChange={(e) => setData('customer_type', e.target.value)}>
                                <option value="individual">Individual</option>
                                <option value="corporate">Corporate</option>
                            </select>
                        </div>
                    </div>
                )}

                {step === 1 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button type="button" onClick={() => setData('request_type', 'basic_scrap')} className={`text-left rounded-xl border p-4 ${data.request_type === 'basic_scrap' ? 'border-primary bg-primary/5' : 'border-gray-200 bg-white'}`}>
                            <p className="font-semibold text-gray-900">Basic Scrap Request</p>
                            <p className="text-sm text-gray-500">Standard pickup for household/general scrap.</p>
                        </button>
                        <button type="button" onClick={() => setData('request_type', 'corporate')} className={`text-left rounded-xl border p-4 ${data.request_type === 'corporate' ? 'border-primary bg-primary/5' : 'border-gray-200 bg-white'}`}>
                            <p className="font-semibold text-gray-900">Corporate Request</p>
                            <p className="text-sm text-gray-500">Business/corporate bulk pickup workflow.</p>
                        </button>
                    </div>
                )}

                {(step === 2 || step === 3) && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                        <div className="flex justify-between items-center">
                            <h2 className="text-lg font-semibold text-gray-800">Item Lines</h2>
                            <button type="button" className="text-primary font-semibold" onClick={addItem}>Add Item</button>
                        </div>
                        {data.items.map((item, index) => {
                            const selectedCategory = categoryById[String(item.category_id)];
                            const subcategories = selectedCategory?.children || [];
                            return (
                                <div key={index} className="grid grid-cols-1 md:grid-cols-3 gap-4 border border-gray-100 rounded-lg p-4">
                                    <select className="border-gray-300 rounded-md shadow-sm" value={item.category_id} onChange={(e) => setItem(index, 'category_id', e.target.value)}>
                                        <option value="">Select category</option>
                                        {categories.map((category) => <option key={category.id} value={category.id}>{category.name?.en || category.name}</option>)}
                                    </select>
                                    <select className="border-gray-300 rounded-md shadow-sm" value={item.subcategory_id} onChange={(e) => setItem(index, 'subcategory_id', e.target.value)}>
                                        <option value="">Select subcategory</option>
                                        {subcategories.map((sub) => <option key={sub.id} value={sub.id}>{sub.name?.en || sub.name}</option>)}
                                    </select>
                                    <TextInput placeholder="Product name" value={item.product_name} onChange={(e) => setItem(index, 'product_name', e.target.value)} />
                                    <TextInput placeholder="Quantity" type="number" step="0.01" value={item.quantity} onChange={(e) => setItem(index, 'quantity', e.target.value)} />
                                    <select className="border-gray-300 rounded-md shadow-sm" value={item.unit} onChange={(e) => setItem(index, 'unit', e.target.value)}>
                                        <option value="kg">kg</option>
                                        <option value="piece">piece</option>
                                        <option value="lot">lot</option>
                                    </select>
                                    <TextInput placeholder="Estimated weight (kg)" type="number" step="0.01" value={item.weight} onChange={(e) => setItem(index, 'weight', e.target.value)} />
                                    <TextInput placeholder="Condition" value={item.condition} onChange={(e) => setItem(index, 'condition', e.target.value)} />
                                    <TextInput placeholder="Estimated price" type="number" step="0.01" value={item.estimated_price} onChange={(e) => setItem(index, 'estimated_price', e.target.value)} />
                                    <textarea className="md:col-span-3 border-gray-300 rounded-md shadow-sm" placeholder="Remarks" value={item.remarks} onChange={(e) => setItem(index, 'remarks', e.target.value)} />
                                    <div className="md:col-span-3 text-right">
                                        <button type="button" className="text-red-600 text-sm font-medium" onClick={() => removeItem(index)}>Remove</button>
                                    </div>
                                </div>
                            );
                        })}
                        <InputError message={errors.items} />
                    </div>
                )}

                {step === 4 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <InputLabel value="Expected Pickup Date/Time" />
                            <TextInput type="datetime-local" className="mt-1 block w-full" value={data.scheduled_at} onChange={(e) => setData('scheduled_at', e.target.value)} />
                            <InputError message={errors.scheduled_at} className="mt-2" />
                        </div>
                        <div className="md:col-span-2">
                            <InputLabel value="Pickup Address" />
                            <textarea className="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="3" value={data.address} onChange={(e) => setData('address', e.target.value)} />
                            <p className="text-xs text-gray-500 mt-1">{selectedCustomer?.landmark ? `Landmark: ${selectedCustomer.landmark}` : ''}</p>
                        </div>
                        <TextInput placeholder="Latitude" value={data.latitude} onChange={(e) => setData('latitude', e.target.value)} />
                        <TextInput placeholder="Longitude" value={data.longitude} onChange={(e) => setData('longitude', e.target.value)} />
                        <div className="md:col-span-2">
                            <InputLabel value="Notes" />
                            <textarea className="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="3" value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                        </div>
                        <div className="md:col-span-2">
                            <InputLabel value="Item Images" />
                            <input type="file" multiple accept="image/*" className="mt-1 block w-full" onChange={(e) => setData('images', Array.from(e.target.files))} />
                        </div>
                    </div>
                )}

                {step === 5 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-sm text-gray-700 space-y-2">
                        <p><span className="font-semibold">Customer:</span> {selectedCustomer?.name || data.customer.name || '-'}</p>
                        <p><span className="font-semibold">Customer Type:</span> {data.customer_type}</p>
                        <p><span className="font-semibold">Request Type:</span> {data.request_type}</p>
                        <p><span className="font-semibold">Items:</span> {data.items.length}</p>
                        <p><span className="font-semibold">Pickup Time:</span> {data.scheduled_at || '-'}</p>
                        <p><span className="font-semibold">Address:</span> {data.address || '-'}</p>
                    </div>
                )}

                <div className="flex items-center justify-between">
                    <button type="button" onClick={back} disabled={step === 0} className="px-4 py-2 rounded-md border border-gray-300 text-gray-700 disabled:opacity-40">Back</button>
                    {step < steps.length - 1 ? (
                        <button type="button" onClick={next} className="px-4 py-2 rounded-md bg-primary text-white">Next</button>
                    ) : (
                        <PrimaryButton disabled={processing}>Submit Pickup Request</PrimaryButton>
                    )}
                </div>
            </form>
        </AdminLayout>
    );
}
