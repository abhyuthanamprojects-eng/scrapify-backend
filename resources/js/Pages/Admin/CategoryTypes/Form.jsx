import React, { useState, useEffect } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Form({ type }) {
    const isEditing = !!type;
    const [previewUrl, setPreviewUrl] = useState(type?.image_url || null);

    const { data, setData, post, processing, errors, transform } = useForm({
        name: {
            en: type?.name?.en || type?.name || '',
            hi: type?.name?.hi || '',
        },
        status: type?.status ?? true,
        show_in_corporate_booking: type?.show_in_corporate_booking ?? false,
        image: null,
        remove_image: false,
    });

    useEffect(() => {
        if (data.image) {
            const url = URL.createObjectURL(data.image);
            setPreviewUrl(url);
            setData('remove_image', false);
            return () => URL.revokeObjectURL(url);
        }
    }, [data.image]);

    const handleRemoveImage = () => {
        setData((prevData) => ({
            ...prevData,
            image: null,
            remove_image: true,
        }));
        setPreviewUrl(null);
        // Clear file input
        const fileInput = document.getElementById('image');
        if (fileInput) fileInput.value = '';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (isEditing) {
            // Using post with _method=PUT to support multipart/form-data images in Laravel updates
            transform((data) => ({
                ...data,
                _method: 'PUT',
            }));
            post(route('admin.category-types.update', type.id), {
                forceFormData: true,
            });
        } else {
            post(route('admin.category-types.store'));
        }
    };

    return (
        <AdminLayout>
            <Head title={isEditing ? 'Edit Category Type' : 'Create Category Type'} />

            <div className="max-w-2xl">
                <div className="mb-6 flex justify-between items-center">
                    <h1 className="text-3xl font-semibold text-gray-800">
                        {isEditing ? `Edit Type: ${type.name?.en || type.name}` : 'Create New Category Type'}
                    </h1>
                    <Link href={route('admin.category-types.index')}>
                        <SecondaryButton>Back to List</SecondaryButton>
                    </Link>
                </div>

                <div className="bg-white shadow-md rounded-lg p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="name_en" value="Type Name (English)" />
                            <TextInput
                                id="name_en"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.name.en}
                                onChange={(e) => setData('name', { ...data.name, en: e.target.value })}
                                placeholder="e.g. Electronics"
                                required
                            />
                            <p className="mt-1 text-xs text-gray-500">The slug will be generated automatically from the English name.</p>
                            <InputError message={errors['name.en']} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="name_hi" value="Type Name (Hindi)" />
                            <TextInput
                                id="name_hi"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.name.hi}
                                onChange={(e) => setData('name', { ...data.name, hi: e.target.value })}
                                placeholder="e.g. ई-कचरा"
                            />
                            <InputError message={errors['name.hi']} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="image" value="Icon / Image" />
                            {previewUrl && (
                                <div className="mt-2 mb-4 relative inline-block">
                                    <img 
                                        src={previewUrl} 
                                        alt="Preview" 
                                        className="h-24 w-24 object-cover rounded-md border border-gray-100 shadow-sm"
                                    />
                                    <button
                                        type="button"
                                        onClick={handleRemoveImage}
                                        className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-md hover:bg-red-600 transition-colors"
                                        title="Remove image"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            )}
                            <input
                                id="image"
                                type="file"
                                className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100"
                                onChange={(e) => setData('image', e.target.files[0])}
                                accept="image/*"
                            />
                            <p className="mt-1 text-xs text-gray-500">Recommended size: 200x200px (PNG/JPG, max 2MB).</p>
                            <InputError message={errors.image} className="mt-2" />
                        </div>

                        <div className="flex items-center">
                            <input
                                id="status"
                                type="checkbox"
                                className="rounded border-gray-300 text-primary shadow-sm focus:ring-primary"
                                checked={data.status}
                                onChange={(e) => setData('status', e.target.checked)}
                            />
                            <label htmlFor="status" className="ml-2 block text-sm text-gray-900">
                                Active Category Type
                            </label>
                            <InputError message={errors.status} className="mt-2" />
                        </div>

                        <div className="flex items-center">
                            <input
                                id="show_in_corporate_booking"
                                type="checkbox"
                                className="rounded border-gray-300 text-primary shadow-sm focus:ring-primary"
                                checked={data.show_in_corporate_booking}
                                onChange={(e) => setData('show_in_corporate_booking', e.target.checked)}
                            />
                            <label htmlFor="show_in_corporate_booking" className="ml-2 block text-sm text-gray-900">
                                Show In Corporate Booking
                            </label>
                            <InputError message={errors.show_in_corporate_booking} className="mt-2" />
                        </div>

                        <div className="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-100">
                            <Link href={route('admin.category-types.index')}>
                                <SecondaryButton disabled={processing}>Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton disabled={processing}>
                                {isEditing ? 'Update Type' : 'Create Type'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
