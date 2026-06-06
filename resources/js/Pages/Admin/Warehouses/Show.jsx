import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Show({ warehouse, availablePickupBoys = [] }) {
    const [boys, setBoys] = useState(warehouse.pickup_boys || []);
    const [selectedBoyId, setSelectedBoyId] = useState('');
    const [busy, setBusy] = useState(false);
    const [err, setErr] = useState(null);

    const refresh = async () => {
        const { data } = await axios.get(route('admin.warehouses.pickupBoys', warehouse.id));
        setBoys(data?.data || []);
    };

    const attach = async () => {
        if (!selectedBoyId) return;
        setBusy(true);
        setErr(null);
        try {
            await axios.post(route('admin.warehouses.attachPickupBoy', warehouse.id), {
                pickup_boy_id: selectedBoyId,
                status: 'active',
            });
            setSelectedBoyId('');
            await refresh();
        } catch (e) {
            setErr(e.response?.data?.message || 'Failed to attach.');
        } finally {
            setBusy(false);
        }
    };

    const detach = async (userId) => {
        if (!confirm('Remove this pickup boy from warehouse?')) return;
        setBusy(true);
        try {
            await axios.delete(route('admin.warehouses.detachPickupBoy', [warehouse.id, userId]));
            await refresh();
        } finally {
            setBusy(false);
        }
    };

    const unmappedBoys = availablePickupBoys.filter(
        (u) => !boys.some((b) => b.id === u.id)
    );

    return (
        <AdminLayout>
            <Head title={`Warehouse: ${warehouse.name}`} />

            <div className="mb-6">
                <Link
                    href={route('admin.warehouses.index')}
                    className="text-indigo-600 hover:text-indigo-900 mb-4 inline-block"
                >
                    ← Back to Warehouses
                </Link>
                <h1 className="text-3xl font-semibold text-gray-800">{warehouse.name}</h1>
                <p className="text-gray-600 mt-2">
                    Code: <span className="font-mono">{warehouse.code}</span>
                </p>
            </div>

            <div className="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 className="text-xl font-semibold mb-4">Warehouse Information</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p className="text-sm text-gray-500">Location</p>
                        <p className="text-lg font-medium">
                            {warehouse.city?.name}, {warehouse.city?.state?.name}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Zone</p>
                        <p className="text-lg font-medium">{warehouse.zone || '—'}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Capacity</p>
                        <p className="text-lg font-medium">
                            {warehouse.capacity ? `${warehouse.capacity} kg` : 'Not specified'}
                        </p>
                    </div>
                    <div className="md:col-span-2">
                        <p className="text-sm text-gray-500">Service Pincodes</p>
                        {warehouse.service_pincodes?.length > 0 ? (
                            <div className="mt-2 flex flex-wrap gap-2">
                                {warehouse.service_pincodes.map((pincode) => (
                                    <span
                                        key={pincode}
                                        className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-sm text-indigo-700 border border-indigo-100"
                                    >
                                        {pincode}
                                    </span>
                                ))}
                            </div>
                        ) : (
                            <p className="text-lg font-medium text-gray-400">No pincodes configured</p>
                        )}
                    </div>
                    <div className="md:col-span-2">
                        <p className="text-sm text-gray-500">Address</p>
                        <p className="text-lg font-medium">{warehouse.address}</p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Coordinates</p>
                        <p className="text-sm font-mono">
                            {warehouse.latitude}, {warehouse.longitude}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-gray-500">Status</p>
                        <span
                            className={`inline-flex px-2 py-1 text-xs rounded-full ${
                                warehouse.status
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800'
                            }`}
                        >
                            {warehouse.status ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                </div>
            </div>

            <div className="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 className="text-xl font-semibold mb-4">Assigned Pickup Boys</h2>

                <div className="flex gap-2 mb-4">
                    <select
                        className="flex-1 border-gray-300 rounded-md shadow-sm"
                        value={selectedBoyId}
                        onChange={(e) => setSelectedBoyId(e.target.value)}
                    >
                        <option value="">Select pickup boy to attach…</option>
                        {unmappedBoys.map((u) => (
                            <option key={u.id} value={u.id}>
                                {u.name} ({u.phone})
                            </option>
                        ))}
                    </select>
                    <button
                        type="button"
                        disabled={!selectedBoyId || busy}
                        onClick={attach}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-md disabled:opacity-50"
                    >
                        Attach
                    </button>
                </div>
                {err && <div className="text-sm text-red-600 mb-2">{err}</div>}

                {boys.length === 0 ? (
                    <p className="text-gray-500 text-sm">No pickup boys mapped yet.</p>
                ) : (
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                    Name
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                    Phone
                                </th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                    Status
                                </th>
                                <th className="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {boys.map((b) => (
                                <tr key={b.id}>
                                    <td className="px-4 py-2 text-sm">{b.name}</td>
                                    <td className="px-4 py-2 text-sm">{b.phone}</td>
                                    <td className="px-4 py-2 text-sm">
                                        <span
                                            className={`inline-flex px-2 py-1 text-xs rounded-full ${
                                                b.pivot?.status === 'active'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            }`}
                                        >
                                            {b.pivot?.status || 'active'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-2 text-sm text-right">
                                        <button
                                            type="button"
                                            onClick={() => detach(b.id)}
                                            className="text-red-600 hover:text-red-800"
                                        >
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            <div className="bg-white shadow-md rounded-lg p-6">
                <h2 className="text-xl font-semibold mb-4">Recent Inventory Logs</h2>
                {warehouse.inventory_logs?.length > 0 ? (
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Date
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Category
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Type
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Quantity
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Pickup Ref
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {warehouse.inventory_logs.map((log) => (
                                <tr key={log.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {new Date(log.created_at).toLocaleString()}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {log.category?.name || 'N/A'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {log.type}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {log.quantity || log.weight}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        #{log.pickup_request?.id || 'N/A'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No inventory logs found.</p>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
