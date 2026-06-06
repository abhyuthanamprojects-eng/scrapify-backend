import { Link } from '@inertiajs/react';

export default function AdminButton({
    variant = 'primary',
    size = 'md',
    children,
    href = null,
    onClick = null,
    disabled = false,
    icon = null,
    type = 'button',
    className = '',
    ...props
}) {
    const variantClasses = {
        primary: 'bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white shadow-md hover:shadow-lg',
        secondary: 'bg-white text-gray-700 border border-gray-300 hover:border-green-500 hover:text-green-600',
        outline: 'border-2 border-green-600 text-green-600 hover:bg-green-50',
        danger: 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white shadow-md hover:shadow-lg',
        success: 'bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white shadow-md hover:shadow-lg',
        warning: 'bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 text-white shadow-md hover:shadow-lg',
        ghost: 'text-gray-700 hover:bg-gray-100',
    };

    const sizeClasses = {
        sm: 'px-3 py-1.5 text-sm font-medium rounded-md',
        md: 'px-4 py-2 text-sm font-semibold rounded-lg',
        lg: 'px-6 py-3 text-base font-semibold rounded-lg',
    };

    const disabledClass = disabled
        ? 'opacity-50 cursor-not-allowed'
        : 'transition-all duration-200';

    const baseClass = `inline-flex items-center gap-2 ${variantClasses[variant]} ${sizeClasses[size]} ${disabledClass} ${className}`;

    if (href) {
        return (
            <Link href={href}>
                <button
                    className={baseClass}
                    type={type}
                    disabled={disabled}
                    {...props}
                >
                    {icon && <span>{icon}</span>}
                    {children}
                </button>
            </Link>
        );
    }

    return (
        <button
            className={baseClass}
            onClick={onClick}
            type={type}
            disabled={disabled}
            {...props}
        >
            {icon && <span>{icon}</span>}
            {children}
        </button>
    );
}

export function AdminActionButton({
    action = 'view',
    href = null,
    onClick = null,
    label,
    icon,
    disabled = false,
}) {
    const actionStyles = {
        view: 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200',
        edit: 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200',
        delete: 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200',
        copy: 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200',
        download: 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200',
    };

    const baseClass = `inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full transition-all duration-150 ${
        actionStyles[action]
    } ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`;

    if (href) {
        return (
            <Link href={href}>
                <button className={baseClass} disabled={disabled}>
                    {icon && <span>{icon}</span>}
                    {label}
                </button>
            </Link>
        );
    }

    return (
        <button
            className={baseClass}
            onClick={onClick}
            disabled={disabled}
        >
            {icon && <span>{icon}</span>}
            {label}
        </button>
    );
}
