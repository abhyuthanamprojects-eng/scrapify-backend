import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ stats, selectedPeriod, isPickupBoy }) {
    const periodLabel = selectedPeriod === 'today'
        ? 'Today'
        : selectedPeriod === 'overall'
            ? 'Overall'
            : 'This Month';

    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />

            <div className="flex flex-col gap-6">
                {isPickupBoy && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 className="text-sm font-semibold text-gray-700">Pickup Performance</h2>
                            <p className="text-xs text-gray-500">Showing {periodLabel} data</p>
                        </div>
                        <div className="flex items-center gap-2">
                            {[
                                ['today', 'Today'],
                                ['month', 'This Month'],
                                ['overall', 'Overall'],
                            ].map(([value, label]) => (
                                <Link
                                    key={value}
                                    href={route('dashboard', { period: value })}
                                    className={`px-3 py-1.5 text-sm rounded-lg border transition-colors ${
                                        selectedPeriod === value
                                            ? 'bg-primary text-white border-primary'
                                            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                    }`}
                                >
                                    {label}
                                </Link>
                            ))}
                        </div>
                    </div>
                )}
                
                {/* Top Stats Grid */}
                {/* Top Stats Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    {[
                        ['Total Customers', stats.total_customers, 'Partner/customer records'],
                        ['Total Pickups', stats.total_pickups, 'All pickup requests'],
                        ['Assigned Pickups', stats.assigned_pickups, 'Assigned to drivers'],
                        ['Delivered to Warehouse', stats.delivered_to_warehouse, 'Warehouse handovers'],
                        ['Pending Settlement', stats.pending_settlement, 'Awaiting payout'],
                        ['Paid Settlement', stats.paid_settlement, 'Paid payouts'],
                    ].filter((card) => card[1] !== null && card[1] !== undefined).map(([title, value, caption]) => (
                        <div key={title} className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col relative justify-center">
                            <h3 className="text-gray-500 font-medium mb-4 whitespace-nowrap">{title}</h3>
                            <h2 className="text-3xl font-bold text-gray-800 mb-2">{value}</h2>
                            <p className="text-sm text-gray-400">{caption}</p>
                        </div>
                    ))}

                    {/* Total Users */}
                    {stats.users_count !== null && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col relative justify-center">
                            <div className="flex justify-between items-start mb-4">
                                <h3 className="text-gray-500 font-medium whitespace-nowrap">Total Users</h3>
                                <Link href={route('admin.users.index')} className="text-gray-400 hover:text-gray-600">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                                </Link>
                            </div>
                            <div className="flex items-end gap-3 mb-2">
                                <h2 className="text-3xl font-bold text-gray-800">{stats.users_count}</h2>
                            </div>
                            <p className="text-sm text-gray-400">Total registered users</p>
                        </div>
                    )}

                    {/* Pending Pickups */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col relative justify-center">
                        <div className="flex justify-between items-start mb-4">
                            <h3 className="text-gray-500 font-medium whitespace-nowrap">Pending Pickups</h3>
                            <Link href={route('admin.pickups.index', { status: 'pending' })} className="text-gray-400 hover:text-gray-600">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                            </Link>
                        </div>
                        <div className="flex items-end gap-3 mb-2">
                            <h2 className="text-3xl font-bold text-gray-800">{stats.pending_requests}</h2>
                        </div>
                        <p className="text-sm text-gray-400">Awaiting assignment</p>
                    </div>

                    {/* Partner Requests */}
                    {stats.partner_requests_count > 0 && (
                        <div className="bg-white rounded-xl shadow-sm border border-l-4 border-l-orange-500 border-gray-100 p-6 flex flex-col relative justify-center">
                            <div className="flex justify-between items-start mb-4">
                                <h3 className="text-gray-500 font-medium whitespace-nowrap">Partner Requests</h3>
                                <Link href={route('admin.channel-partners.index', { status: 'pending' })} className="text-orange-500 hover:text-orange-600">
                                    <span className="flex h-2 w-2 relative">
                                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                                        <span className="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                                    </span>
                                </Link>
                            </div>
                            <div className="flex items-end gap-3 mb-2">
                                <h2 className="text-3xl font-bold text-gray-800">{stats.partner_requests_count}</h2>
                            </div>
                            <p className="text-sm text-gray-400">New partner registrations</p>
                        </div>
                    )}

                    {/* Approval Pending */}
                    {stats.pending_approvals_count > 0 && (
                        <div className="bg-white rounded-xl shadow-sm border border-l-4 border-l-blue-500 border-gray-100 p-6 flex flex-col relative justify-center">
                            <div className="flex justify-between items-start mb-4">
                                <h3 className="text-gray-500 font-medium whitespace-nowrap">Onboarding Approvals</h3>
                                <Link href={route('admin.approval-requests.index', { status: 'pending' })} className="text-blue-500 hover:text-blue-600">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </Link>
                            </div>
                            <div className="flex items-end gap-3 mb-2">
                                <h2 className="text-3xl font-bold text-gray-800">{stats.pending_approvals_count}</h2>
                            </div>
                            <p className="text-sm text-gray-400">Warehouse/Boy onboarding</p>
                        </div>
                    )}
                </div>

                {/* Secondary Operational Stats */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-6">
                        <div className="p-4 bg-indigo-50 text-indigo-600 rounded-2xl">
                            <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-500">Today's Requests</p>
                            <h2 className="text-2xl font-bold text-gray-800">{stats.today_pickups_count}</h2>
                            <p className="text-xs text-gray-400 mt-1">Bookings since midnight</p>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-6">
                        <div className="p-4 bg-emerald-50 text-emerald-600 rounded-2xl">
                            <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-500">Drivers Online</p>
                            <h2 className="text-2xl font-bold text-gray-800">{stats.online_drivers_count}</h2>
                            <p className="text-xs text-gray-400 mt-1">Ready for pickup</p>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-6">
                        <div className="p-4 bg-orange-50 text-orange-600 rounded-2xl">
                            <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-500">Capacity Full</p>
                            <h2 className="text-2xl font-bold text-gray-800">{stats.capacity_full_drivers_count}</h2>
                            <p className="text-xs text-gray-400 mt-1">Reached daily limit</p>
                        </div>
                    </div>
                </div>

                {/* Table Section */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 mt-2 overflow-hidden">
                    <div className="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="flex flex-wrap gap-2 items-center">
                            <h3 className="font-bold text-gray-800">Recent Booking Requests</h3>
                        </div>
                        <Link href={route('admin.pickups.index')} className="text-sm font-semibold text-primary hover:underline">
                            View All Requests →
                        </Link>
                    </div>
                    
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="bg-[#e8f5e9] text-primary text-sm">
                                    <th className="px-6 py-4 font-semibold whitespace-nowrap">Request Id</th>
                                    <th className="px-6 py-4 font-semibold">User</th>
                                    <th className="px-6 py-4 font-semibold">Scheduled Date</th>
                                    <th className="px-6 py-4 font-semibold">Type</th>
                                    <th className="px-6 py-4 font-semibold">Status</th>
                                    <th className="px-6 py-4 font-semibold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="text-sm">
                                {stats.recent_pickups.length === 0 ? (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-10 text-center text-gray-500">No recent pickup requests found.</td>
                                    </tr>
                                ) : (
                                    stats.recent_pickups.map((pickup) => (
                                        <tr key={pickup.id} className="border-b border-gray-100 hover:bg-gray-50 transition">
                                            <td className="px-6 py-4 font-medium text-gray-800">#{pickup.id}</td>
                                            <td className="px-6 py-4 text-gray-600">{pickup.customer?.name || 'N/A'}</td>
                                            <td className="px-6 py-4 text-gray-600">{pickup.scheduled_at ? new Date(pickup.scheduled_at).toLocaleDateString() : 'TBD'}</td>
                                            <td className="px-6 py-4 text-gray-600 uppercase text-xs font-bold">{pickup.request_type.replace('_', ' ')}</td>
                                            <td className="px-6 py-4">
                                                <span className={`flex items-center font-medium ${
                                                    pickup.status === 'completed' ? 'text-primary' : 
                                                    pickup.status === 'pending' ? 'text-orange-400' : 
                                                    pickup.status === 'cancelled' ? 'text-red-500' : 'text-blue-500'
                                                }`}>
                                                    <span className={`w-2 h-2 rounded-full mr-2 ${
                                                        pickup.status === 'completed' ? 'bg-primary' : 
                                                        pickup.status === 'pending' ? 'bg-orange-400' : 
                                                        pickup.status === 'cancelled' ? 'bg-red-500' : 'bg-blue-500'
                                                    }`}></span>
                                                    {pickup.status.charAt(0).toUpperCase() + pickup.status.slice(1)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link href={route('admin.pickups.show', pickup.id)} className="font-medium text-indigo-400 hover:text-indigo-600">View Details</Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </AdminLayout>
    );
}
