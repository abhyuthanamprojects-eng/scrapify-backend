import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';

export default function Index({ logs, filters, logNames }) {
    const handleSearch = (e) => {
        router.get(route('admin.logs.index'), {
            ...filters,
            search: e.target.value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleFilterChange = (name, value) => {
        router.get(route('admin.logs.index'), {
            ...filters,
            [name]: value,
        }, {
            preserveState: true,
        });
    };
    return (
        <AdminLayout>
            <Head title="Activity Logs" />

            <h2 className="font-semibold text-xl text-gray-800 leading-tight mb-6">Activity Logs</h2>

            <div className="bg-white shadow-md rounded-lg overflow-hidden p-6 mb-6">
                <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div className="w-full md:w-1/3">
                        <TextInput
                            placeholder="Search description, module..."
                            className="w-full"
                            value={filters.search || ''}
                            onChange={handleSearch}
                        />
                    </div>
                    <div className="w-full md:w-auto flex gap-4">
                        <select
                            className="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            value={filters.log_name || ''}
                            onChange={(e) => handleFilterChange('log_name', e.target.value)}
                        >
                            <option value="">All Log Names</option>
                            {logNames.map(name => (
                                <option key={name} value={name}>{name}</option>
                            ))}
                        </select>
                    </div>
                </div>
            </div>

            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6 bg-white border-b border-gray-200">
                    <div className="overflow-x-auto">
                        <table className="min-w-full leading-normal">
                             <thead>
                                <tr>
                                    <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        User
                                    </th>
                                     <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Action
                                    </th>
                                    <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Module
                                    </th>
                                     <th className="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        IP
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {logs.data.map((log) => (
                                    <tr key={log.id}>
                                        <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm whitespace-nowrap">
                                             {new Date(log.created_at).toLocaleString()}
                                        </td>
                                        <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            {log.causer ? (
                                                <div>
                                                    <p className="text-gray-900 font-bold whitespace-no-wrap">{log.causer.name}</p>
                                                    <p className="text-xs text-gray-500">{log.properties?.role || 'User'}</p>
                                                </div>
                                            ) : (
                                                 <span className="text-gray-500 italic">System/Guest</span>
                                            )}
                                        </td>
                                         <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            {log.properties?.action || log.event}
                                        </td>
                                        <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            {log.description}
                                        </td>
                                        <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                 {log.properties?.module || 'General'}
                                            </span>
                                        </td>
                                        <td className="px-5 py-5 border-b border-gray-200 bg-white text-sm text-xs">
                                            {log.properties?.ip_address}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    
                    <div className="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
                        <span className="text-xs xs:text-sm text-gray-900">
                            Showing {logs.from} to {logs.to} of {logs.total} Entries
                        </span>
                        <div className="inline-flex mt-2 xs:mt-0 flex-wrap gap-1">
                            {logs.links.map((link, i) => (
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1 text-sm border rounded ${
                                            link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'
                                        }`}
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className="px-3 py-1 text-sm border rounded text-gray-300 cursor-not-allowed bg-white"
                                    />
                                )
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
