export default function AdminCard({
    title,
    value,
    icon,
    color = 'green',
    subtext = null,
    trend = null
}) {
    const isDanger = color === 'red';
    const colorClasses = isDanger
        ? 'border-red-100 bg-white text-red-700 shadow-[0_2px_8px_rgba(239,68,68,0.06)] hover:border-red-200 hover:shadow-[0_8px_20px_rgba(239,68,68,0.12)]'
        : 'border-[#dde8e0] bg-white text-primary shadow-[0_4px_16px_rgba(31,92,57,0.05)] hover:border-[#bad5c2] hover:shadow-[0_10px_24px_rgba(31,92,57,0.09)]';
    const iconColorClasses = isDanger
        ? 'bg-red-50 text-red-600 ring-1 ring-red-100 [&>svg]:!text-red-600'
        : 'bg-[#eaf3ed] text-primary ring-1 ring-[#d8e9dd] [&>svg]:!text-primary';

    return (
        <div className={`admin-stat-card min-h-[142px] rounded-2xl border p-5 transition-[transform,box-shadow,border-color] duration-200 ease-out hover:-translate-y-0.5 ${colorClasses}`}>
            <div className="flex items-start justify-between">
                <div className="flex-1">
                    <p className="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-500">{title}</p>
                    <p className="mt-3 text-[30px] leading-8 font-bold tracking-tight text-gray-950">{value}</p>
                    {subtext && (
                        <p className="mt-2 text-xs text-gray-400">{subtext}</p>
                    )}
                    {trend && (
                        <div className="flex items-center gap-1 mt-2 text-xs font-semibold">
                            <span className={trend.direction === 'up' ? 'text-green-600' : 'text-red-600'}>
                                {trend.direction === 'up' ? '↑' : '↓'} {trend.value}%
                            </span>
                            <span className="opacity-60">{trend.label}</span>
                        </div>
                    )}
                </div>
                {icon && (
                    <div className={`flex h-11 w-11 items-center justify-center rounded-xl transition-transform duration-200 ease-out group-hover:scale-105 ${iconColorClasses}`}>
                        {icon}
                    </div>
                )}
            </div>
        </div>
    );
}
