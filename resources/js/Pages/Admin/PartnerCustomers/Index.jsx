import AdminLayout from '@/Layouts/AdminLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Pagination from '@/Components/Pagination';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ customers, filters }) {
    const search = (e) => {
        router.get(route('admin.partner-customers.index'), { search: e.target.value }, {
            preserveState: true,
            replace: true,
        });
    };

    const destroy = (id) => {
        if (confirm('Delete this customer?')) {
            router.delete(route('admin.partner-customers.destroy', id));
        }
    };

    return (
        <AdminLayout>
            <Head title="Partner Customers" />

            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h1 className="text-3xl font-semibold text-gray-800">Customers</h1>
                <Link href={route('admin.partner-customers.create')}>
                    <PrimaryButton>Add Customer</PrimaryButton>
                </Link>
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
                <TextInput className="w-full md:w-96" placeholder="Search by name or mobile" value={filters.search || ''} onChange={search} />
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th className="px-6 py-3">Name</th>
                            <th className="px-6 py-3">Mobile</th>
                            <th className="px-6 py-3">City</th>
                            <th className="px-6 py-3">Pincode</th>
                            <th className="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {customers.data.map((customer) => (
                            <tr key={customer.id}>
                                <td className="px-6 py-4 font-medium text-gray-800">{customer.name}</td>
                                <td className="px-6 py-4">{customer.mobile}</td>
                                <td className="px-6 py-4">{customer.city || '-'}</td>
                                <td className="px-6 py-4">{customer.pincode || '-'}</td>
                                <td className="px-6 py-4 text-right space-x-3">
                                    <Link className="text-indigo-600 hover:underline" href={route('admin.partner-customers.edit', customer.id)}>Edit</Link>
                                    <button className="text-red-600 hover:underline" onClick={() => destroy(customer.id)}>Delete</button>
                                </td>
                            </tr>
                        ))}
                        {customers.data.length === 0 && (
                            <tr>
                                <td className="px-6 py-10 text-center text-gray-500" colSpan="5">No customers found.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <Pagination links={customers.links} className="mt-6" />
        </AdminLayout>
    );
}
