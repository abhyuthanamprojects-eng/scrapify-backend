import { Head, Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Show({ message }) {
    const { patch, processing } = useForm({
        status: message.status === 'pending' ? 'resolved' : 'pending'
    });

    const toggleStatus = () => {
        patch(route('admin.contacts.update-status', message.id));
    };

    return (
        <AdminLayout>
            <Head title="Contact Message Details" />

            <div className="flex justify-between items-center mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">Message Details</h1>
                <Link href={route('admin.contacts.index')}>
                    <SecondaryButton>Back to List</SecondaryButton>
                </Link>
            </div>

            <div className="bg-white shadow-md rounded-lg overflow-hidden p-8 max-w-4xl">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-b pb-8">
                    <div>
                        <h3 className="text-sm font-medium text-gray-400 uppercase tracking-wider mb-2">Sender Information</h3>
                        <div className="space-y-1">
                            <p className="text-lg font-semibold text-gray-900">{message.name}</p>
                            <p className="text-gray-600">{message.email}</p>
                            <p className="text-gray-600">{message.phone || 'No phone provided'}</p>
                            {message.user_role && (
                                <p className="text-xs mt-1">
                                    <span className="px-2 py-1 bg-purple-50 text-purple-700 rounded">{message.user_role}</span>
                                </p>
                            )}
                            {message.user && (
                                <p className="text-xs text-gray-500 mt-1">
                                    User #{message.user.id} ({message.user.phone})
                                </p>
                            )}
                        </div>
                    </div>
                    <div>
                        <h3 className="text-sm font-medium text-gray-400 uppercase tracking-wider mb-2">Message Metadata</h3>
                        <div className="space-y-1">
                            <div className="flex items-center">
                                <span className="text-gray-500 mr-2">Status:</span>
                                <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                    message.status === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                }`}>
                                    {message.status.toUpperCase()}
                                </span>
                            </div>
                            <div className="flex items-center">
                                <span className="text-gray-500 mr-2">Type:</span>
                                <span className={`px-2 inline-flex text-xs rounded ${
                                    message.type === 'order' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700'
                                }`}>{message.type || 'general'}</span>
                            </div>
                            {message.pickup_request_id && (
                                <div className="flex items-center">
                                    <span className="text-gray-500 mr-2">Order:</span>
                                    <Link href={route('admin.pickups.show', message.pickup_request_id)} className="text-indigo-600 hover:underline font-mono text-sm">
                                        #{message.pickup_request_id}
                                        {message.pickup_request?.pickup_code && ` (${message.pickup_request.pickup_code})`}
                                    </Link>
                                </div>
                            )}
                            <p className="text-gray-500">Received: {new Date(message.created_at).toLocaleString()}</p>
                            <p className="text-gray-500">ID: #{message.id}</p>
                        </div>
                    </div>
                </div>

                <div className="mb-8 border-b pb-8">
                    <h3 className="text-sm font-medium text-gray-400 uppercase tracking-wider mb-2">Subject</h3>
                    <p className="text-xl font-medium text-gray-900">{message.subject || '(No Subject)'}</p>
                </div>

                <div className="mb-8">
                    <h3 className="text-sm font-medium text-gray-400 uppercase tracking-wider mb-2">Message Body</h3>
                    <div className="bg-gray-50 rounded-lg p-6 text-gray-800 whitespace-pre-wrap leading-relaxed border border-gray-100">
                        {message.message}
                    </div>
                </div>

                <div className="flex justify-end gap-4">
                    <PrimaryButton onClick={toggleStatus} disabled={processing}>
                        Mark as {message.status === 'pending' ? 'Resolved' : 'Pending'}
                    </PrimaryButton>
                    <button
                        onClick={() => {
                            if (confirm('Are you sure you want to delete this message?')) {
                                router.delete(route('admin.contacts.destroy', message.id));
                            }
                        }}
                        className="px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Delete Message
                    </button>
                </div>
            </div>
        </AdminLayout>
    );
}
