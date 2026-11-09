import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AdminHeader from '@/Components/Admin/AdminHeader';
import AdminFilters, { AdminFilterInput, AdminFilterSelect } from '@/Components/Admin/AdminFilters';

export default function Index({ attributes, filters, types }) {
    const handleSearch = (e) => {
        router.get(route('admin.attributes.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (name, value) => {
        router.get(route('admin.attributes.index'), {
            ...filters,
            [name]: value,
        }, {
            preserveState: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this attribute?')) {
            router.delete(route('admin.attributes.destroy', id));
        }
    };

    return (
        <AdminLayout>
            <Head title="Attributes" />

            <AdminHeader
                title="Dynamic Attributes"
                subtitle="Define the fields and options used by category records"
                icon={<img src="/images/admin/3d/customers.png" alt="" className="h-10 w-10 object-contain" />}
                action={{ label: 'Add New Attribute', href: route('admin.attributes.create'), icon: '+' }}
            />

            <AdminFilters>
                <AdminFilterInput
                    label="Search"
                    placeholder="Search by name or code..."
                    value={filters.search || ''}
                    onChange={handleSearch}
                    colSpan="md:col-span-6"
                />
                <AdminFilterSelect
                    label="Type"
                    value={filters.type || ''}
                    onChange={(e) => handleFilterChange('type', e.target.value)}
                    options={[
                        { value: '', label: 'All Types' },
                        ...types.map(type => ({ value: type, label: type.charAt(0).toUpperCase() + type.slice(1) })),
                    ]}
                    colSpan="md:col-span-3"
                />
            </AdminFilters>

            <div className="admin-table-shell overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Code
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Categories
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Required
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {attributes.data.map((attribute) => (
                            <tr key={attribute.id}>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">
                                        {attribute.name?.en || attribute.name}
                                    </div>
                                    {attribute.name?.hi && (
                                        <div className="text-sm text-gray-500">{attribute.name.hi}</div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {attribute.code}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        {attribute.type}
                                    </span>
                                </td>
                                <td className="px-6 py-4 text-sm text-gray-500">
                                    {attribute.categories && attribute.categories.length > 0 ? (
                                        <div className="flex flex-wrap gap-1">
                                            {attribute.categories.map((cat) => (
                                                <span key={cat.id} className="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                                    {cat.name?.en || cat.name}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <span className="text-gray-400">No categories</span>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                        attribute.is_required ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'
                                    }`}>
                                        {attribute.is_required ? 'Required' : 'Optional'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        href={route('admin.attributes.edit', attribute.id)}
                                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        onClick={() => handleDelete(attribute.id)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {attributes.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No attributes found.</p>
                    </div>
                )}
            </div>

            <div className="mt-6 flex justify-center">
                <div className="flex gap-1">
                    {attributes.links.map((link, i) => (
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1 border rounded ${
                                    link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'
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
