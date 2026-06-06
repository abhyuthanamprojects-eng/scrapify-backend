import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

export default function AddressManager({ user, states }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingAddress, setEditingAddress] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        title: '',
        address_line_1: '',
        address_line_2: '',
        pincode: '',
        state_id: '',
        city_id: '',
        state: '',
        latitude: '',
        longitude: '',
        is_default: false,
    });

    const openModal = (address = null) => {
        setEditingAddress(address);
        if (address) {
            // Find the state ID based on the city ID if the address has one
            const foundState = address.city?.state_id ? address.city.state_id : '';
            
            setData({
                title: address.title || '',
                address_line_1: address.address_line_1 || '',
                address_line_2: address.address_line_2 || '',
                pincode: address.pincode || '',
                state_id: foundState,
                city_id: address.city_id || '',
                state: address.state || '',
                latitude: address.latitude || '',
                longitude: address.longitude || '',
                is_default: !!address.is_default,
            });
        } else {
            reset();
        }
        clearErrors();
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
        clearErrors();
    };

    const availableCities = states.find(s => s.id == data.state_id)?.cities || [];

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (editingAddress) {
            put(route('admin.users.addresses.update', { user: user.id, address: editingAddress.id }), {
                onSuccess: () => closeModal(),
            });
        } else {
            post(route('admin.users.addresses.store', user.id), {
                onSuccess: () => closeModal(),
            });
        }
    };

    const handleDelete = (addressId) => {
        if (confirm('Are you sure you want to delete this address?')) {
            router.delete(route('admin.users.addresses.destroy', { user: user.id, address: addressId }));
        }
    };

    return (
        <div className="mt-8 bg-white shadow-md rounded-lg p-6">
            <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-semibold text-gray-800">Customer Addresses</h2>
                <PrimaryButton onClick={() => openModal()}>Add New Address</PrimaryButton>
            </div>

            {(!user.addresses || user.addresses.length === 0) ? (
                <div className="text-center py-6 text-gray-500">
                    No addresses found for this user.
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {user.addresses.map((address) => (
                        <div key={address.id} className="border rounded-lg p-4 relative bg-gray-50">
                            {address.is_default && (
                                <span className="absolute top-2 right-2 bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded">
                                    Default
                                </span>
                            )}
                            <h3 className="font-semibold text-lg text-gray-800 mb-1">
                                {address.title || 'Address'}
                            </h3>
                            <p className="text-gray-600 text-sm mb-1">{address.address_line_1}</p>
                            {address.address_line_2 && <p className="text-gray-600 text-sm mb-1">{address.address_line_2}</p>}
                            <p className="text-gray-600 text-sm font-medium mb-3">
                                {address.city?.name}, {address.state} - {address.pincode}
                            </p>
                            
                            <div className="flex gap-2">
                                <SecondaryButton onClick={() => openModal(address)} className="!py-1 !px-3 text-xs">
                                    Edit
                                </SecondaryButton>
                                <DangerButton onClick={() => handleDelete(address.id)} className="!py-1 !px-3 text-xs">
                                    Delete
                                </DangerButton>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <Modal show={isModalOpen} onClose={closeModal}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">
                        {editingAddress ? 'Edit Address' : 'Add New Address'}
                    </h2>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <InputLabel htmlFor="title" value="Title (Optional)" />
                            <TextInput
                                id="title"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                placeholder="e.g. Home, Office"
                            />
                            <InputError message={errors.title} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="address_line_1" value="Address Line 1" />
                            <TextInput
                                id="address_line_1"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.address_line_1}
                                onChange={(e) => setData('address_line_1', e.target.value)}
                                required
                            />
                            <InputError message={errors.address_line_1} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="address_line_2" value="Address Line 2 (Optional)" />
                            <TextInput
                                id="address_line_2"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.address_line_2}
                                onChange={(e) => setData('address_line_2', e.target.value)}
                            />
                            <InputError message={errors.address_line_2} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="state_id" value="State" />
                                <select
                                    id="state_id"
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    value={data.state_id}
                                    onChange={(e) => {
                                        const selectedState = states.find(s => s.id == e.target.value);
                                        setData(data => ({
                                            ...data,
                                            state_id: e.target.value,
                                            state: selectedState ? selectedState.name : '',
                                            city_id: ''
                                        }));
                                    }}
                                    required
                                >
                                    <option value="">Select State</option>
                                    {states.map(state => (
                                        <option key={state.id} value={state.id}>{state.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <InputLabel htmlFor="city_id" value="City" />
                                <select
                                    id="city_id"
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:opacity-50 disabled:bg-gray-100"
                                    value={data.city_id}
                                    onChange={(e) => setData('city_id', e.target.value)}
                                    required
                                    disabled={!data.state_id}
                                >
                                    <option value="">Select City</option>
                                    {availableCities.map(city => (
                                        <option key={city.id} value={city.id}>{city.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.city_id} className="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="pincode" value="Pincode" />
                            <TextInput
                                id="pincode"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.pincode}
                                onChange={(e) => setData('pincode', e.target.value)}
                                required
                            />
                            <InputError message={errors.pincode} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="latitude" value="Latitude (Optional)" />
                                <TextInput
                                    id="latitude"
                                    type="number"
                                    step="any"
                                    className="mt-1 block w-full"
                                    value={data.latitude}
                                    onChange={(e) => setData('latitude', e.target.value)}
                                    placeholder="e.g. 19.0760"
                                />
                                <InputError message={errors.latitude} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="longitude" value="Longitude (Optional)" />
                                <TextInput
                                    id="longitude"
                                    type="number"
                                    step="any"
                                    className="mt-1 block w-full"
                                    value={data.longitude}
                                    onChange={(e) => setData('longitude', e.target.value)}
                                    placeholder="e.g. 72.8777"
                                />
                                <InputError message={errors.longitude} className="mt-2" />
                            </div>
                        </div>

                        <div className="flex items-center mt-4">
                            <input
                                id="is_default"
                                type="checkbox"
                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-primary focus:ring-offset-0"
                                checked={data.is_default}
                                onChange={(e) => setData('is_default', e.target.checked)}
                            />
                            <label htmlFor="is_default" className="ml-2 block text-sm text-gray-900">
                                Set as Default Address
                            </label>
                        </div>

                        <div className="mt-6 flex justify-end gap-3">
                            <SecondaryButton onClick={closeModal} disabled={processing}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton disabled={processing}>
                                {editingAddress ? 'Save Changes' : 'Add Address'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    );
}
