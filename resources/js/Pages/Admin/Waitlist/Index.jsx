import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import TextInput from '@/Components/TextInput';

export default function Index({ entries, filters }) {
    const handleSearch = (e) => {
        router.get(route('admin.waitlist.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (name, value) => {
        router.get(route('admin.waitlist.index'), {
            ...filters,
            [name]: value,
        }, {
            preserveState: true,
        });
    };

    const handleStatusUpdate = (id, status) => {
        router.put(route('admin.waitlist.update', id), { status }, {
            preserveScroll: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this waitlist entry?')) {
            router.delete(route('admin.waitlist.destroy', id));
        }
    };

    const getStatusBadgeClass = (status) => {
        switch (status) {
            case 'new': return 'bg-blue-100 text-blue-800';
            case 'contacted': return 'bg-yellow-100 text-yellow-800';
            case 'planned': return 'bg-purple-100 text-purple-800';
            case 'launched': return 'bg-green-100 text-green-800';
            case 'closed': return 'bg-gray-100 text-gray-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AdminLayout>
            <Head title="Waitlist" />

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">Waitlist Management</h1>
                <a 
                    href={route('admin.waitlist.export')} 
                    className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150"
                >
                    Export CSV
                </a>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden p-6 mb-6">
                <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div className="w-full md:w-1/3">
                        <TextInput
                            placeholder="Search by name, city or phone..."
                            className="w-full"
                            value={filters.search || ''}
                            onChange={handleSearch}
                        />
                    </div>
                    <div className="w-full md:w-auto flex gap-4">
                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            value={filters.status || ''}
                            onChange={(e) => handleFilterChange('status', e.target.value)}
                        >
                            <option value="">All Status</option>
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="planned">Planned</option>
                            <option value="launched">Launched</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {entries.data.map((entry) => (
                            <tr key={entry.id}>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">{entry.name}</div>
                                    <div className="text-xs text-gray-500">{entry.email}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {entry.phone}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {entry.location_name || `${entry.city}${entry.state ? ', ' + entry.state : ''}`}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <select
                                        className={`text-xs font-semibold rounded-full px-2 py-1 border-none focus:ring-0 cursor-pointer ${getStatusBadgeClass(entry.status)}`}
                                        value={entry.status}
                                        onChange={(e) => handleStatusUpdate(entry.id, e.target.value)}
                                    >
                                        <option value="new">New</option>
                                        <option value="contacted">Contacted</option>
                                        <option value="planned">Planned</option>
                                        <option value="launched">Launched</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {new Date(entry.created_at).toLocaleDateString()}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                    <button
                                        onClick={() => handleDelete(entry.id)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {entries.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No waitlist entries found.</p>
                    </div>
                )}
            </div>

            <div className="mt-6 flex justify-center">
                <div className="flex gap-1">
                    {entries.links.map((link, i) => (
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
