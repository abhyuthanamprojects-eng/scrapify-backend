import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Form({ category, types, attributes = [], selectedAttributeIds = [], optionPricingRules = [] }) {
    const [previewUrl, setPreviewUrl] = useState(category?.image_url || null);
    const [optionRules, setOptionRules] = useState(() => {
        if (optionPricingRules.length > 0) {
            return optionPricingRules.map((r) => ({
                attribute_option_id: Number(r.attribute_option_id),
                adjustment_percent: r.adjustment_percent ?? '',
                pricing_type: r.pricing_type || category?.pricing_rules?.[0]?.pricing_type || 'per_piece',
            }));
        }
        return [];
    });

    const { data, setData, post, processing, errors, transform } = useForm({
        name: {
            en: category?.name?.en || '',
            hi: category?.name?.hi || '',
        },
        slug: category?.slug || '',
        category_type_id: category?.category_type_id || (types && types.length > 0 ? types[0].id : ''),
        status: category?.status ?? true,
        requires_details: category?.requires_details ?? (selectedAttributeIds.length > 0),
        selected_attribute_ids: selectedAttributeIds || [],
        pricing_type: category?.pricing_rules?.[0]?.pricing_type || 'per_piece',
        base_price: category?.pricing_rules?.[0]?.base_price || '',
        image: null,
        remove_image: false,
        option_pricing_rules: [],
    });

    const selectedAttributeIdNumbers = (data.selected_attribute_ids || []).map((id) => Number(id));
    const selectedAttributes = attributes.filter((attribute) =>
        selectedAttributeIdNumbers.includes(Number(attribute.id))
    );
    const allowedOptionIds = new Set(
        selectedAttributes.flatMap((attribute) => (attribute.options || []).map((option) => Number(option.id)))
    );

    useEffect(() => {
        setOptionRules((prev) =>
            prev.filter((rule) => allowedOptionIds.has(Number(rule.attribute_option_id)))
        );
    }, [data.selected_attribute_ids]);

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
        const normalizedOptionRules = optionRules
            .filter((rule) =>
                rule.attribute_option_id &&
                allowedOptionIds.has(Number(rule.attribute_option_id)) &&
                rule.adjustment_percent !== '' &&
                rule.adjustment_percent !== null
            )
            .map((rule) => ({
                attribute_option_id: Number(rule.attribute_option_id),
                adjustment_percent: rule.adjustment_percent,
                pricing_type: rule.pricing_type || data.pricing_type,
            }));

        transform((formData) => ({
            ...formData,
            option_pricing_rules: normalizedOptionRules,
        }));
        
        if (category) {
            transform((data) => ({
                ...data,
                option_pricing_rules: normalizedOptionRules,
                _method: 'put',
            }));
            post(route('admin.categories.update', category.id));
        } else {
            post(route('admin.categories.store'));
        }
    };

    const groupedOptions = selectedAttributes.map((attribute) => ({
        attribute_id: Number(attribute.id),
        attribute_name: attribute.name,
        options: (attribute.options || []).map((option) => ({
            id: Number(option.id),
            value: option.value,
        })),
    }));

    const updateOptionRule = (attributeOptionId, field, value) => {
        setOptionRules((prev) => {
            const existing = prev.find((r) => Number(r.attribute_option_id) === Number(attributeOptionId));
            if (existing) {
                return prev.map((r) =>
                    Number(r.attribute_option_id) === Number(attributeOptionId)
                        ? { ...r, [field]: value }
                        : r
                );
            }
            return [
                ...prev,
                {
                    attribute_option_id: Number(attributeOptionId),
                    adjustment_percent: field === 'adjustment_percent' ? value : '',
                    pricing_type: field === 'pricing_type' ? value : data.pricing_type,
                },
            ];
        });
    };

    const getOptionRule = (attributeOptionId) => {
        return optionRules.find((r) => Number(r.attribute_option_id) === Number(attributeOptionId));
    };

    const toggleSelectedAttribute = (attributeId, checked) => {
        const id = Number(attributeId);
        const existing = (data.selected_attribute_ids || []).map((value) => Number(value));
        const next = checked
            ? Array.from(new Set([...existing, id]))
            : existing.filter((value) => value !== id);
        setData('selected_attribute_ids', next);
        if (!checked) {
            setData('requires_details', next.length > 0 ? data.requires_details : false);
        }
    };

    return (
        <AdminLayout>
            <Head title={category ? 'Edit Category' : 'Create Category'} />

            <div className="max-w-2xl">
                <h1 className="text-3xl font-semibold text-gray-800 mb-6">
                    {category ? 'Edit Category' : 'Create New Category'}
                </h1>

                <div className="bg-white shadow-md rounded-lg p-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="name_en" value="Name (English)" />
                            <TextInput
                                id="name_en"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.name.en}
                                onChange={(e) => setData('name', { ...data.name, en: e.target.value })}
                                required
                            />
                            <InputError message={errors['name.en']} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="name_hi" value="Name (Hindi)" />
                            <TextInput
                                id="name_hi"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.name.hi}
                                onChange={(e) => setData('name', { ...data.name, hi: e.target.value })}
                            />
                            <InputError message={errors['name.hi']} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="slug" value="Slug" />
                            <TextInput
                                id="slug"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                                required
                            />
                            <InputError message={errors.slug} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="category_type_id" value="Type" />
                            <select
                                id="category_type_id"
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                value={data.category_type_id}
                                onChange={(e) => setData('category_type_id', e.target.value)}
                                required
                            >
                                <option value="">Select a Type</option>
                                {types && types.map((t) => (
                                    <option key={t.id} value={t.id}>{t.name?.en || t.name}</option>
                                ))}
                            </select>
                            <InputError message={errors.category_type_id} className="mt-2" />
                        </div>

                        <div className="flex items-center gap-3">
                            <input
                                id="requires_details"
                                type="checkbox"
                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-primary focus:ring-offset-0"
                                checked={data.requires_details}
                                onChange={(e) => setData('requires_details', e.target.checked)}
                            />
                            <label htmlFor="requires_details" className="block text-sm text-gray-900">
                                Requires details flow (show attribute questions before add-to-basket)
                            </label>
                        </div>
                        <InputError message={errors.requires_details} className="mt-2" />

                        <div>
                            <InputLabel value="Map Attributes to Category" />
                            <p className="text-sm text-gray-500 mt-1">
                                Select only attributes that apply to this category. Percentage rules are limited to these selected attributes.
                            </p>
                            <div className="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                                {attributes.map((attribute) => {
                                    const checked = selectedAttributeIdNumbers.includes(Number(attribute.id));
                                    return (
                                        <label
                                            key={attribute.id}
                                            className="flex items-start gap-3 border border-gray-200 rounded-md px-3 py-2"
                                        >
                                            <input
                                                type="checkbox"
                                                className="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-primary focus:ring-offset-0"
                                                checked={checked}
                                                onChange={(e) => toggleSelectedAttribute(attribute.id, e.target.checked)}
                                            />
                                            <span className="text-sm text-gray-800">
                                                {attribute.name}
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>
                            <InputError message={errors.selected_attribute_ids} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="pricing_type" value="Pricing Type" />
                            <select
                                id="pricing_type"
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                value={data.pricing_type}
                                onChange={(e) => setData('pricing_type', e.target.value)}
                                required
                            >
                                <option value="per_kg">Per KG</option>
                                <option value="per_piece">Per Piece</option>
                                <option value="per_capacity">Per Capacity</option>
                            </select>
                            <InputError message={errors.pricing_type} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="base_price" value="Base Price (₹)" />
                            <TextInput
                                id="base_price"
                                type="number"
                                step="0.01"
                                className="mt-1 block w-full"
                                value={data.base_price}
                                onChange={(e) => setData('base_price', e.target.value)}
                                required
                            />
                            <InputError message={errors.base_price} className="mt-2" />
                        </div>

                        {groupedOptions.length > 0 && (
                            <div>
                                <InputLabel value="Option-Specific Pricing Rules (Optional)" />
                                <p className="text-sm text-gray-500 mt-1">
                                    Set per-option percentage adjustments for dynamic estimate (material type, pickup size, condition, etc).
                                </p>
                                <div className="mt-3 space-y-4">
                                    {groupedOptions.map((group) => (
                                        <div key={group.attribute_id} className="border rounded-md p-3">
                                            <p className="font-medium text-gray-800 mb-2">{group.attribute_name}</p>
                                            <div className="space-y-2">
                                                {group.options.map((option) => {
                                                    const rule = getOptionRule(option.id);
                                                    return (
                                                        <div key={option.id} className="grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
                                                            <div className="text-sm text-gray-700">{option.value}</div>
                                                            <TextInput
                                                                type="number"
                                                                step="0.01"
                                                                placeholder="e.g. +10 or -5"
                                                                value={rule?.adjustment_percent ?? ''}
                                                                onChange={(e) => updateOptionRule(option.id, 'adjustment_percent', e.target.value)}
                                                            />
                                                            <select
                                                                className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                                value={rule?.pricing_type || data.pricing_type}
                                                                onChange={(e) => updateOptionRule(option.id, 'pricing_type', e.target.value)}
                                                            >
                                                                <option value="per_kg">Per KG</option>
                                                                <option value="per_piece">Per Piece</option>
                                                                <option value="per_capacity">Per Capacity</option>
                                                            </select>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <InputError message={errors.option_pricing_rules} className="mt-2" />
                            </div>
                        )}

                        <div className="flex items-center">
                            <input
                                id="status"
                                type="checkbox"
                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-primary focus:ring-offset-0"
                                checked={data.status}
                                onChange={(e) => setData('status', e.target.checked)}
                            />
                            <label htmlFor="status" className="ml-2 block text-sm text-gray-900">
                                Active
                            </label>
                        </div>

                        <div>
                            <InputLabel htmlFor="image" value="Category Image" />
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
                            <InputError message={errors.image} className="mt-2" />
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                {category ? 'Update' : 'Create'} Category
                            </PrimaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={() => window.history.back()}
                            >
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
