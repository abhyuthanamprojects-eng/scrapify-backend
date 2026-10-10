import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AdminHeader from '@/Components/Admin/AdminHeader';
import AdminFilters, { AdminFilterInput, AdminFilterSelect } from '@/Components/Admin/AdminFilters';
import AdminCard from '@/Components/Admin/AdminCard';
import { ClipboardList, CheckCircle, Clock3 } from 'lucide-react';

export default function Index({ entries, filters, stats, states }) {
    const handleSearch = (e) => {
        router.get(route('admin.waitlist.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (name, value) => {
        router.get(route('admin.waitlist.index'), {
            ...filters,
            [name]: value,
        }, {
            preserveState: true,
        });
    };

    const handleStatusUpdate = (id, status) => {
        router.put(route('admin.waitlist.update', id), { status }, {
            preserveScroll: true,
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this waitlist entry?')) {
            router.delete(route('admin.waitlist.destroy', id));
        }
    };

    const getStatusBadgeClass = (status) => {
        switch (status) {
            case 'new': return 'bg-blue-100 text-blue-800';
            case 'contacted': return 'bg-yellow-100 text-yellow-800';
            case 'planned': return 'bg-purple-100 text-purple-800';
            case 'launched': return 'bg-green-100 text-green-800';
            case 'closed': return 'bg-gray-100 text-gray-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AdminLayout>
            <Head title="Waitlist" />

            <AdminHeader
                title="Waitlist Management"
                subtitle="Track requests from customers outside active service areas"
                icon={<img src="/images/admin/3d/waitlist.png" alt="" className="h-10 w-10 object-contain" />}
                action={{
                    label: 'Export CSV',
                    onClick: () => { window.location.href = route('admin.waitlist.export'); },
                    icon: <ClipboardList size={18} />,
                }}
            />

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <AdminCard
                    title="Total Waitlist"
                    value={stats.total.toLocaleString()}
                    icon={<img src="/images/admin/3d/waitlist.png" alt="" className="h-12 w-12 object-contain" />}
                    color="green"
                    subtext="All submitted requests"
                />
                <AdminCard
                    title="New Requests"
                    value={stats.new.toLocaleString()}
                    icon={<Clock3 className="text-white" size={24} />}
                    color="blue"
                    subtext="Awaiting first contact"
                />
                <AdminCard
                    title="Closed Requests"
                    value={stats.closed.toLocaleString()}
                    icon={<CheckCircle className="text-white" size={24} />}
                    color="purple"
                    subtext="Completed waitlist records"
                />
            </div>

            <AdminFilters>
                <AdminFilterInput
                    label="Search"
                    placeholder="Search by name, city or phone..."
                    value={filters.search || ''}
                    onChange={handleSearch}
                    colSpan="md:col-span-5"
                />
                <AdminFilterSelect
                    label="Status"
                    options={[
                        { value: '', label: 'All Status' },
                        { value: 'new', label: 'New' },
                        { value: 'contacted', label: 'Contacted' },
                        { value: 'planned', label: 'Planned' },
                        { value: 'launched', label: 'Launched' },
                        { value: 'closed', label: 'Closed' },
                    ]}
                    value={filters.status || ''}
                    onChange={(e) => handleFilterChange('status', e.target.value)}
                    colSpan="md:col-span-2"
                />
                <AdminFilterSelect
                    label="State"
                    options={[
                        { value: '', label: 'All States' },
                        ...states.map((state) => ({ value: state, label: state })),
                    ]}
                    value={filters.state || ''}
                    onChange={(e) => handleFilterChange('state', e.target.value)}
                    colSpan="md:col-span-2"
                />
            </AdminFilters>

            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {entries.data.map((entry) => (
                            <tr key={entry.id}>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">{entry.name}</div>
                                    <div className="text-xs text-gray-500">{entry.email}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {entry.phone}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {entry.location_name || `${entry.city}${entry.state ? ', ' + entry.state : ''}`}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <select
                                        className={`text-xs font-semibold rounded-full px-2 py-1 border-none focus:ring-0 cursor-pointer ${getStatusBadgeClass(entry.status)}`}
                                        value={entry.status}
                                        onChange={(e) => handleStatusUpdate(entry.id, e.target.value)}
                                    >
                                        <option value="new">New</option>
                                        <option value="contacted">Contacted</option>
                                        <option value="planned">Planned</option>
                                        <option value="launched">Launched</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {new Date(entry.created_at).toLocaleDateString()}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                    <button
                                        onClick={() => handleDelete(entry.id)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {entries.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No waitlist entries found.</p>
                    </div>
                )}
            </div>

            <div className="mt-6 flex justify-center">
                <div className="flex gap-1">
                    {entries.links.map((link, i) => (
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1 border rounded ${
                                    link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'
                                }`}
                            />
                        ) : (
                            <span
                                key={i}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className="px-3 py-1 border rounded text-gray-300 cursor-not-allowed bg-white"
                            />
                        )
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
