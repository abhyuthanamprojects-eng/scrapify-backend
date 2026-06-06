import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useState } from 'react';

export default function Form({ attribute, categories }) {
    const [options, setOptions] = useState(
        attribute?.options?.map(opt => opt.value?.en || opt.value) || ['']
    );

    const { data, setData, post, put, processing, errors } = useForm({
        name: {
            en: attribute?.name?.en || '',
            hi: attribute?.name?.hi || '',
        },
        code: attribute?.code || '',
        type: attribute?.type || 'text',
        is_required: attribute?.is_required ?? false,
        options: options,
        categories: attribute?.categories?.map(c => c.id) || [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        
        const submitData = {
            ...data,
            options: data.type === 'select' || data.type === 'radio' || data.type === 'checkbox' 
                ? options.filter(opt => opt.trim() !== '') 
                : []
        };
        
        if (attribute) {
            put(route('admin.attributes.update', attribute.id), { data: submitData });
        } else {
            post(route('admin.attributes.store'), { data: submitData });
        }
    };

    const addOption = () => {
        setOptions([...options, '']);
    };

    const removeOption = (index) => {
        setOptions(options.filter((_, i) => i !== index));
    };

    const updateOption = (index, value) => {
        const newOptions = [...options];
        newOptions[index] = value;
        setOptions(newOptions);
    };

    const needsOptions = ['select', 'radio', 'checkbox'].includes(data.type);

    return (
        <AdminLayout>
            <Head title={attribute ? 'Edit Attribute' : 'Create Attribute'} />

            <div className="max-w-2xl">
                <h1 className="text-3xl font-semibold text-gray-800 mb-6">
                    {attribute ? 'Edit Attribute' : 'Create New Attribute'}
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
                            <InputLabel htmlFor="code" value="Code (Unique Identifier)" />
                            <TextInput
                                id="code"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value)}
                                placeholder="e.g., brand, condition, weight"
                                required
                            />
                            <InputError message={errors.code} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="type" value="Input Type" />
                            <select
                                id="type"
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                value={data.type}
                                onChange={(e) => setData('type', e.target.value)}
                                required
                            >
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="select">Select Dropdown</option>
                                <option value="radio">Radio Buttons</option>
                                <option value="checkbox">Checkboxes</option>
                                <option value="date">Date</option>
                            </select>
                            <InputError message={errors.type} className="mt-2" />
                        </div>

                        {needsOptions && (
                            <div>
                                <InputLabel value="Options" />
                                <div className="space-y-2 mt-2">
                                    {options.map((option, index) => (
                                        <div key={index} className="flex gap-2">
                                            <TextInput
                                                type="text"
                                                className="flex-1"
                                                value={option}
                                                onChange={(e) => updateOption(index, e.target.value)}
                                                placeholder={`Option ${index + 1}`}
                                            />
                                            <button
                                                type="button"
                                                onClick={() => removeOption(index)}
                                                className="px-3 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    ))}
                                </div>
                                <button
                                    type="button"
                                    onClick={addOption}
                                    className="mt-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200"
                                >
                                    + Add Option
                                </button>
                            </div>
                        )}

                        <div>
                            <InputLabel value="Assign to Categories" />
                            <div className="mt-2 space-y-2">
                                {categories.map((category) => (
                                    <label key={category.id} className="flex items-center">
                                        <input
                                            type="checkbox"
                                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            checked={data.categories.includes(category.id)}
                                            onChange={(e) => {
                                                if (e.target.checked) {
                                                    setData('categories', [...data.categories, category.id]);
                                                } else {
                                                    setData('categories', data.categories.filter(id => id !== category.id));
                                                }
                                            }}
                                        />
                                        <span className="ml-2 text-sm text-gray-700">
                                            {category.name?.en || category.name}
                                        </span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="flex items-center">
                            <input
                                id="is_required"
                                type="checkbox"
                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                checked={data.is_required}
                                onChange={(e) => setData('is_required', e.target.checked)}
                            />
                            <label htmlFor="is_required" className="ml-2 block text-sm text-gray-900">
                                Required Field
                            </label>
                        </div>

                        <div className="flex items-center gap-4">
                            <PrimaryButton disabled={processing}>
                                {attribute ? 'Update' : 'Create'} Attribute
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
