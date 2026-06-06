import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import TextInput from '@/Components/TextInput';

export default function Index({ partners, filters, states }) {
    const handleSearch = (e) => {
        router.get(route('admin.partners.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (name, value) => {
        const newFilters = { ...filters, [name]: value };
        if (name === 'state_id') newFilters.city_id = '';
        
        router.get(route('admin.partners.index'), newFilters, {
            preserveState: true,
        });
    };

    const availableCities = filters.state_id 
        ? (states.find(s => s.id == filters.state_id)?.cities || [])
        : [];

    return (
        <AdminLayout>
            <Head title="Channel Partners" />

            <div className="mb-6 flex justify-between items-end">
                <div>
                    <h1 className="text-3xl font-semibold text-gray-800">Channel Partners</h1>
                    <p className="text-gray-600 mt-2">Manage and track channel partner performance</p>
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden p-6 mb-6">
                <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div className="w-full md:w-1/3">
                        <TextInput
                            placeholder="Search by name, email or phone..."
                            className="w-full"
                            value={filters.search || ''}
                            onChange={handleSearch}
                        />
                    </div>
                    <div className="w-full md:w-auto flex gap-4">
                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            value={filters.state_id || ''}
                            onChange={(e) => handleFilterChange('state_id', e.target.value)}
                        >
                            <option value="">All States</option>
                            {states.map(state => (
                                <option key={state.id} value={state.id}>{state.name}</option>
                            ))}
                        </select>
                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:opacity-50 disabled:bg-gray-100"
                            value={filters.city_id || ''}
                            onChange={(e) => handleFilterChange('city_id', e.target.value)}
                            disabled={!filters.state_id}
                        >
                            <option value="">All Cities</option>
                            {availableCities.map(city => (
                                <option key={city.id} value={city.id}>{city.name}</option>
                            ))}
                        </select>
                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            value={filters.status ?? ''}
                            onChange={(e) => handleFilterChange('status', e.target.value)}
                        >
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Partner
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Location
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Pickups
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
                        {partners.data.map((partner) => (
                            <tr key={partner.id}>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="flex items-center">
                                        <div>
                                            <div className="text-sm font-medium text-gray-900">
                                                {partner.name}
                                            </div>
                                            <div className="text-sm text-gray-500">
                                                {partner.email}
                                            </div>
                                            <div className="text-sm text-gray-500">
                                                {partner.phone}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {partner.city?.name || 'N/A'}, {partner.city?.state?.name || ''}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">
                                        {partner.pickup_requests_count || 0} pickups
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                        partner.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                    }`}>
                                        {partner.status ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        href={route('admin.partners.show', partner.id)}
                                        className="text-indigo-600 hover:text-indigo-900"
                                    >
                                        View Details
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {partners.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No channel partners found.</p>
                    </div>
                )}
            </div>

            <div className="mt-6 flex justify-center">
                <div className="flex gap-1">
                    {partners.links.map((link, i) => (
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
