import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ ticket }) {
    const { data, setData, post, processing } = useForm({
        status: ticket.status,
    });

    const updateStatus = (newStatus) => {
        setData('status', newStatus);
        post(route('admin.help-support.update-status', ticket.id), {
            preserveScroll: true,
        });
    };

    const statusColors = {
        pending: 'bg-orange-100 text-orange-700 border-orange-200',
        in_progress: 'bg-blue-100 text-blue-700 border-blue-200',
        resolved: 'bg-green-100 text-green-700 border-green-200',
        closed: 'bg-gray-100 text-gray-700 border-gray-200',
    };

    return (
        <AdminLayout>
            <Head title="Ticket Detail" />

            <div className="max-w-4xl mx-auto flex flex-col gap-6">
                <div className="flex justify-between items-center">
                    <Link href={route('admin.help-support.index')} className="text-gray-500 hover:text-gray-700 flex items-center text-sm">
                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7"></path></svg>
                        Back to tickets
                    </Link>
                    <div className="flex gap-2">
                        {['pending', 'in_progress', 'resolved', 'closed'].map((s) => (
                            <button
                                key={s}
                                onClick={() => updateStatus(s)}
                                disabled={processing || data.status === s}
                                className={`px-3 py-1.5 rounded-lg text-xs font-bold uppercase transition-all border ${
                                    data.status === s 
                                    ? statusColors[s] 
                                    : 'bg-white text-gray-400 border-gray-200 hover:border-gray-400'
                                }`}
                            >
                                {s.replace('_', ' ')}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        {/* Message Card */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div className="p-6 border-b border-gray-100 flex justify-between items-center">
                                <h2 className="text-lg font-bold text-gray-800">{ticket.subject || 'No Subject'}</h2>
                                <span className="text-xs text-gray-400">{new Date(ticket.created_at).toLocaleString()}</span>
                            </div>
                            <div className="p-6">
                                <div className="p-4 bg-gray-50 rounded-xl border border-gray-100 text-gray-700 whitespace-pre-wrap leading-relaxed">
                                    {ticket.message}
                                </div>
                            </div>
                        </div>

                        {/* Order Context if available */}
                        {ticket.pickup_request && (
                            <div className="bg-white rounded-xl shadow-sm border border-blue-100 overflow-hidden">
                                <div className="p-4 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
                                    <h3 className="text-sm font-bold text-blue-800">Related Order Context</h3>
                                    <Link href={route('admin.pickups.show', ticket.pickup_request_id)} className="text-xs font-bold text-blue-600 hover:underline">View Order details →</Link>
                                </div>
                                <div className="p-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="text-[10px] text-gray-400 uppercase font-bold block">Order ID</label>
                                        <p className="text-sm font-semibold text-gray-800">#{ticket.pickup_request_id}</p>
                                    </div>
                                    <div>
                                        <label className="text-[10px] text-gray-400 uppercase font-bold block">Type</label>
                                        <p className="text-sm font-semibold text-gray-800 capitalize">{ticket.pickup_request.request_type.replace('_', ' ')}</p>
                                    </div>
                                    <div>
                                        <label className="text-[10px] text-gray-400 uppercase font-bold block">Order Status</label>
                                        <p className="text-sm font-semibold text-gray-800 capitalize">{ticket.pickup_request.status}</p>
                                    </div>
                                    <div>
                                        <label className="text-[10px] text-gray-400 uppercase font-bold block">Scheduled At</label>
                                        <p className="text-sm font-semibold text-gray-800">{new Date(ticket.pickup_request.scheduled_at).toLocaleDateString()}</p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="space-y-6">
                        {/* User Details Card */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 className="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest">User Details</h3>
                            <div className="flex items-center gap-4 mb-6">
                                <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold text-lg">
                                    {ticket.name?.charAt(0) || 'U'}
                                </div>
                                <div>
                                    <h4 className="font-bold text-gray-800">{ticket.name || ticket.user?.name}</h4>
                                    <p className="text-xs text-gray-400 capitalize">{ticket.user_role || 'Visitor'}</p>
                                </div>
                            </div>
                            <div className="space-y-4">
                                <div>
                                    <label className="text-[10px] text-gray-400 uppercase font-bold block">Email Address</label>
                                    <p className="text-sm text-gray-700">{ticket.email || ticket.user?.email || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-[10px] text-gray-400 uppercase font-bold block">Phone Number</label>
                                    <p className="text-sm text-gray-700">{ticket.phone || ticket.user?.phone || 'N/A'}</p>
                                </div>
                            </div>
                            <div className="mt-8">
                                <Link
                                    href={ticket.user_id ? route('admin.users.index', { search: ticket.email }) : '#'}
                                    className="block w-full text-center py-2 bg-gray-50 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition-colors"
                                >
                                    View User History
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
