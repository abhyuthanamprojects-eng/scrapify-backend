import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';

export default function Show({ pickup, pickupBoys }) {
    const { auth } = usePage().props;
    const canAssign = auth.user.roles.some(r => ['admin', 'warehouse'].includes(r.name));
    const isAdmin = auth.user.roles.some(r => r.name === 'admin');

    const [priceLogs, setPriceLogs] = useState(null);
    const [showLogs, setShowLogs] = useState(false);
    const [editingPrice, setEditingPrice] = useState(false);
    const [newAmount, setNewAmount] = useState(pickup.final_amount || pickup.estimated_amount || 0);
    const [priceReason, setPriceReason] = useState('');
    const [priceErr, setPriceErr] = useState(null);
    const [priceBusy, setPriceBusy] = useState(false);
    const [currentPickup, setCurrentPickup] = useState(pickup);
    const corporateEntries = pickup.metadata?.corporate_category_items || [];
    const corporateQuoteRequired = pickup.request_type === 'corporate' && pickup.estimated_amount === null;

    const loadLogs = async () => {
        if (priceLogs) { setShowLogs(!showLogs); return; }
        const { data } = await axios.get(route('admin.pickups.priceLogs', pickup.id));
        setPriceLogs(data?.data || []);
        setShowLogs(true);
    };

    const submitPrice = async () => {
        setPriceBusy(true);
        setPriceErr(null);
        try {
            const { data } = await axios.put(route('admin.pickups.updatePrice', pickup.id), {
                final_amount: Number(newAmount),
                reason: priceReason,
            });
            setCurrentPickup({ ...currentPickup, ...(data?.data || {}) });
            setEditingPrice(false);
            setPriceReason('');
            setPriceLogs(null);
        } catch (e) {
            setPriceErr(e.response?.data?.message || 'Update failed.');
        } finally {
            setPriceBusy(false);
        }
    };

    const priceLocked = !!currentPickup.price_locked_at;

    const { data, setData, post, processing, errors } = useForm({
        pickup_boy_id: '',
        notes: '',
        override_capacity: false,
    });

    const { data: rData, setData: setRData, post: rPost, processing: rProcessing, errors: rErrors } = useForm({
        new_scheduled_at: '',
        notes: '',
    });

    const { data: qData, setData: setQData, post: qPost, processing: qProcessing, errors: qErrors } = useForm({
        estimated_amount: pickup.estimated_amount || '',
        notes: pickup.metadata?.admin_quote_notes || '',
    });

    const assignPickupBoy = (e) => {
        e.preventDefault();
        post(route('admin.pickups.assign', pickup.id));
    };

    const approveReschedule = (e) => {
        e.preventDefault();
        rPost(route('admin.pickups.approve-reschedule', pickup.id));
    };

    const rejectReschedule = (e) => {
        e.preventDefault();
        rPost(route('admin.pickups.reject-reschedule', pickup.id));
    };

    const submitQuote = (e) => {
        e.preventDefault();
        qPost(route('admin.pickups.submit-quote', pickup.id));
    };

    const statusColors = {
        pending: 'bg-gray-100 text-gray-800',
        assigned: 'bg-blue-100 text-blue-800',
        accepted: 'bg-indigo-100 text-indigo-800',
        completed: 'bg-green-100 text-green-800',
        reschedule_requested: 'bg-orange-100 text-orange-800 font-bold',
        cancelled: 'bg-red-100 text-red-800',
    };

    return (
        <AdminLayout>
            <Head title={`Pickup #${pickup.id}`} />

            <div className="max-w-5xl mx-auto flex flex-col gap-6 pb-20">
                <div className="flex justify-between items-center">
                    <Link href={route('admin.pickups.index')} className="text-gray-500 hover:text-gray-700 flex items-center text-sm">
                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7"></path></svg>
                        Back to list
                    </Link>
                    <div className="flex gap-2">
                        <span className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${pickup.request_type === 'donation' ? 'bg-green-100 text-green-700' :
                                pickup.request_type === 'corporate' ? 'bg-blue-100 text-blue-700' :
                                    'bg-gray-100 text-gray-700'
                            }`}>
                            {pickup.request_type}
                        </span>
                        <span className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${statusColors[pickup.status] || 'bg-gray-100 text-gray-800'}`}>
                            {pickup.status.replace('_', ' ')}
                        </span>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        {/* Details Card */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div className="p-6 border-b border-gray-100 flex justify-between items-center">
                                <h2 className="text-lg font-bold text-gray-800">Order Details</h2>
                                <span className="text-sm font-bold text-gray-400">#{pickup.id}</span>
                            </div>
                            <div className="p-6">
                                <div className="grid grid-cols-2 gap-8">
                                    <div>
                                        <label className="text-[10px] text-gray-400 uppercase font-bold block mb-1">Scheduled Date</label>
                                        <p className="text-sm font-semibold text-gray-800">
                                            {new Date(pickup.scheduled_at).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' })}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-[10px] text-gray-400 uppercase font-bold block mb-1">Warehouse</label>
                                        <p className="text-sm font-semibold text-gray-800">{pickup.warehouse?.name || 'Not assigned'}</p>
                                    </div>
                                    <div className="col-span-2">
                                        <label className="text-[10px] text-gray-400 uppercase font-bold block mb-1">Pickup Address</label>
                                        <p className="text-sm font-medium text-gray-600 leading-relaxed">{pickup.address}</p>
                                    </div>
                                </div>

                                {pickup.request_type === 'donation' && (
                                    <div className="mt-6 p-4 bg-green-50 border border-green-100 rounded-lg">
                                        <h4 className="text-xs font-bold text-green-800 uppercase mb-1">Donation Category</h4>
                                        <p className="text-sm text-green-700 font-semibold capitalize">{pickup.donation_category || 'General Scrap'}</p>
                                        <p className="text-[10px] text-green-600 mt-1 italic">* This is a zero-payout donation request.</p>
                                    </div>
                                )}

                                {pickup.request_type === 'corporate' && (
                                    <div className="mt-6 p-4 bg-blue-50 border border-blue-100 rounded-lg space-y-4">
                                        <div>
                                            <h4 className="text-xs font-bold text-blue-800 uppercase mb-1">Corporate Enquiry Details</h4>
                                            <p className="text-[10px] text-blue-600 italic">Quote must be provided before assignment.</p>
                                        </div>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="text-[10px] text-blue-600 uppercase font-bold block mb-1">Company</label>
                                                <p className="text-sm text-blue-900 font-semibold">{pickup.metadata?.company_name || 'Not provided'}</p>
                                            </div>
                                            <div>
                                                <label className="text-[10px] text-blue-600 uppercase font-bold block mb-1">Contact</label>
                                                <p className="text-sm text-blue-900 font-semibold">
                                                    {pickup.metadata?.contact_name || '-'} · {pickup.metadata?.contact_mobile || '-'}
                                                </p>
                                                <p className="text-xs text-blue-700">{pickup.metadata?.contact_email || '-'}</p>
                                            </div>
                                            <div>
                                                <label className="text-[10px] text-blue-600 uppercase font-bold block mb-1">Meeting Type</label>
                                                <p className="text-sm text-blue-900 font-semibold capitalize">{(pickup.metadata?.meeting_type || '-').replaceAll('_', ' ')}</p>
                                            </div>
                                            <div>
                                                <label className="text-[10px] text-blue-600 uppercase font-bold block mb-1">GST</label>
                                                <p className="text-sm text-blue-900 font-semibold">{pickup.metadata?.gst_number || 'Not provided'}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label className="text-[10px] text-blue-600 uppercase font-bold block mb-2">Selected Corporate Items</label>
                                            <div className="flex flex-wrap gap-2">
                                                {corporateEntries.length > 0 ? corporateEntries.map((entry, index) => (
                                                    <span key={`${entry.corporate_category}-${index}`} className="px-3 py-1.5 rounded-full bg-white border border-blue-100 text-xs font-bold text-blue-800">
                                                        {entry.corporate_category} · {entry.quantity} {entry.unit}
                                                    </span>
                                                )) : (
                                                    <span className="text-xs text-blue-700">{pickup.metadata?.corporate_category || 'No category details available'}</span>
                                                )}
                                            </div>
                                        </div>
                                        {pickup.metadata?.notes && (
                                            <div>
                                                <label className="text-[10px] text-blue-600 uppercase font-bold block mb-1">Customer Notes</label>
                                                <p className="text-sm text-blue-900">{pickup.metadata.notes}</p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Items & Images */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div className="p-6 border-b border-gray-100">
                                <h2 className="text-lg font-bold text-gray-800">Items & Media</h2>
                            </div>
                            <div className="p-6 space-y-8">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {pickup.items.map((item) => (
                                        <div key={item.id} className="p-3 bg-gray-50 rounded-lg border border-gray-100 flex justify-between items-center">
                                            <div>
                                                <p className="text-sm font-bold text-gray-800">{item.category?.name?.en || item.category?.name || item.product_name || 'Item'}</p>
                                                <p className="text-xs text-gray-500">{item.quantity} units</p>
                                            </div>
                                            {item.price_per_unit > 0 && (
                                                <span className="text-sm font-bold text-primary">₹{item.price_per_unit} / unit</span>
                                            )}
                                        </div>
                                    ))}
                                </div>

                                {pickup.images?.length > 0 && (
                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                        {pickup.images.map((img) => (
                                            <a key={img.id} href={img.url} target="_blank" className="aspect-square rounded-lg overflow-hidden border border-gray-200 group">
                                                <img src={img.url} className="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                                            </a>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Reschedule Request */}
                        {pickup.status === 'reschedule_requested' && canAssign && (
                            <div className="bg-orange-50 border border-orange-200 rounded-xl p-6">
                                <h3 className="text-sm font-bold text-orange-800 uppercase mb-4">Pending Reschedule Request</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div className="space-y-1">
                                        <label className="text-[10px] text-orange-600 uppercase font-bold">New Proposed Date</label>
                                        <input
                                            type="datetime-local"
                                            className="w-full rounded-lg border-orange-200 text-sm focus:ring-orange-500"
                                            value={rData.new_scheduled_at}
                                            onChange={e => setRData('new_scheduled_at', e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <label className="text-[10px] text-orange-600 uppercase font-bold">Admin Notes</label>
                                        <input
                                            type="text"
                                            className="w-full rounded-lg border-orange-200 text-sm focus:ring-orange-500"
                                            value={rData.notes}
                                            onChange={e => setRData('notes', e.target.value)}
                                            placeholder="Reason..."
                                        />
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <button onClick={approveReschedule} disabled={rProcessing} className="flex-1 py-2 bg-orange-500 text-white font-bold rounded-lg hover:bg-orange-600 transition-colors">
                                        Approve
                                    </button>
                                    <button onClick={rejectReschedule} disabled={rProcessing} className="flex-1 py-2 bg-white text-orange-600 border border-orange-200 font-bold rounded-lg hover:bg-orange-100 transition-colors">
                                        Reject
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* Corporate Quote Section */}
                        {pickup.request_type === 'corporate' && (
                            <div className="bg-blue-50 border border-blue-100 rounded-xl p-6">
                                <h3 className="text-sm font-bold text-blue-800 uppercase mb-4">Corporate Quote Management</h3>
                                <form onSubmit={submitQuote} className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-1">
                                            <label className="text-[10px] text-blue-600 uppercase font-bold">Estimated Quote (₹)</label>
                                            <input
                                                type="number"
                                                className="w-full rounded-lg border-blue-200 text-sm focus:ring-blue-500 font-bold"
                                                value={qData.estimated_amount}
                                                onChange={e => setQData('estimated_amount', e.target.value)}
                                                placeholder="Enter amount..."
                                                required
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[10px] text-blue-600 uppercase font-bold">Quotation Notes</label>
                                            <input
                                                type="text"
                                                className="w-full rounded-lg border-blue-200 text-sm focus:ring-blue-500"
                                                value={qData.notes}
                                                onChange={e => setQData('notes', e.target.value)}
                                                placeholder="Internal or customer notes..."
                                            />
                                        </div>
                                    </div>
                                    <button type="submit" disabled={qProcessing} className="w-full py-2.5 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-all shadow-sm">
                                        {pickup.estimated_amount ? 'Update Quote' : 'Submit Initial Quote'}
                                    </button>
                                </form>
                            </div>
                        )}
                    </div>

                    <div className="space-y-6">
                        {/* User Sidebar Card */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 className="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest">User</h3>
                            <div className="flex items-center gap-4 mb-4">
                                {pickup.customer?.profile_photo_url ? (
                                    <img 
                                        className="w-10 h-10 rounded-full object-cover border border-gray-100" 
                                        src={pickup.customer.profile_photo_url} 
                                        alt={pickup.customer.name} 
                                    />
                                ) : (
                                    <div className="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold">
                                        {pickup.customer?.name?.charAt(0) || 'U'}
                                    </div>
                                )}
                                <div>
                                    <h4 className="font-bold text-gray-800">{pickup.customer?.name}</h4>
                                    <p className="text-xs text-gray-500">{pickup.customer?.phone}</p>
                                </div>
                            </div>
                            <div className="pt-4 border-t border-gray-50">
                                <label className="text-[10px] text-gray-400 uppercase font-bold block mb-1">Email</label>
                                <p className="text-sm text-gray-600 truncate">{pickup.customer?.email || 'N/A'}</p>
                            </div>
                        </div>

                        {/* Assignment Card */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 className="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest">Assignment</h3>

                            {pickup.assignment ? (
                                <div className="space-y-4">
                                    <div className="flex items-center gap-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                        <div className="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">
                                            {pickup.assignment.pickup_boy?.name?.charAt(0)}
                                        </div>
                                        <div>
                                            <p className="text-xs font-bold text-blue-900">{pickup.assignment.pickup_boy?.name}</p>
                                            <p className="text-[10px] text-blue-600 uppercase font-bold">{pickup.assignment.status}</p>
                                        </div>
                                    </div>
                                    {canAssign && pickup.status !== 'completed' && (
                                        <button onClick={() => setData('pickup_boy_id', '')} className="text-xs text-primary font-bold hover:underline">Change Assignment</button>
                                    )}
                                </div>
                            ) : (
                                canAssign ? (
                                    <form onSubmit={assignPickupBoy} className="space-y-3">
                                        {corporateQuoteRequired && (
                                            <div className="p-3 rounded-lg bg-blue-50 border border-blue-100 text-xs font-semibold text-blue-800">
                                                Submit the corporate quote before assigning a pickup boy.
                                            </div>
                                        )}
                                        <select
                                            value={data.pickup_boy_id}
                                            onChange={(e) => setData('pickup_boy_id', e.target.value)}
                                            className="w-full rounded-lg border-gray-200 text-xs focus:ring-primary"
                                            required
                                            disabled={corporateQuoteRequired}
                                        >
                                            <option value="" disabled>Select Pickup Boy</option>
                                            {pickupBoys.map((pb) => (
                                                <option key={pb.id} value={pb.id} disabled={pb.is_capacity_full && !data.override_capacity}>
                                                    {pb.name} {pb.is_online ? '(Online)' : '(Offline)'} {pb.is_capacity_full ? '[Full]' : `(${pb.today_assignments_count}/${pb.daily_capacity})`}
                                                </option>
                                            ))}
                                        </select>

                                        {isAdmin && (
                                            <div className="flex items-center gap-2 px-1">
                                                <input 
                                                    type="checkbox" 
                                                    id="override" 
                                                    checked={data.override_capacity} 
                                                    onChange={e => setData('override_capacity', e.target.checked)}
                                                    className="rounded border-gray-300 text-primary focus:ring-primary h-3 w-3"
                                                />
                                                <label htmlFor="override" className="text-[10px] font-bold text-gray-500 uppercase cursor-pointer">Bypass capacity checks</label>
                                            </div>
                                        )}
                                        <button type="submit" disabled={processing || corporateQuoteRequired} className="w-full py-2 bg-primary text-white text-xs font-bold rounded-lg hover:bg-opacity-90 disabled:opacity-50">
                                            Assign Now
                                        </button>
                                    </form>
                                ) : (
                                    <p className="text-xs text-gray-400 italic">No agent assigned yet.</p>
                                )
                            )}
                        </div>

                        {/* Timeline */}
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h3 className="text-xs font-bold text-gray-400 uppercase mb-4 tracking-widest">Audit Timeline</h3>
                            <div className="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                {pickup.status_logs?.map((log) => (
                                    <div key={log.id} className="relative pl-4 pb-2 border-l border-gray-100 last:border-0">
                                        <div className="absolute left-[-5px] top-0 w-2 h-2 rounded-full bg-gray-300" />
                                        <p className="text-[10px] text-gray-400 font-bold">{new Date(log.created_at).toLocaleDateString()}</p>
                                        <p className="text-xs font-bold text-gray-700 capitalize">{log.status.replace('_', ' ')}</p>
                                        {log.notes && <p className="text-[10px] text-gray-500 mt-0.5 italic">{log.notes}</p>}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
