export default function AdminCard({
    title,
    value,
    icon,
    color = 'green',
    subtext = null,
    trend = null
}) {
    const colorClasses = {
        green: 'bg-gradient-to-br from-green-50 to-green-100 border-green-200 text-green-700',
        blue: 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200 text-blue-700',
        red: 'bg-gradient-to-br from-red-50 to-red-100 border-red-200 text-red-700',
        purple: 'bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200 text-purple-700',
        orange: 'bg-gradient-to-br from-orange-50 to-orange-100 border-orange-200 text-orange-700',
    };

    const iconColorClasses = {
        green: 'bg-green-200 text-green-700',
        blue: 'bg-blue-200 text-blue-700',
        red: 'bg-red-200 text-red-700',
        purple: 'bg-purple-200 text-purple-700',
        orange: 'bg-orange-200 text-orange-700',
    };

    return (
        <div className={`rounded-xl border p-6 ${colorClasses[color]}`}>
            <div className="flex items-start justify-between">
                <div className="flex-1">
                    <p className="text-sm font-medium opacity-80">{title}</p>
                    <p className="text-3xl font-bold mt-2">{value}</p>
                    {subtext && (
                        <p className="text-xs opacity-70 mt-2">{subtext}</p>
                    )}
                    {trend && (
                        <div className="flex items-center gap-1 mt-3 text-xs font-semibold">
                            <span className={trend.direction === 'up' ? 'text-green-600' : 'text-red-600'}>
                                {trend.direction === 'up' ? '↑' : '↓'} {trend.value}%
                            </span>
                            <span className="opacity-60">{trend.label}</span>
                        </div>
                    )}
                </div>
                {icon && (
                    <div className={`p-3 rounded-lg ${iconColorClasses[color]}`}>
                        {icon}
                    </div>
                )}
            </div>
        </div>
    );
}
