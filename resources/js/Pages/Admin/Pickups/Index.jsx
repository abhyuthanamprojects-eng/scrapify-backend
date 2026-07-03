import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';

const statusColors = {
    pending: 'bg-gray-100 text-gray-800',
    assigned: 'bg-blue-100 text-blue-800',
    accepted: 'bg-indigo-100 text-indigo-800',
    on_the_way: 'bg-yellow-100 text-yellow-800',
    arrived: 'bg-yellow-100 text-yellow-800',
    verifying: 'bg-purple-100 text-purple-800',
    picked_up: 'bg-teal-100 text-teal-800',
    completed: 'bg-green-100 text-green-800',
    reschedule_requested: 'bg-red-100 text-red-800 font-bold',
    rescheduled: 'bg-orange-100 text-orange-800',
    cancelled: 'bg-red-100 text-red-800',
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

export default function Index({ pickups, filters }) {
    const handleFilter = (key, value) => {
        router.get(route('admin.pickups.index'), {
            ...filters,
            [key]: value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AdminLayout>
             <Head title="Manage Pickups" />
             
             <div className="flex justify-between items-center mb-6">
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">Pickup Requests</h2>
                <button 
                    onClick={() => {
                        if (confirm('Auto-allocate all pending pickups to available warehouse drivers?')) {
                            router.post(route('admin.pickups.auto-assign'));
                        }
                    }}
                    className="px-4 py-2 bg-indigo-600 text-white font-bold text-sm rounded-lg hover:bg-indigo-700 transition-all shadow-md flex items-center gap-2"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Auto Allocate Drivers
                </button>
             </div>

             <div className="bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden mb-6">
                <div className="p-6">
                    <div className="flex flex-col md:flex-row gap-4 items-center justify-between mb-6">
                        <div className="flex flex-wrap gap-2">
                             {['all', 'scrap', 'donation', 'corporate'].map((t) => (
                                <button
                                    key={t}
                                    onClick={() => handleFilter('request_type', t === 'all' ? '' : t)}
                                    className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors capitalize ${
                                        (filters.request_type === t || (!filters.request_type && t === 'all')) 
                                        ? 'bg-primary text-white' 
                                        : 'text-gray-500 hover:bg-gray-50'
                                    }`}
                                >
                                    {t}
                                </button>
                            ))}
                        </div>

                        <div className="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                            <TextInput
                                placeholder="Search ID, name..."
                                className="w-full md:w-64"
                                value={filters.search || ''}
                                onChange={e => handleFilter('search', e.target.value)}
                            />
                            <select
                                className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-48"
                                value={filters.status || ''}
                                onChange={e => handleFilter('status', e.target.value)}
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="assigned">Assigned</option>
                                <option value="accepted">Accepted</option>
                                <option value="on_the_way">On the Way</option>
                                <option value="arrived">Arrived</option>
                                <option value="verifying">Verifying</option>
                                <option value="picked_up">Picked Up</option>
                                <option value="completed">Completed</option>
                                <option value="reschedule_requested">Reschedule Requested</option>
                                <option value="rescheduled">Rescheduled</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr className="bg-gray-50">
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Scheduled At</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Warehouse</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Booking Amount</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Final Amount</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {pickups.data.map((pickup) => (
                                    <tr 
                                        key={pickup.id} 
                                        onClick={() => router.visit(route('admin.pickups.show', pickup.id))}
                                        className="hover:bg-gray-50 transition cursor-pointer"
                                    >
                                        <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">#{pickup.id}</td>
                                        <td className="px-4 py-4">
                                            <div className="text-sm font-semibold text-gray-800 break-words max-w-[150px]">{pickup.customer?.name}</div>
                                            <div className="text-xs text-gray-500">{pickup.customer?.phone}</div>
                                        </td>
                                        <td className="px-4 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                                                pickup.request_type === 'donation' ? 'bg-green-100 text-green-700' : 
                                                pickup.request_type === 'corporate' ? 'bg-blue-100 text-blue-700' : 
                                                'bg-gray-100 text-gray-700'
                                            }`}>
                                                {pickup.request_type}
                                            </span>
                                        </td>
                                        <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{formatDate(pickup.scheduled_at)}</td>
                                        <td className="px-4 py-4 whitespace-nowrap">
                                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColors[pickup.status] || 'bg-gray-100 text-gray-800'}`}>
                                                {pickup.status.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="px-4 py-4 text-sm text-gray-500 max-w-[200px] break-words">{pickup.warehouse?.name || '-'}</td>
                                        <td className="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                                            {pickup.estimated_amount !== null && pickup.estimated_amount !== undefined ? `₹${pickup.estimated_amount}` : '-'}
                                        </td>
                                        <td className="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-800">
                                            {pickup.final_amount !== null && pickup.final_amount !== undefined && !['pending', 'assigned', 'accepted', 'on_the_way', 'arrived', 'verifying', 'reschedule_requested', 'rescheduled', 'cancelled'].includes(pickup.status) ? `₹${pickup.final_amount}` : '-'}
                                        </td>
                                        <td className="px-4 py-4 whitespace-nowrap text-right text-sm font-medium" onClick={(e) => e.stopPropagation()}>
                                            <Link href={route('admin.pickups.show', pickup.id)} className="text-primary font-bold hover:underline">
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    
                    <div className="px-6 py-5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                         <span className="text-xs text-gray-500">
                             Showing {pickups.from} to {pickups.to} of {pickups.total} results
                         </span>
                         <div className="flex gap-2">
                             {pickups.links.map((link, i) => (
                                 <Link
                                    key={i}
                                    href={link.url || '#'}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                    className={`px-3 py-1 rounded-md text-xs border ${
                                        link.active ? 'bg-primary text-white border-primary' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'
                                    } ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                 />
                             ))}
                         </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
