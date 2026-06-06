import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ approvalRequest }) {
    const [showRemarkModal, setShowRemarkModal] = useState(false);
    const [actionType, setActionType] = useState(null); // 'approve' or 'reject'

    const { data, setData, post, processing } = useForm({
        remarks: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        const routeName = actionType === 'approve' 
            ? 'admin.approval-requests.approve' 
            : 'admin.approval-requests.reject';
        
        post(route(routeName, approvalRequest.id), {
            onSuccess: () => setShowRemarkModal(false),
        });
    };

    const statusColors = {
        pending: 'bg-orange-100 text-orange-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
    };

    const openModal = (type) => {
        setActionType(type);
        setShowRemarkModal(true);
    };

    return (
        <AdminLayout>
            <Head title="Review Request" />

            <div className="max-w-4xl mx-auto flex flex-col gap-6 pb-20">
                <div className="flex justify-between items-center">
                    <Link href={route('admin.approval-requests.index')} className="text-gray-500 hover:text-gray-700 flex items-center text-sm">
                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7"></path></svg>
                        Back to requests
                    </Link>
                    <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider ${statusColors[approvalRequest.status]}`}>
                        {approvalRequest.status}
                    </span>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="p-6 border-b border-gray-100">
                        <h2 className="text-xl font-bold text-gray-800">Request Details</h2>
                        <p className="text-sm text-gray-500">Review the information submitted by the partner.</p>
                    </div>

                    <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 className="text-xs font-bold text-gray-400 uppercase mb-3">Request Information</h3>
                            <div className="space-y-4">
                                <div>
                                    <label className="text-xs text-gray-400 block">Type</label>
                                    <p className="text-sm font-semibold text-gray-800 capitalize">{approvalRequest.entity_type.replace(/_/g, ' ')}</p>
                                </div>
                                <div>
                                    <label className="text-xs text-gray-400 block">Action Requested</label>
                                    <p className="text-sm font-semibold text-gray-800 capitalize">{approvalRequest.request_type}</p>
                                </div>
                                <div>
                                    <label className="text-xs text-gray-400 block">Submitted At</label>
                                    <p className="text-sm font-semibold text-gray-800">{new Date(approvalRequest.created_at).toLocaleString()}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 className="text-xs font-bold text-gray-400 uppercase mb-3">Submitter Details</h3>
                            <div className="space-y-4">
                                <div>
                                    <label className="text-xs text-gray-400 block">Partner Name</label>
                                    <p className="text-sm font-semibold text-gray-800">{approvalRequest.channel_partner?.full_name || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-xs text-gray-400 block">Business</label>
                                    <p className="text-sm font-semibold text-gray-800">{approvalRequest.channel_partner?.business_name || 'N/A'}</p>
                                </div>
                                <div>
                                    <label className="text-xs text-gray-400 block">Email / Phone</label>
                                    <p className="text-sm font-semibold text-gray-800">{approvalRequest.channel_partner?.email} | {approvalRequest.channel_partner?.phone}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="p-6 border-t border-gray-100 bg-gray-50">
                        <h3 className="text-xs font-bold text-gray-400 uppercase mb-4">Submitted Data (Payload)</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {Object.entries(approvalRequest.payload || {}).map(([key, value]) => (
                                <div key={key} className="bg-white p-3 rounded-lg border border-gray-200">
                                    <label className="text-[10px] text-gray-400 uppercase block leading-none mb-1">{key.replace(/_/g, ' ')}</label>
                                    <p className="text-sm font-medium text-gray-700 truncate">
                                        {typeof value === 'boolean' ? (value ? 'Yes' : 'No') : (value || '—')}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>

                    {approvalRequest.status === 'pending' ? (
                        <div className="p-6 border-t border-gray-100 flex gap-4">
                            <button
                                onClick={() => openModal('reject')}
                                className="flex-1 py-3 px-4 bg-white border border-red-200 text-red-600 rounded-lg font-semibold hover:bg-red-50 transition-colors"
                            >
                                Reject Request
                            </button>
                            <button
                                onClick={() => openModal('approve')}
                                className="flex-1 py-3 px-4 bg-primary text-white rounded-lg font-semibold hover:bg-opacity-90 transition-all shadow-md"
                            >
                                Approve & Finalize
                            </button>
                        </div>
                    ) : (
                        <div className="p-6 border-t border-gray-100 bg-white">
                            <div className="flex items-start p-4 bg-blue-50 border border-blue-100 rounded-lg">
                                <svg className="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div>
                                    <h4 className="text-sm font-bold text-blue-800">Processed by Admin</h4>
                                    <p className="text-sm text-blue-600 mt-1">
                                        <strong>Status:</strong> {approvalRequest.status.toUpperCase()} <br/>
                                        <strong>Processed At:</strong> {new Date(approvalRequest.approved_at).toLocaleString()} <br/>
                                        <strong>Admin Remarks:</strong> {approvalRequest.admin_remarks || 'No remarks provided.'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Remark Modal */}
            {showRemarkModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
                    <div className="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden">
                        <div className="p-6 border-b border-gray-100">
                            <h3 className="text-lg font-bold text-gray-800">
                                {actionType === 'approve' ? 'Confirm Approval' : 'Provide Rejection Reason'}
                            </h3>
                            <p className="text-sm text-gray-500 mt-1">
                                {actionType === 'approve' 
                                    ? 'This will create the entity (Pickup Boy/Warehouse) in the system.' 
                                    : 'Please explain why this request is being rejected.'}
                            </p>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6">
                            <textarea
                                value={data.remarks}
                                onChange={e => setData('remarks', e.target.value)}
                                className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:outline-none min-h-[120px] text-sm"
                                placeholder="Enter remarks for the partner..."
                                required={actionType === 'reject'}
                            />
                            <div className="flex gap-3 mt-6">
                                <button
                                    type="button"
                                    onClick={() => setShowRemarkModal(false)}
                                    className="flex-1 py-3 px-4 bg-gray-100 text-gray-600 rounded-xl font-semibold hover:bg-gray-200 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className={`flex-1 py-3 px-4 rounded-xl font-semibold text-white transition-all ${
                                        actionType === 'approve' ? 'bg-primary' : 'bg-red-600'
                                    } disabled:opacity-50`}
                                >
                                    {processing ? 'Processing...' : (actionType === 'approve' ? 'Confirm' : 'Reject')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
