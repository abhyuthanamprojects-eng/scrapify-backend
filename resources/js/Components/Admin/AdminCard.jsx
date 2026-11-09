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
        ? 'border-red-100 bg-white text-red-700'
        : 'border-card-border bg-white text-primary';
    const iconColorClasses = isDanger
        ? 'bg-red-50 text-red-600 ring-red-100 [&>svg]:!text-red-600'
        : 'bg-green-50 text-primary ring-green-100 [&>svg]:!text-primary';

    return (
        <div className={`admin-stat-card min-h-[142px] rounded-2xl border p-5 transition duration-200 hover:-translate-y-0.5 ${colorClasses}`}>
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
                    <div className={`flex h-11 w-11 items-center justify-center rounded-xl ring-1 ${iconColorClasses}`}>
                        {icon}
                    </div>
                )}
            </div>
        </div>
    );
}
