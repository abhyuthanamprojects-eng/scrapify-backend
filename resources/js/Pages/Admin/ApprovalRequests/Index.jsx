import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';

export default function Index({ requests, filters }) {
    const handleFilter = (key, value) => {
        router.get(route('admin.approval-requests.index'), { ...filters, [key]: value }, { preserveState: true });
    };

    const statusColors = {
        pending: 'bg-orange-100 text-orange-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
    };

    return (
        <AdminLayout>
            <Head title="Approval Requests" />

            <div className="flex flex-col gap-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Approval Requests</h1>
                        <p className="text-gray-500 text-sm">Review onboarding requests for pickup boys, warehouses, and partners.</p>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="flex flex-wrap gap-2 items-center">
                            <button
                                onClick={() => handleFilter('status', '')}
                                className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${!filters.status ? 'bg-primary text-white' : 'text-gray-500 hover:bg-gray-50'}`}
                            >
                                All
                            </button>
                            <button
                                onClick={() => handleFilter('status', 'pending')}
                                className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${filters.status === 'pending' ? 'bg-orange-500 text-white' : 'text-gray-500 hover:bg-gray-50'}`}
                            >
                                Pending
                            </button>
                            <button
                                onClick={() => handleFilter('status', 'approved')}
                                className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${filters.status === 'approved' ? 'bg-green-500 text-white' : 'text-gray-500 hover:bg-gray-50'}`}
                            >
                                Approved
                            </button>
                            <button
                                onClick={() => handleFilter('status', 'rejected')}
                                className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${filters.status === 'rejected' ? 'bg-red-500 text-white' : 'text-gray-500 hover:bg-gray-50'}`}
                            >
                                Rejected
                            </button>
                        </div>

                        <div className="flex gap-2">
                            <select
                                value={filters.entity_type || ''}
                                onChange={e => handleFilter('entity_type', e.target.value)}
                                className="text-sm bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                                <option value="">All Types</option>
                                <option value="pickup_boy">Pickup Boy</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="channel_partner_registration">Partner Registration</option>
                            </select>
                            <input
                                type="text"
                                placeholder="Search..."
                                value={filters.search || ''}
                                onChange={e => handleFilter('search', e.target.value)}
                                className="text-sm bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-1 focus:ring-primary w-64"
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                                    <th className="px-6 py-4 font-semibold">Entity Type</th>
                                    <th className="px-6 py-4 font-semibold">Submitted By</th>
                                    <th className="px-6 py-4 font-semibold">Partner</th>
                                    <th className="px-6 py-4 font-semibold">Status</th>
                                    <th className="px-6 py-4 font-semibold">Date</th>
                                    <th className="px-6 py-4 font-semibold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="text-sm">
                                {requests.data.length === 0 ? (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-10 text-center text-gray-500 italic">No approval requests found.</td>
                                    </tr>
                                ) : (
                                    requests.data.map((req) => (
                                        <tr key={req.id} className="border-b border-gray-100 hover:bg-gray-50 transition">
                                            <td className="px-6 py-4">
                                                <span className="font-semibold text-gray-800 capitalize">
                                                    {req.entity_type.replace(/_/g, ' ')}
                                                </span>
                                                <div className="text-xs text-gray-400 capitalize">{req.request_type}</div>
                                            </td>
                                            <td className="px-6 py-4 text-gray-600">{req.creator?.name || 'System'}</td>
                                            <td className="px-6 py-4 text-gray-600">
                                                {req.channel_partner?.business_name || req.channel_partner?.full_name || 'N/A'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[req.status]}`}>
                                                    {req.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500">
                                                {new Date(req.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link
                                                    href={route('admin.approval-requests.show', req.id)}
                                                    className="inline-flex items-center text-primary font-semibold hover:underline"
                                                >
                                                    Review
                                                    <svg className="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {requests.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-100">
                            <Pagination links={requests.links} />
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
