import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { useState } from 'react';

export default function Show({ partner }) {
    const [showRejectModal, setShowRejectModal] = useState(false);

    const { data: approveData, setData: setApproveData, post: postApprove, processing: processingApprove } = useForm({
        fee_amount: partner.onboarding_fee_amount || 5000,
    });

    const { data: feeData, setData: setFeeData, post: postFee, processing: processingFee } = useForm({
        status: partner.fee_payment_status,
        reference: partner.payment_reference || '',
        remark: partner.payment_remark || '',
    });

    const { data: rejectData, setData: setRejectData, post: postReject, processing: processingReject } = useForm({
        rejection_reason: '',
        admin_remark: '',
    });

    const handleApprove = (e) => {
        e.preventDefault();
        if (confirm('Are you sure you want to approve this partner application? A user account will be created.')) {
            postApprove(route('admin.channel-partners.approve', partner.id));
        }
    };

    const handleFeeUpdate = (e) => {
        e.preventDefault();
        postFee(route('admin.channel-partners.fee-status', partner.id));
    };

    const handleReject = (e) => {
        e.preventDefault();
        if (!rejectData.rejection_reason.trim()) {
            alert('Please provide a rejection reason.');
            return;
        }
        postReject(route('admin.channel-partners.reject', partner.id), {
            onSuccess: () => setShowRejectModal(false),
        });
    };

    const statusBadge = {
        pending: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        under_review: 'bg-blue-100 text-blue-700 border-blue-200',
        approved: 'bg-green-100 text-green-700 border-green-200',
        rejected: 'bg-red-100 text-red-700 border-red-200',
    };

    return (
        <AdminLayout>
            <Head title={`Partner Review: ${partner.business_name}`} />

            {/* Header */}
            <div className="flex justify-between items-center mb-6">
                <div>
                    <Link href={route('admin.channel-partners.index')} className="text-indigo-600 hover:text-indigo-800 text-sm font-semibold mb-2 inline-flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" /></svg>
                        Back to Applications
                    </Link>
                    <h2 className="font-semibold text-2xl text-gray-800 leading-tight">Partner Application: {partner.business_name}</h2>
                    <p className="text-gray-600">Review documents and manage onboarding</p>
                </div>
                <div className={`px-4 py-2 rounded-full font-bold uppercase text-sm border ${statusBadge[partner.registration_status]}`}>
                    {partner.registration_status.replace('_', ' ')}
                </div>
            </div>

            {/* Rejected reason banner */}
            {partner.registration_status === 'rejected' && partner.rejection_reason && (
                <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div className="flex items-start gap-3">
                        <svg className="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <div>
                            <p className="font-bold text-red-800">Application Rejected</p>
                            <p className="text-red-700 text-sm mt-1">{partner.rejection_reason}</p>
                            {partner.rejected_at && (
                                <p className="text-red-500 text-xs mt-1">Rejected on: {new Date(partner.rejected_at).toLocaleDateString()}</p>
                            )}
                        </div>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-6">
                    {/* Business Details */}
                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-bold border-b pb-2 mb-4">Business Information</h3>
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p className="text-gray-500">Contact Person</p>
                                <p className="font-semibold">{partner.full_name}</p>
                            </div>
                            <div>
                                <p className="text-gray-500">Business Name</p>
                                <p className="font-semibold">{partner.business_name}</p>
                            </div>
                            <div>
                                <p className="text-gray-500">Email</p>
                                <p className="font-semibold">{partner.email}</p>
                            </div>
                            <div>
                                <p className="text-gray-500">Phone</p>
                                <p className="font-semibold">{partner.phone}</p>
                            </div>
                            <div>
                                <p className="text-gray-500">Applied On</p>
                                <p className="font-semibold">{new Date(partner.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                            </div>
                            {partner.approved_at && (
                                <div>
                                    <p className="text-gray-500">Approved On</p>
                                    <p className="font-semibold text-green-600">{new Date(partner.approved_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* KYC Documents */}
                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-bold border-b pb-2 mb-4">Identity & KYC</h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="bg-gray-50 p-4 rounded border border-gray-200 flex flex-col justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 uppercase tracking-wider">Aadhaar Number</p>
                                    <p className="text-lg font-mono font-bold">{partner.aadhaar_number}</p>
                                </div>
                                {partner.aadhaar_file ? (
                                    <div className="mt-3 pt-2 border-t border-gray-200">
                                        <a href={`/${partner.aadhaar_file}`} target="_blank" rel="noopener noreferrer" className="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                                            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                            View Aadhaar File
                                        </a>
                                    </div>
                                ) : (
                                    <div className="mt-3 pt-2 border-t border-gray-200">
                                        <span className="text-xs text-gray-400 italic">No document file</span>
                                    </div>
                                )}
                            </div>
                            <div className="bg-gray-50 p-4 rounded border border-gray-200 flex flex-col justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 uppercase tracking-wider">PAN Number</p>
                                    <p className="text-lg font-mono font-bold uppercase">{partner.pan_number}</p>
                                </div>
                                {partner.pan_file ? (
                                    <div className="mt-3 pt-2 border-t border-gray-200">
                                        <a href={`/${partner.pan_file}`} target="_blank" rel="noopener noreferrer" className="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                                            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                            View PAN File
                                        </a>
                                    </div>
                                ) : (
                                    <div className="mt-3 pt-2 border-t border-gray-200">
                                        <span className="text-xs text-gray-400 italic">No document file</span>
                                    </div>
                                )}
                            </div>
                            <div className="bg-gray-50 p-4 rounded border border-gray-200 flex flex-col justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 uppercase tracking-wider">GST Number</p>
                                    <p className="text-lg font-mono font-bold uppercase">{partner.gst_number || 'N/A'}</p>
                                </div>
                                {partner.gst_file ? (
                                    <div className="mt-3 pt-2 border-t border-gray-200">
                                        <a href={`/${partner.gst_file}`} target="_blank" rel="noopener noreferrer" className="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                                            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                            View GST File
                                        </a>
                                    </div>
                                ) : (
                                    <div className="mt-3 pt-2 border-t border-gray-200">
                                        <span className="text-xs text-gray-400 italic">No document file</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Location */}
                    <div className="bg-white shadow rounded-lg p-6">
                        <h3 className="text-lg font-bold border-b pb-2 mb-4">Proposed Location</h3>
                        <p className="mb-4"><strong>Proposed Area:</strong> {partner.opening_location_name}</p>
                        <p className="text-gray-700 bg-gray-50 p-3 rounded italic">{partner.address}, {partner.city}, {partner.state} - {partner.pincode}</p>
                    </div>
                </div>

                <div className="space-y-6">
                    {/* Approval Action */}
                    {!['approved', 'rejected'].includes(partner.registration_status) && (
                        <div className="bg-white shadow rounded-lg p-6 border-t-4 border-indigo-500">
                            <h3 className="text-lg font-bold mb-4">Review Application</h3>
                            <form onSubmit={handleApprove} className="space-y-4">
                                <div>
                                    <InputLabel value="Set Onboarding Fee (₹)" />
                                    <TextInput 
                                        type="number" 
                                        className="mt-1 block w-full" 
                                        value={approveData.fee_amount} 
                                        onChange={e => setApproveData('fee_amount', e.target.value)} 
                                    />
                                </div>
                                <PrimaryButton className="w-full justify-center py-3 bg-green-600 hover:bg-green-700 font-bold" disabled={processingApprove}>
                                    ✓ Approve & Create Account
                                </PrimaryButton>
                                <p className="text-xs text-gray-500 text-center">This will create a user account and trigger a welcome email.</p>
                            </form>

                            <div className="border-t mt-4 pt-4">
                                <button
                                    onClick={() => setShowRejectModal(true)}
                                    className="w-full py-3 px-4 bg-red-50 text-red-700 border border-red-200 rounded-md font-bold text-sm hover:bg-red-100 transition-colors"
                                >
                                    ✕ Reject Application
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Fee Management */}
                    {partner.registration_status === 'approved' && (
                        <div className="bg-white shadow rounded-lg p-6 border-t-4 border-green-500">
                            <h3 className="text-lg font-bold mb-4">Onboarding Fee Status</h3>
                            <div className="mb-4 text-center">
                                <p className="text-3xl font-bold text-gray-900">₹{partner.onboarding_fee_amount}</p>
                            </div>
                            
                            <form onSubmit={handleFeeUpdate} className="space-y-4">
                                <div>
                                    <InputLabel value="Payment Status" />
                                    <select 
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        value={feeData.status}
                                        onChange={e => setFeeData('status', e.target.value)}
                                    >
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid (Verified)</option>
                                        <option value="waived">Waived (Free)</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <InputLabel value="Payment Ref / Trans ID" />
                                    <TextInput 
                                        className="mt-1 block w-full" 
                                        value={feeData.reference} 
                                        onChange={e => setFeeData('reference', e.target.value)}
                                        placeholder="UTR / Check No"
                                    />
                                </div>

                                <PrimaryButton className="w-full justify-center bg-green-600 hover:bg-green-700" disabled={processingFee}>
                                    Update Fee Details
                                </PrimaryButton>
                                
                                {partner.login_enabled ? (
                                    <div className="p-2 bg-green-50 text-green-700 text-xs font-bold text-center rounded border border-green-200">
                                        🟢 Login Access: ENABLED
                                    </div>
                                ) : (
                                    <div className="p-2 bg-red-50 text-red-700 text-xs font-bold text-center rounded border border-red-200">
                                        🔴 Login Access: DISABLED
                                    </div>
                                )}
                            </form>
                        </div>
                    )}

                    {/* Admin Remark */}
                    {partner.admin_remark && (
                        <div className="bg-white shadow rounded-lg p-6 border-t-4 border-gray-400">
                            <h3 className="text-sm font-bold text-gray-600 uppercase tracking-wider mb-2">Admin Remark</h3>
                            <p className="text-gray-700 text-sm italic">{partner.admin_remark}</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Reject Modal */}
            {showRejectModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-2xl w-full max-w-md">
                        <div className="p-6">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                                    <svg className="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900">Reject Application</h3>
                                    <p className="text-sm text-gray-500">This action cannot be undone.</p>
                                </div>
                            </div>

                            <form onSubmit={handleReject} className="space-y-4">
                                <div>
                                    <InputLabel value="Rejection Reason *" />
                                    <textarea
                                        className="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                                        rows="3"
                                        value={rejectData.rejection_reason}
                                        onChange={e => setRejectData('rejection_reason', e.target.value)}
                                        placeholder="Explain why this application is being rejected..."
                                        required
                                    />
                                </div>

                                <div>
                                    <InputLabel value="Admin Remark (Optional)" />
                                    <TextInput
                                        className="mt-1 block w-full"
                                        value={rejectData.admin_remark}
                                        onChange={e => setRejectData('admin_remark', e.target.value)}
                                        placeholder="Internal note..."
                                    />
                                </div>

                                <div className="flex gap-3 pt-2">
                                    <button
                                        type="button"
                                        onClick={() => setShowRejectModal(false)}
                                        className="flex-1 py-2.5 px-4 bg-gray-100 text-gray-700 rounded-md text-sm font-semibold hover:bg-gray-200 transition-colors"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processingReject}
                                        className="flex-1 py-2.5 px-4 bg-red-600 text-white rounded-md text-sm font-bold hover:bg-red-700 transition-colors disabled:opacity-50"
                                    >
                                        {processingReject ? 'Rejecting...' : 'Confirm Reject'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
