import { Head, useForm, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Form({ page = null }) {
    const isEditing = !!page;

    const { data, setData, post, put, processing, errors } = useForm({
        title: page?.title || '',
        slug: page?.slug || '',
        content: page?.content || '',
        is_active: page?.is_active ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEditing) {
            put(route('admin.pages.update', page.id));
        } else {
            post(route('admin.pages.store'));
        }
    };

    return (
        <AdminLayout>
            <Head title={isEditing ? 'Edit Page' : 'Create Page'} />

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">
                    {isEditing ? `Edit: ${page.title}` : 'Create New Page'}
                </h1>
                <Link href={route('admin.pages.index')}>
                    <SecondaryButton>Back to List</SecondaryButton>
                </Link>
            </div>

            <div className="max-w-4xl bg-white shadow-md rounded-lg overflow-hidden p-8">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <InputLabel htmlFor="title" value="Page Title" />
                        <TextInput
                            id="title"
                            type="text"
                            name="title"
                            value={data.title}
                            className="mt-1 block w-full"
                            onChange={(e) => {
                                setData('title', e.target.value);
                                if (!isEditing) {
                                    setData('slug', e.target.value.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, ''));
                                }
                            }}
                            required
                        />
                        <InputError message={errors.title} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="slug" value="Slug (URL Path)" />
                        <div className="flex items-center mt-1">
                            <span className="text-gray-500 mr-2">scrapify.in/</span>
                            <TextInput
                                id="slug"
                                type="text"
                                name="slug"
                                value={data.slug}
                                className="block w-full"
                                onChange={(e) => setData('slug', e.target.value)}
                                required
                            />
                        </div>
                        <InputError message={errors.slug} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="content" value="Page Content (HTML/Markdown)" />
                        <textarea
                            id="content"
                            name="content"
                            value={data.content}
                            className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="15"
                            onChange={(e) => setData('content', e.target.value)}
                            required
                        />
                        <InputError message={errors.content} className="mt-2" />
                    </div>

                    <div className="flex items-center">
                        <input
                            id="is_active"
                            type="checkbox"
                            className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                        />
                        <label htmlFor="is_active" className="ml-2 text-sm text-gray-600">
                            Published (Visible on site)
                        </label>
                        <InputError message={errors.is_active} className="mt-2" />
                    </div>

                    <div className="pt-4">
                        <PrimaryButton disabled={processing}>
                            {isEditing ? 'Update Page' : 'Create Page'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
