import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import TextInput from '@/Components/TextInput';

export default function Index({ messages, filters }) {
    const handleSearch = (e) => {
        router.get(route('admin.contacts.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleStatusFilter = (e) => {
        router.get(route('admin.contacts.index'), {
            ...filters,
            status: e.target.value,
        }, {
            preserveState: true,
        });
    };

    const handleTypeFilter = (e) => {
        router.get(route('admin.contacts.index'), {
            ...filters,
            type: e.target.value,
        }, { preserveState: true });
    };

    const handleRoleFilter = (e) => {
        router.get(route('admin.contacts.index'), {
            ...filters,
            user_role: e.target.value,
        }, { preserveState: true });
    };

    return (
        <AdminLayout>
            <Head title="Contact Messages" />

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">Contact Messages</h1>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden p-6 mb-6">
                <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div className="w-full md:w-1/3">
                        <TextInput
                            placeholder="Search by name, email or subject..."
                            className="w-full"
                            value={filters.search || ''}
                            onChange={handleSearch}
                        />
                    </div>
                    <div className="flex flex-wrap gap-2 md:w-auto">
                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            value={filters.status || ''}
                            onChange={handleStatusFilter}
                        >
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                        </select>
                        <select
                            className="border-gray-300 rounded-md shadow-sm"
                            value={filters.type || ''}
                            onChange={handleTypeFilter}
                        >
                            <option value="">All Types</option>
                            <option value="general">General</option>
                            <option value="order">Order</option>
                        </select>
                        <select
                            className="border-gray-300 rounded-md shadow-sm"
                            value={filters.user_role || ''}
                            onChange={handleRoleFilter}
                        >
                            <option value="">All Roles</option>
                            <option value="customer">Customer</option>
                            <option value="channel_partner">Channel Partner</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="pickup_boy">Pickup Boy</option>
                        </select>
                    </div>
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact Information
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Subject / Type
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Role
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Received At
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {messages.data.map((msg) => (
                            <tr key={msg.id}>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">{msg.name}</div>
                                    <div className="text-sm text-gray-500">{msg.email}</div>
                                    <div className="text-xs text-gray-400">{msg.phone}</div>
                                </td>
                                <td className="px-6 py-4">
                                    <div className="text-sm text-gray-900 truncate max-w-xs">{msg.subject || 'No Subject'}</div>
                                    <span className={`mt-1 inline-block px-2 text-xs rounded ${
                                        msg.type === 'order' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700'
                                    }`}>
                                        {msg.type || 'general'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                    {msg.pickup_request_id ? (
                                        <span className="font-mono">
                                            #{msg.pickup_request_id}
                                            {msg.pickup_request?.pickup_code && (
                                                <div className="text-xs text-gray-400">{msg.pickup_request.pickup_code}</div>
                                            )}
                                        </span>
                                    ) : <span className="text-gray-400">—</span>}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-xs">
                                    {msg.user_role ? (
                                        <span className="px-2 py-1 bg-purple-50 text-purple-700 rounded">{msg.user_role}</span>
                                    ) : <span className="text-gray-400">—</span>}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                        msg.status === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                    }`}>
                                        {msg.status.toUpperCase()}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {new Date(msg.created_at).toLocaleString()}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        href={route('admin.contacts.show', msg.id)}
                                        className="text-indigo-600 hover:text-indigo-900"
                                    >
                                        View Details
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {messages.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No messages found.</p>
                    </div>
                )}
            </div>

            {/* Pagination */}
            <div className="mt-6 flex justify-center">
                <div className="flex gap-1">
                    {messages.links.map((link, i) => (
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
