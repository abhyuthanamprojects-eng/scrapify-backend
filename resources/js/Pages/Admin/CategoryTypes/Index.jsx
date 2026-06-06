import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';

export default function Index({ types }) {
    const { flash } = usePage().props;

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this category type?')) {
            router.delete(route('admin.category-types.destroy', id));
        }
    };

    return (
        <AdminLayout>
            <Head title="Category Types" />

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">Category Types</h1>
                <Link href={route('admin.category-types.create')}>
                    <PrimaryButton>Add Category Type</PrimaryButton>
                </Link>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Image
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Slug
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Corporate
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {types.data.map((type) => (
                            <tr key={type.id}>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    {type.image_url ? (
                                        <img 
                                            src={type.image_url} 
                                            alt={type.name?.en || type.name} 
                                            className="h-10 w-10 object-cover rounded shadow-sm border border-gray-100" 
                                            onError={(e) => {
                                                e.target.onerror = null;
                                                e.target.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(type.name?.en || type.name || 'T') + '&color=7F9CF5&background=EBF4FF';
                                            }}
                                        />
                                    ) : (
                                        <div className="h-10 w-10 bg-gray-100 rounded flex items-center justify-center">
                                            <svg className="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">{type.name?.en || type.name}</div>
                                    {type.name?.hi && (
                                        <div className="text-xs text-gray-500">{type.name.hi}</div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">{type.slug}</span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                        type.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`}>
                                        {type.status ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                        type.show_in_corporate_booking ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'
                                    }`}>
                                        {type.show_in_corporate_booking ? 'Enabled' : 'Hidden'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        href={route('admin.category-types.edit', type.id)}
                                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        onClick={() => handleDelete(type.id)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {types.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No category types found. Be sure to migrate and seed.</p>
                    </div>
                )}
            </div>

            <div className="mt-6 flex justify-center">
                <div className="flex gap-1">
                    {types.links.map((link, i) => (
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1 border rounded ${
                                    link.active ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'
                                }`}
                            />
                        ) : (
                            <span
                                key={i}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className="px-3 py-1 border rounded text-gray-300 cursor-not-allowed bg-white"
                            />
                        )
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
