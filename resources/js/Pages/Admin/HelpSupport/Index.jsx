import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';

export default function Index({ tickets, filters }) {
    const handleFilter = (key, value) => {
        router.get(route('admin.help-support.index'), { ...filters, [key]: value }, { preserveState: true });
    };

    const statusColors = {
        pending: 'bg-orange-100 text-orange-700',
        in_progress: 'bg-blue-100 text-blue-700',
        resolved: 'bg-green-100 text-green-700',
        closed: 'bg-gray-100 text-gray-700',
    };

    return (
        <AdminLayout>
            <Head title="Help & Support" />

            <div className="flex flex-col gap-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Help & Support Tickets</h1>
                        <p className="text-gray-500 text-sm">Manage customer and partner support queries submitted via mobile app.</p>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="flex flex-wrap gap-2 items-center">
                            {['all', 'pending', 'in_progress', 'resolved', 'closed'].map((s) => (
                                <button
                                    key={s}
                                    onClick={() => handleFilter('status', s === 'all' ? '' : s)}
                                    className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors capitalize ${
                                        (filters.status === s || (!filters.status && s === 'all')) 
                                        ? 'bg-primary text-white' 
                                        : 'text-gray-500 hover:bg-gray-50'
                                    }`}
                                >
                                    {s.replace('_', ' ')}
                                </button>
                            ))}
                        </div>

                        <div className="flex gap-2">
                            <input
                                type="text"
                                placeholder="Search tickets..."
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
                                    <th className="px-6 py-4 font-semibold">User</th>
                                    <th className="px-6 py-4 font-semibold">Subject</th>
                                    <th className="px-6 py-4 font-semibold">Type</th>
                                    <th className="px-6 py-4 font-semibold">Status</th>
                                    <th className="px-6 py-4 font-semibold">Date</th>
                                    <th className="px-6 py-4 font-semibold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="text-sm">
                                {tickets.data.length === 0 ? (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-10 text-center text-gray-500 italic">No support tickets found.</td>
                                    </tr>
                                ) : (
                                    tickets.data.map((t) => (
                                        <tr key={t.id} className="border-b border-gray-100 hover:bg-gray-50 transition">
                                            <td className="px-6 py-4">
                                                <div className="font-semibold text-gray-800">{t.name || t.user?.name || 'Guest'}</div>
                                                <div className="text-xs text-gray-400 capitalize">{t.user_role || 'Visitor'}</div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-gray-800 font-medium truncate max-w-[200px]">{t.subject}</div>
                                                {t.pickup_request_id && (
                                                    <div className="text-[10px] text-blue-500 font-bold uppercase mt-0.5">Order #{t.pickup_request_id}</div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-gray-600 capitalize">{t.type}</td>
                                            <td className="px-6 py-4">
                                                <span className={`px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${statusColors[t.status]}`}>
                                                    {t.status.replace('_', ' ')}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500">
                                                {new Date(t.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link
                                                    href={route('admin.help-support.show', t.id)}
                                                    className="inline-flex items-center text-primary font-semibold hover:underline"
                                                >
                                                    View
                                                    <svg className="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {tickets.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-100">
                            <Pagination links={tickets.links} />
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
