import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Show({ pickupBoy, assignments, trackingHistory }) {
    const [activeTab, setActiveTab] = useState('assignments');

    const toggleStatus = (field, currentValue) => {
        if (confirm(`Are you sure you want to change the ${field.replace('_', ' ')} status?`)) {
            router.put(route('admin.pickup-boys.update', pickupBoy.id), {
                [field]: !currentValue
            }, {
                preserveScroll: true
            });
        }
    };

    return (
        <AdminLayout>
            <Head title={`Pickup Boy Details - ${pickupBoy.name}`} />

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">Agent Profile: {pickupBoy.name}</h1>
                <Link href={route('admin.pickup-boys.index')} className="text-gray-500 hover:text-gray-700">
                    &larr; Back to List
                </Link>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                {/* Profile Card */}
                <div className="bg-white rounded-lg shadow p-6 border-t-4 border-primary">
                    <div className="flex items-center gap-4 mb-6">
                        <img 
                            src={pickupBoy.profile_photo_url || `https://ui-avatars.com/api/?name=${pickupBoy.name}&background=random&size=64`} 
                            className="rounded-full shadow-sm w-16 h-16 object-cover"
                            alt={pickupBoy.name}
                        />
                        <div>
                            <h2 className="text-xl font-bold text-gray-800">{pickupBoy.name}</h2>
                            <p className="text-sm text-gray-500">ID: {pickupBoy.id}</p>
                        </div>
                    </div>
                    
                    <div className="space-y-3 text-sm">
                        <div className="flex justify-between border-b pb-2">
                            <span className="text-gray-500">Phone:</span>
                            <span className="font-semibold">{pickupBoy.phone}</span>
                        </div>
                        <div className="flex justify-between border-b pb-2">
                            <span className="text-gray-500">Email:</span>
                            <span className="font-semibold">{pickupBoy.email}</span>
                        </div>
                        <div className="flex justify-between border-b pb-2">
                            <span className="text-gray-500">City:</span>
                            <span className="font-semibold">{pickupBoy.city?.name || 'N/A'}, {pickupBoy.city?.state?.name || ''}</span>
                        </div>
                        <div className="flex justify-between pb-2">
                            <span className="text-gray-500">Joined:</span>
                            <span className="font-semibold">{new Date(pickupBoy.created_at).toLocaleDateString()}</span>
                        </div>
                    </div>
                </div>

                {/* Controls & Stats */}
                <div className="bg-white rounded-lg shadow p-6 col-span-1 lg:col-span-2">
                    <h3 className="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Agent Controls & Performance</h3>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 className="text-sm font-semibold text-gray-600 mb-3 uppercase tracking-wider">Status Toggles</h4>
                            <div className="space-y-4">
                                <div className="flex items-center justify-between bg-gray-50 p-3 rounded border">
                                    <div>
                                        <div className="font-medium">Account Access</div>
                                        <div className="text-xs text-gray-500">Allow agent to login</div>
                                    </div>
                                    <button 
                                        onClick={() => toggleStatus('status', pickupBoy.status)}
                                        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${pickupBoy.status ? 'bg-green-500' : 'bg-gray-300'}`}
                                    >
                                        <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${pickupBoy.status ? 'translate-x-6' : 'translate-x-1'}`} />
                                    </button>
                                </div>
                                <div className="flex items-center justify-between bg-gray-50 p-3 rounded border">
                                    <div>
                                        <div className="font-medium">Availability for Assignment</div>
                                        <div className="text-xs text-gray-500">Can receive new pickups</div>
                                    </div>
                                    <button 
                                        onClick={() => toggleStatus('is_available', pickupBoy.is_available)}
                                        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${pickupBoy.is_available ? 'bg-blue-500' : 'bg-gray-300'}`}
                                    >
                                        <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${pickupBoy.is_available ? 'translate-x-6' : 'translate-x-1'}`} />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                             <h4 className="text-sm font-semibold text-gray-600 mb-3 uppercase tracking-wider">Performance Metrics</h4>
                             <div className="grid grid-cols-2 gap-4">
                                <div className="bg-blue-50 p-4 rounded-lg border border-blue-100 text-center">
                                    <div className="text-3xl font-bold text-blue-600">{pickupBoy.assigned_pickups_count}</div>
                                    <div className="text-xs font-semibold text-blue-800 uppercase mt-1">Total Assigned</div>
                                </div>
                                <div className="bg-green-50 p-4 rounded-lg border border-green-100 text-center">
                                    <div className="text-3xl font-bold text-green-600">{pickupBoy.completed_pickups_count}</div>
                                    <div className="text-xs font-semibold text-green-800 uppercase mt-1">Total Completed</div>
                                </div>
                             </div>
                             
                             <div className="mt-4 flex gap-2">
                                <span className={`px-2 py-1 text-xs font-semibold rounded ${pickupBoy.is_online ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800'}`}>
                                    {pickupBoy.is_online ? 'Currently Online' : 'Currently Offline'}
                                </span>
                                {pickupBoy.location_updated_at && (
                                    <span className="px-2 py-1 text-xs text-gray-500 bg-gray-50 rounded border">
                                       Last ping: {new Date(pickupBoy.location_updated_at).toLocaleString()}
                                    </span>
                                )}
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Tabs Section */}
            <div className="bg-white shadow-md rounded-lg overflow-hidden">
                <div className="border-b border-gray-200">
                    <nav className="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button
                            onClick={() => setActiveTab('assignments')}
                            className={`${
                                activeTab === 'assignments'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                        >
                            Assignment History
                        </button>
                        <button
                            onClick={() => setActiveTab('tracking')}
                            className={`${
                                activeTab === 'tracking'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                        >
                            Location Tracking Logs
                        </button>
                    </nav>
                </div>

                <div className="p-6">
                    {activeTab === 'assignments' && (
                        <div>
                            <table className="min-w-full divide-y divide-gray-200 border">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pickup Request</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location Status / Completed Date</th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {assignments.data.map((assignment) => (
                                        <tr key={assignment.id}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {new Date(assignment.assigned_at).toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">{assignment.pickup_request?.pickup_code}</div>
                                                <div className="text-xs text-gray-500 capitalize">{assignment.status}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">{assignment.pickup_request?.customer?.name || 'N/A'}</div>
                                                <div className="text-xs text-gray-500">{assignment.pickup_request?.customer?.phone}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {assignment.status === 'completed' && assignment.completed_at ? (
                                                    <span className="text-green-600 font-semibold">{new Date(assignment.completed_at).toLocaleString()}</span>
                                                ) : (
                                                    <span>Pending</span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <Link href={route('admin.pickups.show', assignment.pickup_request_id)} className="text-indigo-600 hover:text-indigo-900">
                                                    View Pickup
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                    {assignments.data.length === 0 && (
                                        <tr>
                                            <td colSpan="5" className="px-6 py-8 text-center text-gray-500">No assignments found for this agent.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                            
                            {/* Pagination */}
                            <div className="mt-4 flex justify-center">
                                <div className="flex gap-1 flex-wrap">
                                    {assignments.links.map((link, i) => (
                                        link.url ? (
                                            <Link
                                                key={i}
                                                href={link.url}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                preserveScroll
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
                        </div>
                    )}

                    {activeTab === 'tracking' && (
                        <div>
                            {pickupBoy.latitude && pickupBoy.longitude && (
                                <div className="mb-6 p-4 bg-indigo-50 border border-indigo-100 rounded-lg flex items-center justify-between">
                                    <div>
                                        <h4 className="font-bold text-indigo-800">Last Known Location</h4>
                                        <p className="text-sm text-indigo-600">Lat: {pickupBoy.latitude}, Lng: {pickupBoy.longitude}</p>
                                    </div>
                                    <div className="text-sm border bg-white px-3 py-1 rounded text-gray-600 font-medium">
                                        Updated {new Date(pickupBoy.location_updated_at).toLocaleString()}
                                    </div>
                                </div>
                            )}

                            <h4 className="font-bold text-gray-700 mb-4">Location Ping History (Latest 50)</h4>
                            <div className="border rounded-lg overflow-hidden">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coordinates (Lat, Lng)</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Map Link</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {trackingHistory.map((log) => (
                                            <tr key={log.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">
                                                    {new Date(log.created_at).toLocaleString()}
                                                </td>
                                                <td className="px-6 py-3 whitespace-nowrap text-sm font-mono text-gray-600">
                                                    {log.latitude}, {log.longitude}
                                                </td>
                                                <td className="px-6 py-3 whitespace-nowrap text-sm">
                                                    <a href={`https://maps.google.com/?q=${log.latitude},${log.longitude}`} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline inline-flex items-center">
                                                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                        View Map
                                                    </a>
                                                </td>
                                            </tr>
                                        ))}
                                        {trackingHistory.length === 0 && (
                                            <tr>
                                                <td colSpan="3" className="px-6 py-8 text-center text-gray-500">No tracking history recorded yet.</td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
