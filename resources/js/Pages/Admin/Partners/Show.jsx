import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({ partner, stats }) {
    return (
        <AdminLayout>
            <Head title={`Partner Details: ${partner.name}`} />

            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="text-3xl font-semibold text-gray-800">Partner Details: {partner.name}</h1>
                    <div className="flex items-center gap-4 mt-2">
                        <span className="text-gray-600">{partner.email}</span>
                        <span className="text-gray-300">|</span>
                        <span className="text-gray-600">{partner.phone}</span>
                        <span className="text-gray-300">|</span>
                        <span className="text-gray-600">{partner.city?.name || 'No City Assigned'}</span>
                    </div>
                </div>
                <Link href={route('admin.partners.index')}>
                    <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">
                        Back to List
                    </button>
                </Link>
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p className="text-sm text-gray-500 uppercase font-bold tracking-wider">Total Pickups</p>
                    <p className="text-2xl font-semibold mt-1">{stats.total_pickups}</p>
                </div>
                <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p className="text-sm text-gray-500 uppercase font-bold tracking-wider">Completed</p>
                    <p className="text-2xl font-semibold mt-1 text-green-600">{stats.completed_pickups}</p>
                </div>
                <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p className="text-sm text-gray-500 uppercase font-bold tracking-wider">Pending Settlement</p>
                    <p className="text-2xl font-semibold mt-1 text-orange-600">₹{stats.pending_settlements}</p>
                </div>
                <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <p className="text-sm text-gray-500 uppercase font-bold tracking-wider">Paid Amount</p>
                    <p className="text-2xl font-semibold mt-1 text-indigo-600">₹{stats.paid_settlements}</p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {/* Pickup Requests Table */}
                <div className="bg-white shadow-md rounded-lg overflow-hidden">
                    <div className="px-6 py-4 bg-gray-50 border-b">
                        <h2 className="text-lg font-semibold text-gray-800">Recent Pickups</h2>
                    </div>
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {partner.pickup_requests.map((pickup) => (
                                <tr key={pickup.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{pickup.id}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full font-semibold ${
                                            pickup.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                        }`}>
                                            {pickup.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {pickup.items.length} items
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link href={route('admin.pickups.show', pickup.id)} className="text-indigo-600 hover:text-indigo-900">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {partner.pickup_requests.length === 0 && (
                        <div className="p-12 text-center text-gray-500">No pickups found.</div>
                    )}
                </div>

                {/* Settlements Table */}
                <div className="bg-white shadow-md rounded-lg overflow-hidden">
                    <div className="px-6 py-4 bg-gray-50 border-b">
                        <h2 className="text-lg font-semibold text-gray-800">Settlements</h2>
                    </div>
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {partner.settlements.map((settlement) => (
                                <tr key={settlement.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{settlement.id}</td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs rounded-full font-semibold ${
                                            settlement.status === 'paid' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'
                                        }`}>
                                            {settlement.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        ₹{settlement.net_amount}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                        {new Date(settlement.created_at).toLocaleDateString()}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {partner.settlements.length === 0 && (
                        <div className="p-12 text-center text-gray-500">No settlements found.</div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
