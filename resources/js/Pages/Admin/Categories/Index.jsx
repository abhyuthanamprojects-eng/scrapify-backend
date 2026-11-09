import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AdminHeader from '@/Components/Admin/AdminHeader';
import AdminFilters, { AdminFilterInput, AdminFilterSelect } from '@/Components/Admin/AdminFilters';

export default function Index({ categories, filters, types }) {
    const handleSearch = (e) => {
        router.get(route('admin.categories.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (name, value) => {
        router.get(route('admin.categories.index'), {
            ...filters,
            [name]: value,
        }, {
            preserveState: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this category?')) {
            router.delete(route('admin.categories.destroy', id));
        }
    };

    return (
        <AdminLayout>
            <Head title="Categories" />

            <AdminHeader
                title="Categories"
                subtitle="Manage the reusable categories used across bookings and inventory"
                icon={<img src="/images/admin/3d/customers.png" alt="" className="h-10 w-10 object-contain" />}
                action={{ label: 'Add New Category', href: route('admin.categories.create'), icon: '+' }}
            />

            <AdminFilters>
                <AdminFilterInput
                    label="Search"
                    placeholder="Search by name or slug..."
                    value={filters.search || ''}
                    onChange={handleSearch}
                    colSpan="md:col-span-5"
                />
                <AdminFilterSelect
                    label="Category type"
                    value={filters.category_type_id || ''}
                    onChange={(e) => handleFilterChange('category_type_id', e.target.value)}
                    options={[
                        { value: '', label: 'All Types' },
                        ...(types?.map(type => ({ value: type.id, label: type.name?.en || type.name })) || []),
                    ]}
                    colSpan="md:col-span-3"
                />
                <AdminFilterSelect
                    label="Status"
                    value={filters.status ?? ''}
                    onChange={(e) => handleFilterChange('status', e.target.value)}
                    options={[{ value: '', label: 'All Status' }, { value: '1', label: 'Active' }, { value: '0', label: 'Inactive' }]}
                    colSpan="md:col-span-2"
                />
            </AdminFilters>

            <div className="admin-table-shell overflow-hidden">
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
                                Type
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pricing
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {categories?.data?.map((category) => (
                            <tr key={category.id}>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    {category.image_url ? (
                                        <img 
                                            src={category.image_url} 
                                            alt={category.name?.en || 'Category'} 
                                            className="w-12 h-12 object-cover rounded-md"
                                            onError={(e) => {
                                                e.target.onerror = null;
                                                e.target.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(category.name?.en || 'C') + '&color=7F9CF5&background=EBF4FF';
                                            }}
                                        />
                                    ) : (
                                        <div className="w-12 h-12 bg-gray-100 flex items-center justify-center rounded-md text-gray-400">
                                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">
                                        {category.name?.en || category.name}
                                    </div>
                                    {category.name?.hi && (
                                        <div className="text-sm text-gray-500">{category.name.hi}</div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {category.category_type?.name?.en || category.category_type?.name || 'Unknown'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {category.pricing_rules && category.pricing_rules[0] && category.pricing_rules[0].pricing_type && (
                                        <div>
                                            ₹{category.pricing_rules[0].base_price} / {category.pricing_rules[0].pricing_type.replace('_', ' ')}
                                        </div>
                                    )}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                        category.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`}>
                                        {category.status ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        href={route('admin.categories.edit', category.id)}
                                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        onClick={() => handleDelete(category.id)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {categories?.data?.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No categories found. Create your first category to get started.</p>
                    </div>
                )}
            </div>

            <div className="mt-6 flex justify-center">
                <div className="flex gap-1">
                    {categories?.links?.map((link, i) => (
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
