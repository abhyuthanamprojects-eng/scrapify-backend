import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import { useState } from 'react';

export default function Index({ kycDocuments, filters }) {
    const [selectedKyc, setSelectedKyc] = useState(null);
    const [remarks, setRemarks] = useState('');

    const handleVerify = (kycId, status) => {
        if (confirm(`Are you sure you want to ${status} this KYC document?`)) {
            router.post(route('admin.kyc.verify', kycId), {
                status,
                remarks,
            });
            setSelectedKyc(null);
            setRemarks('');
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            pending: 'bg-yellow-100 text-yellow-800',
            approved: 'bg-green-100 text-green-800',
            rejected: 'bg-red-100 text-red-800',
        };
        
        return badges[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AdminLayout>
            <Head title="KYC Verification" />

            <div className="mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">KYC Verification</h1>
                <p className="text-gray-600 mt-2">Review and verify user KYC documents</p>
            </div>

            {/* Filters */}
            <div className="bg-white shadow-md rounded-lg p-4 mb-6">
                <div className="flex gap-4">
                    <Link
                        href={route('admin.kyc.index')}
                        className={`px-4 py-2 rounded-md ${!filters.status ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Pending
                    </Link>
                    <Link
                        href={route('admin.kyc.index', { status: 'approved' })}
                        className={`px-4 py-2 rounded-md ${filters.status === 'approved' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Approved
                    </Link>
                    <Link
                        href={route('admin.kyc.index', { status: 'rejected' })}
                        className={`px-4 py-2 rounded-md ${filters.status === 'rejected' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'}`}
                    >
                        Rejected
                    </Link>
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Document Type
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Document Number
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Submitted
                            </th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {kycDocuments.data.map((kyc) => (
                            <tr key={kyc.id}>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">
                                        {kyc.user?.name}
                                    </div>
                                    <div className="text-sm text-gray-500">
                                        {kyc.user?.mobile}
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {kyc.document_type}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {kyc.document_number}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadge(kyc.status)}`}>
                                        {kyc.status}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {new Date(kyc.created_at).toLocaleDateString()}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    {kyc.status === 'pending' && (
                                        <div className="flex justify-end gap-2">
                                            <button
                                                onClick={() => handleVerify(kyc.id, 'approved')}
                                                className="text-green-600 hover:text-green-900"
                                            >
                                                Approve
                                            </button>
                                            <button
                                                onClick={() => handleVerify(kyc.id, 'rejected')}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Reject
                                            </button>
                                        </div>
                                    )}
                                    {kyc.document_path && (
                                        <a
                                            href={kyc.document_path}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-indigo-600 hover:text-indigo-900 ml-4"
                                        >
                                            View Document
                                        </a>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {kycDocuments.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No KYC documents found.</p>
                    </div>
                )}
            </div>

            {/* Pagination */}
            {kycDocuments.links && kycDocuments.links.length > 3 && (
                <div className="mt-6 flex justify-center gap-1">
                    {kycDocuments.links.map((link, index) => (
                        link.url ? (
                            <Link
                                key={index}
                                href={link.url}
                                className={`px-4 py-2 border rounded ${
                                    link.active
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : 'bg-white text-gray-700 hover:bg-gray-50'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ) : (
                            <span
                                key={index}
                                className="px-4 py-2 border rounded text-gray-300 cursor-not-allowed bg-white"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        )
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
