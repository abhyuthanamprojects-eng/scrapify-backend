import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AdminHeader from '@/Components/Admin/AdminHeader';
import AdminFilters, { AdminFilterInput, AdminFilterSelect } from '@/Components/Admin/AdminFilters';
import AdminTable, { AdminTablePagination } from '@/Components/Admin/AdminTable';
import AdminCard from '@/Components/Admin/AdminCard';
import { AdminActionButton } from '@/Components/Admin/AdminButton';
import { Users, Plus, CheckCircle, XCircle } from 'lucide-react';

export default function Index({ users, filters, roles, states }) {
    const { auth } = usePage().props;

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this user?')) {
            router.delete(route('admin.users.destroy', id));
        }
    };

    const handleSearch = (e) => {
        router.get(route('admin.users.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleStateFilter = (e) => {
        router.get(route('admin.users.index'), {
            ...filters,
            state_id: e.target.value,
            city_id: '', // Reset city when state changes
        }, {
            preserveState: true,
        });
    };

    const handleCityFilter = (e) => {
        router.get(route('admin.users.index'), {
            ...filters,
            city_id: e.target.value,
        }, {
            preserveState: true,
        });
    };

    const availableCities = filters.state_id 
        ? (states.find(s => s.id == filters.state_id)?.cities || [])
        : [];

    return (
        <AdminLayout>
            <Head title="User Management" />

            <AdminHeader
                title="User Management"
                subtitle="Manage all users and their roles in the system"
                icon={<Users className="text-green-600" size={24} />}
                action={{
                    label: 'Add New User',
                    href: route('admin.users.create'),
                    icon: <Plus size={18} />
                }}
            />

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <AdminCard
                    title="Total Users"
                    value={users.total?.toLocaleString() || '0'}
                    icon={<Users className="text-white" size={24} />}
                    color="green"
                    subtext="Based on current filters"
                />
                <AdminCard
                    title="Active Users"
                    value={users.data.filter(u => u.status).length}
                    icon={<CheckCircle className="text-white" size={24} />}
                    color="green"
                    subtext="Currently active"
                />
                <AdminCard
                    title="Inactive Users"
                    value={users.data.filter(u => !u.status).length}
                    icon={<XCircle className="text-white" size={24} />}
                    color="red"
                    subtext="Deactivated"
                />
            </div>

            <AdminFilters>
                <AdminFilterInput
                    label="Search"
                    placeholder="Search by name, email or phone..."
                    value={filters.search || ''}
                    onChange={handleSearch}
                    colSpan="md:col-span-5"
                />
                <AdminFilterSelect
                    label="Status"
                    options={[
                        { value: '', label: 'All Status' },
                        { value: '1', label: 'Active' },
                        { value: '0', label: 'Inactive' }
                    ]}
                    value={filters.status ?? ''}
                    onChange={(e) => router.get(route('admin.users.index'), { ...filters, status: e.target.value }, { preserveState: true })}
                    colSpan="md:col-span-2"
                />
                <AdminFilterSelect
                    label="Role"
                    options={[
                        { value: '', label: 'All Roles' },
                        ...roles.map(role => ({ value: role, label: role }))
                    ]}
                    value={filters.role || ''}
                    onChange={(e) => router.get(route('admin.users.index'), { ...filters, role: e.target.value }, { preserveState: true })}
                    colSpan="md:col-span-2"
                />
                <AdminFilterSelect
                    label="State"
                    options={[
                        { value: '', label: 'All States' },
                        ...states.map(state => ({ value: state.id, label: state.name }))
                    ]}
                    value={filters.state_id || ''}
                    onChange={handleStateFilter}
                    colSpan="md:col-span-2"
                />
                <AdminFilterSelect
                    label="City"
                    options={[
                        { value: '', label: 'All Cities' },
                        ...availableCities.map(city => ({ value: city.id, label: city.name }))
                    ]}
                    value={filters.city_id || ''}
                    onChange={handleCityFilter}
                    disabled={!filters.state_id}
                    colSpan="md:col-span-2"
                />
            </AdminFilters>

            <AdminTable
                columns={[
                    {
                        key: 'name',
                        label: 'Name',
                        render: (value, user) => (
                            <div className="flex items-center gap-3">
                                <img
                                    className="h-10 w-10 rounded-full object-cover border border-gray-100"
                                    src={user.profile_photo_url || `https://ui-avatars.com/api/?name=${user.name}&background=f3f4f6&color=6b7280`}
                                    alt={user.name}
                                />
                                <div>
                                    <div className="font-semibold text-gray-900">{user.name}</div>
                                    <div className="text-xs text-gray-500">ID: {user.id}</div>
                                </div>
                            </div>
                        )
                    },
                    {
                        key: 'email',
                        label: 'Contact',
                        render: (value, user) => (
                            <div>
                                <div className="text-sm text-gray-900">{user.email}</div>
                                <div className="text-sm text-gray-500">{user.phone || '-'}</div>
                            </div>
                        )
                    },
                    {
                        key: 'roles',
                        label: 'Role',
                        render: (value) => (
                            <div className="flex flex-wrap gap-1">
                                {value.map(role => (
                                    <span key={role.id} className="px-2.5 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                                        {role.name}
                                    </span>
                                ))}
                            </div>
                        )
                    },
                    {
                        key: 'city',
                        label: 'City',
                        render: (value) => (
                            <span className="text-sm text-gray-900">{value?.name || '-'}</span>
                        )
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        render: (value) => (
                            <span className={`inline-flex items-center px-3 py-1.5 rounded-full font-semibold text-xs ${
                                value ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                            }`}>
                                {value ? 'Active' : 'Inactive'}
                            </span>
                        )
                    },
                    {
                        key: 'created_at',
                        label: 'Joined',
                        render: (value) => (
                            <span className="text-sm text-gray-600">
                                {new Date(value).toLocaleDateString()}
                            </span>
                        )
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        align: 'right',
                        render: (value, user) => (
                            <div className="flex justify-end gap-2">
                                <AdminActionButton
                                    action="edit"
                                    href={route('admin.users.edit', user.id)}
                                    label="Edit"
                                />
                                {auth.user.id !== user.id && (
                                    <AdminActionButton
                                        action="delete"
                                        onClick={() => handleDelete(user.id)}
                                        label="Delete"
                                    />
                                )}
                            </div>
                        )
                    }
                ]}
                data={users.data}
                emptyMessage="No users found"
            />

            <AdminTablePagination links={users.links} />
        </AdminLayout>
    );
}
