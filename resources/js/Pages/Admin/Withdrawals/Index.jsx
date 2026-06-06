import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Pagination';

export default function Index({ withdrawals, filters }) {
    const [selectedWithdrawal, setSelectedWithdrawal] = useState(null);
    const [actionType, setActionType] = useState(null); // 'approve' or 'reject'

    const { data, setData, post, processing, reset, errors } = useForm({
        transaction_id: '',
        admin_notes: '',
    });

    const handleFilter = (key, value) => {
        router.get(route('admin.withdrawals.index'), { ...filters, [key]: value }, { preserveState: true });
    };

    const openModal = (withdrawal, type) => {
        setSelectedWithdrawal(withdrawal);
        setActionType(type);
        reset();
    };

    const closeModal = () => {
        setSelectedWithdrawal(null);
        setActionType(null);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const routeName = actionType === 'approve' ? 'admin.withdrawals.approve' : 'admin.withdrawals.reject';
        post(route(routeName, selectedWithdrawal.id), {
            onSuccess: () => closeModal(),
        });
    };

    const statusColors = {
        pending: 'bg-orange-100 text-orange-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
    };

    return (
        <AdminLayout>
            <Head title="Withdrawal Requests" />

            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Withdrawal Requests</h1>
                    <p className="text-gray-500 text-sm">Manage payout requests from partners and pickup boys.</p>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="flex flex-wrap gap-2 items-center">
                            {['all', 'pending', 'approved', 'rejected'].map((s) => (
                                <button
                                    key={s}
                                    onClick={() => handleFilter('status', s === 'all' ? '' : s)}
                                    className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors capitalize ${
                                        (filters.status === s || (!filters.status && s === 'all')) 
                                        ? 'bg-primary text-white' 
                                        : 'text-gray-500 hover:bg-gray-50'
                                    }`}
                                >
                                    {s}
                                </button>
                            ))}
                        </div>

                        <div className="flex gap-2">
                            <input
                                type="text"
                                placeholder="Search by name/phone..."
                                value={filters.search || ''}
                                onChange={e => handleFilter('search', e.target.value)}
                                className="text-sm bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-1 focus:ring-primary w-64"
                            />
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="bg-gray-50 text-gray-600 text-[10px] uppercase tracking-widest font-bold">
                                    <th className="px-6 py-4">User</th>
                                    <th className="px-6 py-4">Amount</th>
                                    <th className="px-6 py-4">Bank / UPI</th>
                                    <th className="px-6 py-4">Status</th>
                                    <th className="px-6 py-4">Date</th>
                                    <th className="px-6 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="text-sm">
                                {withdrawals.data.length === 0 ? (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-10 text-center text-gray-500 italic">No withdrawal requests found.</td>
                                    </tr>
                                ) : (
                                    withdrawals.data.map((w) => (
                                        <tr key={w.id} className="border-b border-gray-100 hover:bg-gray-50 transition">
                                            <td className="px-6 py-4">
                                                <div className="font-semibold text-gray-800">{w.partner?.name}</div>
                                                <div className="text-xs text-gray-400">{w.partner?.phone}</div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-lg font-bold text-gray-800">₹{w.amount}</div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {w.upi_id ? (
                                                    <div className="text-xs">
                                                        <span className="text-gray-400 block uppercase font-bold text-[8px]">UPI ID</span>
                                                        <span className="font-medium text-gray-700">{w.upi_id}</span>
                                                    </div>
                                                ) : (
                                                    <div className="text-xs">
                                                        <span className="text-gray-400 block uppercase font-bold text-[8px]">{w.bank_name}</span>
                                                        <span className="font-medium text-gray-700">{w.account_number}</span>
                                                        <span className="text-gray-400 block text-[9px]">{w.ifsc_code}</span>
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${statusColors[w.status]}`}>
                                                    {w.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500">
                                                {new Date(w.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                {w.status === 'pending' ? (
                                                    <div className="flex justify-end gap-2">
                                                        <button
                                                            onClick={() => openModal(w, 'reject')}
                                                            className="text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors border border-red-100"
                                                        >
                                                            Reject
                                                        </button>
                                                        <button
                                                            onClick={() => openModal(w, 'approve')}
                                                            className="bg-primary text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-opacity-90 transition-all shadow-sm"
                                                        >
                                                            Approve
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <div className="text-[10px] text-gray-400 italic">
                                                        Processed: {new Date(w.updated_at).toLocaleDateString()}
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {withdrawals.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-gray-100">
                            <Pagination links={withdrawals.links} />
                        </div>
                    )}
                </div>
            </div>

            {/* Action Modal */}
            {selectedWithdrawal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
                    <div className="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden">
                        <div className="p-6 border-b border-gray-100">
                            <h3 className="text-lg font-bold text-gray-800">
                                {actionType === 'approve' ? 'Approve Withdrawal' : 'Reject Withdrawal'}
                            </h3>
                            <p className="text-sm text-gray-500 mt-1">
                                {actionType === 'approve' 
                                    ? `Mark ₹${selectedWithdrawal.amount} as paid to ${selectedWithdrawal.partner?.name}.` 
                                    : `Reject request from ${selectedWithdrawal.partner?.name}. Amount will be refunded to wallet.`}
                            </p>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            {actionType === 'approve' && (
                                <div className="space-y-1">
                                    <label className="text-xs font-bold text-gray-500 uppercase">Transaction ID / Reference</label>
                                    <input
                                        type="text"
                                        value={data.transaction_id}
                                        onChange={e => setData('transaction_id', e.target.value)}
                                        className="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none text-sm"
                                        placeholder="e.g. TXN123456789"
                                        required
                                    />
                                    {errors.transaction_id && <p className="text-xs text-red-500">{errors.transaction_id}</p>}
                                </div>
                            )}
                            <div className="space-y-1">
                                <label className="text-xs font-bold text-gray-500 uppercase">Admin Remarks</label>
                                <textarea
                                    value={data.admin_notes}
                                    onChange={e => setData('admin_notes', e.target.value)}
                                    className="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-1 focus:ring-primary focus:outline-none text-sm min-h-[80px]"
                                    placeholder={actionType === 'reject' ? "Provide reason for rejection..." : "Any internal notes..."}
                                    required={actionType === 'reject'}
                                />
                                {errors.admin_notes && <p className="text-xs text-red-500">{errors.admin_notes}</p>}
                            </div>
                            
                            <div className="flex gap-3 mt-6">
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    className="flex-1 py-2 px-4 bg-gray-100 text-gray-600 rounded-lg font-semibold hover:bg-gray-200 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className={`flex-1 py-2 px-4 rounded-lg font-semibold text-white transition-all ${
                                        actionType === 'approve' ? 'bg-primary' : 'bg-red-600'
                                    } disabled:opacity-50`}
                                >
                                    {processing ? 'Processing...' : (actionType === 'approve' ? 'Confirm Payment' : 'Reject Request')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
