import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import TextInput from '@/Components/TextInput';
import AdminHeader from '@/Components/Admin/AdminHeader';
import AdminFilters, { AdminFilterInput, AdminFilterSelect } from '@/Components/Admin/AdminFilters';
import AdminTable, { AdminTablePagination } from '@/Components/Admin/AdminTable';
import AdminCard from '@/Components/Admin/AdminCard';
import { AdminActionButton } from '@/Components/Admin/AdminButton';
import { MapPin, Package, Code2, Activity, Plus, CheckCircle, XCircle } from 'lucide-react';

export default function Index({ warehouses, filters, states }) {
    const { auth } = usePage().props;
    const isAdmin = auth.user.roles.some(r => r.name === 'admin');

    const handleSearch = (e) => {
        router.get(route('admin.warehouses.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (name, value) => {
        const newFilters = { ...filters, [name]: value };
        if (name === 'state_id') newFilters.city_id = '';

        router.get(route('admin.warehouses.index'), newFilters, {
            preserveState: true,
        });
    };

    const availableCities = filters.state_id
        ? (states.find(s => s.id == filters.state_id)?.cities || [])
        : [];

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this warehouse?')) {
            router.delete(route('admin.warehouses.destroy', id));
        }
    };

    return (
        <AdminLayout>
            <Head title="Warehouses" />

            {/* Header Section */}
            <AdminHeader
                title="Warehouse Management"
                subtitle="Manage and organize all your warehouse locations and inventory"
                icon={<MapPin className="text-green-600" size={24} />}
                action={
                    isAdmin
                        ? {
                            label: 'Add New Warehouse',
                            href: route('admin.warehouses.create'),
                            icon: <Plus size={18} />
                        }
                        : null
                }
            />

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <AdminCard
                    title="Total Warehouses"
                    value={warehouses.total?.toLocaleString() || '0'}
                    icon={<Package className="text-white" size={24} />}
                    color="green"
                    subtext="Based on current filters"
                />
                <AdminCard
                    title="Active Warehouses"
                    value={warehouses.data.filter(w => w.status).length}
                    icon={<CheckCircle className="text-white" size={24} />}
                    color="green"
                    subtext="Currently active"
                />
                <AdminCard
                    title="Inactive Warehouses"
                    value={warehouses.data.filter(w => !w.status).length}
                    icon={<XCircle className="text-white" size={24} />}
                    color="red"
                    subtext="Deactivated"
                />
            </div>

            {/* Filters Section */}
            <AdminFilters>
                <AdminFilterInput
                    label="Search Warehouses"
                    placeholder="Search by name or code..."
                    value={filters.search || ''}
                    onChange={handleSearch}
                    colSpan="md:col-span-5"
                />
                <AdminFilterSelect
                    label="State"
                    options={[
                        { value: '', label: 'All States' },
                        ...states.map(state => ({ value: state.id, label: state.name }))
                    ]}
                    value={filters.state_id || ''}
                    onChange={(e) => handleFilterChange('state_id', e.target.value)}
                    colSpan="md:col-span-2"
                />
                <AdminFilterSelect
                    label="City"
                    options={[
                        { value: '', label: 'All Cities' },
                        ...availableCities.map(city => ({ value: city.id, label: city.name }))
                    ]}
                    value={filters.city_id || ''}
                    onChange={(e) => handleFilterChange('city_id', e.target.value)}
                    disabled={!filters.state_id}
                    colSpan="md:col-span-2"
                />
                <AdminFilterSelect
                    label="Status"
                    options={[
                        { value: '', label: 'All Status' },
                        { value: '1', label: 'Active' },
                        { value: '0', label: 'Inactive' }
                    ]}
                    value={filters.status ?? ''}
                    onChange={(e) => handleFilterChange('status', e.target.value)}
                    colSpan="md:col-span-3"
                />
            </AdminFilters>

            {/* Table Section */}
            <AdminTable
                columns={[
                    {
                        key: 'name',
                        label: 'Warehouse Name',
                        render: (value, warehouse) => (
                            <div>
                                <div className="font-semibold text-gray-900">{warehouse.name}</div>
                                <div className="text-sm text-gray-500">ID: {warehouse.id}</div>
                            </div>
                        )
                    },
                    {
                        key: 'code',
                        label: 'Code',
                        render: (value) => (
                            <div className="inline-flex items-center gap-2 bg-gray-100 text-gray-700 px-3 py-1 rounded-lg">
                                <Code2 size={16} />
                                <span className="font-medium text-sm">{value}</span>
                            </div>
                        )
                    },
                    {
                        key: 'location',
                        label: 'Location',
                        render: (value, warehouse) => (
                            <div className="flex items-center gap-2 text-gray-700">
                                <MapPin size={16} className="text-green-600" />
                                <span className="text-sm">{warehouse.city?.name}, {warehouse.city?.state?.name}</span>
                            </div>
                        )
                    },
                    {
                        key: 'capacity',
                        label: 'Capacity',
                        render: (value) => (
                            <div className="flex items-center gap-2 text-gray-700">
                                <Package size={16} className="text-green-600" />
                                <span className="text-sm font-medium">{value ? `${value.toLocaleString()} kg` : 'N/A'}</span>
                            </div>
                        )
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        render: (value) => (
                            <span className={`inline-flex items-center gap-2 px-4 py-2 rounded-full font-semibold text-xs ${
                                value
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            }`}>
                                <Activity size={14} />
                                {value ? 'Active' : 'Inactive'}
                            </span>
                        )
                    },
                    {
                        key: 'actions',
                        label: 'Actions',
                        align: 'right',
                        render: (value, warehouse) => (
                            <div className="flex justify-end gap-2">
                                <AdminActionButton
                                    action="view"
                                    href={route('admin.warehouses.show', warehouse.id)}
                                    label="View"
                                />
                                <AdminActionButton
                                    action="edit"
                                    href={route('admin.warehouses.edit', warehouse.id)}
                                    label="Edit"
                                />
                                {isAdmin && (
                                    <AdminActionButton
                                        action="delete"
                                        onClick={() => handleDelete(warehouse.id)}
                                        label="Delete"
                                    />
                                )}
                            </div>
                        )
                    }
                ]}
                data={warehouses.data}
                emptyMessage="No warehouses found"
                emptyIcon={<Package className="mx-auto mb-4 text-gray-400" size={48} />}
            />

            {/* Pagination */}
            <AdminTablePagination links={warehouses.links} />
        </AdminLayout>
    );
}
