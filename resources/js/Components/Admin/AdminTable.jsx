import { Link } from '@inertiajs/react';
import { Package } from 'lucide-react';

export default function AdminTable({
    columns,
    data,
    emptyMessage = 'No records found',
    emptyIcon = <Package className="mx-auto mb-4 text-[#9aa79f]" size={44} strokeWidth={1.8} />
}) {
    return (
        <div className="admin-table-shell overflow-hidden rounded-2xl border border-[#dde8e0] bg-white shadow-[0_2px_14px_rgba(31,92,57,0.04)]">
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
                    <tbody className="divide-y divide-[#edf2ee]">
                        {data && data.length > 0 ? (
                            data.map((row, rowIndex) => (
                                <tr key={rowIndex} className="border-b border-[#edf2ee] transition-colors duration-100 ease-out last:border-b-0 hover:bg-[#f8fbf9]">
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
                                    <div className="flex flex-col items-center justify-center">
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
            <div className="flex flex-wrap items-center gap-1.5">
                {links.map((link, i) => (
                    link.url ? (
                        <Link
                            key={i}
                            href={link.url}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`select-none rounded-lg px-3.5 py-1.5 text-sm font-medium transition-[transform,background-color,border-color,color,box-shadow] duration-150 ease-out active:scale-[0.96] ${
                                link.active
                                    ? 'bg-primary text-white shadow-sm'
                                    : 'border border-[#dce7df] bg-white text-[#526359] hover:border-primary hover:text-primary'
                            }`}
                        />
                    ) : (
                        <span
                            key={i}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className="select-none rounded-lg border border-[#e5ece7] bg-[#f8fbf9] px-3.5 py-1.5 text-sm text-[#a4b0a9] cursor-not-allowed opacity-60"
                        />
                    )
                ))}
            </div>
        </div>
    );
}
