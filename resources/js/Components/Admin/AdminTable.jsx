import { Link } from '@inertiajs/react';
import { Package } from 'lucide-react';

export default function AdminTable({
    columns,
    data,
    emptyMessage = 'No records found',
    emptyIcon = <Package className="mx-auto mb-4 text-gray-400" size={48} />
}) {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full">
                    <thead className="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className="px-8 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                                    style={{
                                        width: column.width || 'auto',
                                        textAlign: column.align || 'left'
                                    }}
                                >
                                    {column.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {data && data.length > 0 ? (
                            data.map((row, rowIndex) => (
                                <tr key={rowIndex} className="hover:bg-gray-50 transition-colors duration-150">
                                    {columns.map((column) => (
                                        <td
                                            key={column.key}
                                            className="px-8 py-5"
                                            style={{ textAlign: column.align || 'left' }}
                                        >
                                            {column.render
                                                ? column.render(row[column.key], row)
                                                : row[column.key]}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="text-center py-16"
                                >
                                    <div>
                                        {emptyIcon}
                                        <p className="text-gray-500 text-lg font-medium">{emptyMessage}</p>
                                    </div>
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export function AdminTablePagination({ links }) {
    if (!links || links.length <= 1) return null;

    return (
        <div className="mt-8 flex justify-center">
            <div className="flex gap-2">
                {links.map((link, i) => (
                    link.url ? (
                        <Link
                            key={i}
                            href={link.url}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`px-4 py-2 rounded-lg font-medium transition-all ${
                                link.active
                                    ? 'bg-gradient-to-r from-green-600 to-green-700 text-white shadow-md'
                                    : 'bg-white text-gray-700 border border-gray-300 hover:border-green-500 hover:text-green-600'
                            }`}
                        />
                    ) : (
                        <span
                            key={i}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className="px-4 py-2 rounded-lg text-gray-300 cursor-not-allowed bg-gray-50 border border-gray-200"
                        />
                    )
                ))}
            </div>
        </div>
    );
}
