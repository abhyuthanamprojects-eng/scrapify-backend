import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Index({ pickupBoys, filters, states }) {
    
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

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">Pickup Boys (Agents)</h1>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden p-6 mb-6">
                <div className="flex flex-col md:flex-row gap-4 items-center justify-between flex-wrap">
                    <div className="w-full md:w-1/4">
                        <TextInput
                            placeholder="Search by name, email or phone..."
                            className="w-full"
                            value={filters.search || ''}
                            onChange={handleSearch}
                        />
                    </div>
                    <div className="w-full md:w-auto flex flex-wrap gap-2">
                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            value={filters.status ?? ''}
                            onChange={(e) => handleStatusFilter(e, 'status')}
                        >
                            <option value="">A/c Status (All)</option>
                            <option value="1">Active Account</option>
                            <option value="0">Inactive Account</option>
                        </select>

                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            value={filters.is_available ?? ''}
                            onChange={(e) => handleStatusFilter(e, 'is_available')}
                        >
                            <option value="">Availability (All)</option>
                            <option value="1">Available</option>
                            <option value="0">Unavailable</option>
                        </select>

                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            value={filters.is_online ?? ''}
                            onChange={(e) => handleStatusFilter(e, 'is_online')}
                        >
                            <option value="">Online Status (All)</option>
                            <option value="1">Online</option>
                            <option value="0">Offline</option>
                        </select>

                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                            value={filters.state_id || ''}
                            onChange={handleStateFilter}
                        >
                            <option value="">All States</option>
                            {states.map(state => (
                                <option key={state.id} value={state.id}>{state.name}</option>
                            ))}
                        </select>
                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:opacity-50 text-sm disabled:bg-gray-100"
                            value={filters.city_id || ''}
                            onChange={handleCityFilter}
                            disabled={!filters.state_id}
                        >
                            <option value="">All Cities</option>
                            {availableCities.map(city => (
                                <option key={city.id} value={city.id}>{city.name}</option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden">
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
                                        className="inline-flex items-center px-3 py-1.5 bg-primary text-white hover:bg-primary-dark rounded shadow-sm text-sm"
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

            <div className="mt-6 flex justify-center">
                <div className="flex gap-1 flex-wrap">
                    {pickupBoys.links.map((link, i) => (
                        link.url ? (
                            <Link
                                key={i}
                                href={link.url}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1 border rounded ${
                                    link.active ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 hover:bg-gray-50'
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
