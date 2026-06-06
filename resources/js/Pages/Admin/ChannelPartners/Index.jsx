import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';

const statusTabs = [
    { label: 'All', value: '' },
    { label: 'Pending', value: 'pending' },
    { label: 'Under Review', value: 'under_review' },
    { label: 'Approved', value: 'approved' },
    { label: 'Rejected', value: 'rejected' },
];

export default function Index({ partners, filters }) {
    const handleSearch = (e) => {
        router.get(route('admin.channel-partners.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleStatusFilter = (status) => {
        router.get(route('admin.channel-partners.index'), {
            ...filters,
            status: status,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        under_review: 'bg-blue-100 text-blue-800',
    };

    const tabColors = {
        '': 'bg-gray-800 text-white',
        pending: 'bg-yellow-500 text-white',
        under_review: 'bg-blue-500 text-white',
        approved: 'bg-green-500 text-white',
        rejected: 'bg-red-500 text-white',
    };

    return (
        <AdminLayout>
            <Head title="Channel Partners" />

            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">Channel Partner Applications</h2>
                    <p className="text-sm text-gray-500 mt-1">Review and manage partner registration requests</p>
                </div>
                <div className="text-sm text-gray-500">
                    {partners.total} total application{partners.total !== 1 ? 's' : ''}
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden p-6 mb-6">
                <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div className="w-full md:w-1/3">
                        <TextInput
                            placeholder="Search by name, business or email..."
                            className="w-full"
                            value={filters.search || ''}
                            onChange={handleSearch}
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {statusTabs.map((tab) => (
                            <button
                                key={tab.value}
                                onClick={() => handleStatusFilter(tab.value)}
                                className={`px-4 py-1.5 rounded-full text-xs font-bold transition-all duration-200 border ${
                                    (filters.status || '') === tab.value
                                        ? `${tabColors[tab.value]} border-transparent shadow-md`
                                        : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6 bg-white border-b border-gray-200">
                    {partners.data.length === 0 ? (
                        <div className="text-center py-12">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">No applications found</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                {filters.status ? `No ${filters.status} applications.` : 'No partner applications yet.'}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full leading-normal">
                                <thead>
                                    <tr>
                                        <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Business Name
                                        </th>
                                        <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Partner Name
                                        </th>
                                        <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Contact
                                        </th>
                                        <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Fee Status
                                        </th>
                                        <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {partners.data.map((partner) => (
                                        <tr key={partner.id} className="hover:bg-gray-50 transition-colors">
                                            <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <p className="text-gray-900 font-bold">{partner.business_name}</p>
                                                <p className="text-gray-600 text-xs">{partner.city}, {partner.state}</p>
                                            </td>
                                            <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <p className="text-gray-900 whitespace-no-wrap">{partner.full_name}</p>
                                            </td>
                                            <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <p className="text-gray-900 whitespace-no-wrap">{partner.email}</p>
                                                <p className="text-gray-600 whitespace-no-wrap text-xs">{partner.phone}</p>
                                            </td>
                                            <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColors[partner.registration_status]}`}>
                                                    {partner.registration_status.replace('_', ' ')}
                                                </span>
                                                {partner.registration_status === 'rejected' && partner.rejection_reason && (
                                                    <p className="text-red-500 text-xs mt-1 max-w-[150px] truncate" title={partner.rejection_reason}>
                                                        {partner.rejection_reason}
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <span className={`font-bold ${partner.fee_payment_status === 'paid' ? 'text-green-600' : partner.fee_payment_status === 'waived' ? 'text-blue-600' : 'text-orange-600'}`}>
                                                    {partner.fee_payment_status.toUpperCase()}
                                                </span>
                                            </td>
                                            <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                {new Date(partner.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                <Link href={route('admin.channel-partners.show', partner.id)} className="text-indigo-600 hover:text-indigo-900 font-bold">
                                                    Review →
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Pagination */}
                    {partners.links && partners.links.length > 3 && (
                        <div className="flex items-center justify-between mt-6 pt-4 border-t">
                            <p className="text-sm text-gray-600">
                                Showing {partners.from} to {partners.to} of {partners.total}
                            </p>
                            <div className="flex gap-1">
                                {partners.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url || '#'}
                                        className={`px-3 py-1 rounded text-sm ${
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : link.url
                                                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        preserveState
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
