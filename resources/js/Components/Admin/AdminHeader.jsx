import { Link } from '@inertiajs/react';

export default function AdminHeader({
    title,
    subtitle,
    action = null,
    icon = null
}) {
    return (
        <div className="mb-8">
            <div className="flex justify-between items-start">
                <div>
                    <div className="flex items-center gap-3 mb-2">
                        {icon && (
                            <div className="p-2 bg-green-100 rounded-lg">
                                {icon}
                            </div>
                        )}
                        <h1 className="text-4xl font-bold text-gray-900 tracking-tight">
                            {title}
                        </h1>
                    </div>
                    {subtitle && (
                        <p className="text-base text-gray-600 mt-2">{subtitle}</p>
                    )}
                </div>
                {action && (
                    <div>
                        {action.href ? (
                            <Link href={action.href}>
                                <button className="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold px-6 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2">
                                    <span>{action.icon || '+'}</span>
                                    {action.label}
                                </button>
                            </Link>
                        ) : (
                            <button
                                onClick={action.onClick}
                                className="bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold px-6 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2"
                            >
                                <span>{action.icon || '+'}</span>
                                {action.label}
                            </button>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
