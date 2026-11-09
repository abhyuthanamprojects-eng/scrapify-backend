import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AdminHeader from '@/Components/Admin/AdminHeader';
import AdminCard from '@/Components/Admin/AdminCard';
import AdminFilters, { AdminFilterInput, AdminFilterSelect } from '@/Components/Admin/AdminFilters';
import { AdminTablePagination } from '@/Components/Admin/AdminTable';

export default function Index({ pickupBoys, stats, filters, states }) {
    
    const handleSearch = (e) => {
        router.get(route('admin.pickup-boys.index'), {
            ...filters,
            search: e.target.value,
        }, { preserveState: true, replace: true });
    };

    const handleStateFilter = (e) => {
        router.get(route('admin.pickup-boys.index'), {
            ...filters,
            state_id: e.target.value,
            city_id: '',
        }, { preserveState: true });
    };

    const handleCityFilter = (e) => {
        router.get(route('admin.pickup-boys.index'), {
            ...filters,
            city_id: e.target.value,
        }, { preserveState: true });
    };

    const handleStatusFilter = (e, key) => {
        router.get(route('admin.pickup-boys.index'), {
            ...filters,
            [key]: e.target.value,
        }, { preserveState: true });
    };

    const availableCities = filters.state_id 
        ? (states.find(s => s.id == filters.state_id)?.cities || [])
        : [];

    return (
        <AdminLayout>
            <Head title="Pickup Boys Management" />

            <AdminHeader
                title="Pickup Boys"
                subtitle="Monitor agents, availability, and daily pickup capacity"
                icon={<img src="/images/admin/3d/pickups.png" alt="" className="h-10 w-10 object-contain" />}
            />

            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <AdminCard title="Total Agents" value={stats.total.toLocaleString()} icon={<img src="/images/admin/3d/pickup-agent.png" alt="" className="h-12 w-12 object-contain mix-blend-multiply" />} color="green" subtext="Matching current filters" />
                <AdminCard title="Active Accounts" value={stats.active.toLocaleString()} icon={<img src="/images/admin/3d/active-users.png" alt="" className="h-12 w-12 object-contain mix-blend-multiply" />} color="green" subtext="Ready accounts" />
                <AdminCard title="Available Now" value={stats.available.toLocaleString()} icon={<img src="/images/admin/3d/pickups.png" alt="" className="h-12 w-12 object-contain" />} color="green" subtext="Available for assignments" />
                <AdminCard title="Online Now" value={stats.online.toLocaleString()} icon={<img src="/images/admin/3d/active-users.png" alt="" className="h-12 w-12 object-contain mix-blend-multiply" />} color="green" subtext="Currently online" />
            </div>

            <AdminFilters>
                <AdminFilterInput label="Search" placeholder="Search by name, email or phone..." value={filters.search || ''} onChange={handleSearch} colSpan="md:col-span-4" />
                <AdminFilterSelect label="Account status" options={[{ value: '', label: 'All accounts' }, { value: '1', label: 'Active account' }, { value: '0', label: 'Inactive account' }]} value={filters.status ?? ''} onChange={(e) => handleStatusFilter(e, 'status')} colSpan="md:col-span-2" />
                <AdminFilterSelect label="Availability" options={[{ value: '', label: 'All availability' }, { value: '1', label: 'Available' }, { value: '0', label: 'Unavailable' }]} value={filters.is_available ?? ''} onChange={(e) => handleStatusFilter(e, 'is_available')} colSpan="md:col-span-2" />
                <AdminFilterSelect label="Online status" options={[{ value: '', label: 'All online states' }, { value: '1', label: 'Online' }, { value: '0', label: 'Offline' }]} value={filters.is_online ?? ''} onChange={(e) => handleStatusFilter(e, 'is_online')} colSpan="md:col-span-2" />
                <AdminFilterSelect label="State" options={[{ value: '', label: 'All states' }, ...states.map(state => ({ value: state.id, label: state.name }))]} value={filters.state_id || ''} onChange={handleStateFilter} colSpan="md:col-span-2" />
                <AdminFilterSelect label="City" options={[{ value: '', label: 'All cities' }, ...availableCities.map(city => ({ value: city.id, label: city.name }))]} value={filters.city_id || ''} onChange={handleCityFilter} disabled={!filters.state_id} colSpan="md:col-span-2" />
            </AdminFilters>

            <div className="admin-table-shell overflow-hidden rounded-2xl border border-[#dfe9e2] bg-white shadow-[0_6px_20px_rgba(31,92,57,0.04)]">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status & Availability</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">City</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Today's Workload</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lifetime Stats</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {pickupBoys.data.map((user) => (
                            <tr key={user.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm font-medium text-gray-900">{user.name}</div>
                                    <div className="text-xs text-gray-500">ID: {user.id}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">{user.email}</div>
                                    <div className="text-sm text-gray-500">{user.phone}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="flex flex-col gap-1">
                                        <div>
                                            <span className={`px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                user.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                            }`}>
                                                A/c {user.status ? 'Active' : 'Inactive'}
                                            </span>
                                        </div>
                                        <div>
                                            <span className={`px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                user.is_available ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                                            }`}>
                                                {user.is_available ? 'Available' : 'Unavailable'}
                                            </span>
                                        </div>
                                        <div>
                                            <span className={`px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                user.is_online ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600'
                                            }`}>
                                                {user.is_online ? (
                                                    <span className="flex items-center gap-1">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Online
                                                    </span>
                                                ) : 'Offline'}
                                            </span>
                                        </div>
                                        {user.is_capacity_full && (
                                            <div>
                                                <span className="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                                    Capacity Full
                                                </span>
                                            </div>
                                        )}
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className="text-sm text-gray-900">{user.city?.name || '-'}</span>
                                    {user.city && <div className="text-xs text-gray-500">{user.city.state?.name}</div>}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="flex items-center gap-2">
                                        <div className="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden w-24">
                                            <div 
                                                className={`h-full transition-all ${user.is_capacity_full ? 'bg-orange-500' : 'bg-primary'}`} 
                                                style={{ width: `${Math.min((user.today_assignments_count / user.daily_capacity) * 100, 100)}%` }}
                                            ></div>
                                        </div>
                                        <span className="text-sm font-bold text-gray-700">{user.today_assignments_count}/{user.daily_capacity}</span>
                                    </div>
                                    <div className="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">Daily Allocation</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">Total: <span className="font-semibold">{user.assigned_pickups_count}</span></div>
                                    <div className="text-sm text-gray-500">Done: <span className="font-semibold">{user.completed_pickups_count}</span></div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        href={route('admin.pickup-boys.show', user.id)}
                                    className="inline-flex items-center rounded-xl bg-primary px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-primary-hover"
                                    >
                                        View Details & Track
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {pickupBoys.data.length === 0 && (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No pickup boys found matching your filters.</p>
                    </div>
                )}
            </div>

            <AdminTablePagination links={pickupBoys.links} />
        </AdminLayout>
    );
}
