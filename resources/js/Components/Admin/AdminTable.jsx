import { Link } from '@inertiajs/react';
import { Package } from 'lucide-react';

export default function AdminTable({
    columns,
    data,
    emptyMessage = 'No records found',
    emptyIcon = <Package className="mx-auto mb-4 text-gray-400" size={48} />
}) {
    return (
        <div className="admin-table-shell overflow-hidden rounded-2xl border bg-white">
            <div className="overflow-x-auto">
                <table className="w-full">
                    <thead className="border-b border-[#e5eee7] bg-[#f3f8f4]">
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    className="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-[0.13em] text-[#718078]"
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
                    <tbody className="divide-y divide-gray-100">
                        {data && data.length > 0 ? (
                            data.map((row, rowIndex) => (
                        <tr key={rowIndex} className="border-b border-[#edf2ee] transition-colors duration-150 last:border-b-0 hover:bg-[#f8fbf9]">
                                    {columns.map((column) => (
                                        <td
                                            key={column.key}
                                            className="px-5 py-3.5 text-sm text-[#4d5e53]"
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
                                    className="px-5 py-14 text-center"
                                >
                                    <div>
                                        {emptyIcon}
                                        <p className="text-sm font-medium text-[#7d8b83]">{emptyMessage}</p>
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
        <div className="mt-5 flex justify-center">
            <div className="flex gap-1.5">
                {links.map((link, i) => (
                    link.url ? (
                        <Link
                            key={i}
                            href={link.url}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`px-3.5 py-1.5 rounded-lg font-medium transition-all ${
                                link.active
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'border border-gray-200 bg-white text-gray-700 hover:border-primary hover:text-primary'
                            }`}
                        />
                    ) : (
                        <span
                            key={i}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className="px-3.5 py-1.5 rounded-lg text-gray-300 cursor-not-allowed bg-gray-50 border border-gray-200"
                        />
                    )
                ))}
            </div>
        </div>
    );
}
