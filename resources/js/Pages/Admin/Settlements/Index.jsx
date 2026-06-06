import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ settlements, filters }) {
    const getStatusBadge = (status) => {
        const badges = {
            pending: 'bg-yellow-100 text-yellow-800',
            approved: 'bg-blue-100 text-blue-800',
            paid: 'bg-green-100 text-green-800',
            rejected: 'bg-red-100 text-red-800',
        };
        
        return badges[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AdminLayout>
            <Head title="Settlements" />

            <div className="mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">Partner Settlements</h1>
                <p className="text-gray-600 mt-2">Manage and approve partner payment settlements</p>
            </div>

            {/* Filters */}
            <div className="bg-white shadow-md rounded-lg p-4 mb-6">
                <div className="flex gap-4">
                    <Link
                        href={route('admin.settlements.index')}
                        className={`px-4 py-2 rounded-md ${!filters.status ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        All
                    </Link>
                    <Link
                        href={route('admin.settlements.index', { status: 'pending' })}
                        className={`px-4 py-2 rounded-md ${filters.status === 'pending' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Pending
                    </Link>
                    <Link
                        href={route('admin.settlements.index', { status: 'approved' })}
                        className={`px-4 py-2 rounded-md ${filters.status === 'approved' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Approved
                    </Link>
                    <Link
                        href={route('admin.settlements.index', { status: 'paid' })}
                        className={`px-4 py-2 rounded-md ${filters.status === 'paid' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Paid
                    </Link>
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Settlement ID
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Partner
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {settlements.data.map((settlement) => (
                            <tr key={settlement.id}>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #{settlement.id}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">
                                        {settlement.partner?.name}
                                    </div>
                                    <div className="text-sm text-gray-500">
                                        {settlement.partner?.mobile}
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ₹{settlement.total_amount?.toLocaleString()}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadge(settlement.status)}`}>
                                        {settlement.status}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {new Date(settlement.created_at).toLocaleDateString()}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        href={route('admin.settlements.show', settlement.id)}
                                        className="text-indigo-600 hover:text-indigo-900"
                                    >
                                        View Details
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {settlements.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No settlements found.</p>
                    </div>
                )}
            </div>

            {/* Pagination */}
            {settlements.links && settlements.links.length > 3 && (
                <div className="mt-6 flex justify-center gap-1">
                    {settlements.links.map((link, index) => (
                        link.url ? (
                            <Link
                                key={index}
                                href={link.url}
                                className={`px-4 py-2 border rounded ${
                                    link.active
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-white text-gray-700 hover:bg-gray-50'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span
                                key={index}
                                className="px-4 py-2 border rounded text-gray-300 cursor-not-allowed bg-white"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        )
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
