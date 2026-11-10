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
        primary: 'bg-primary hover:bg-primary-hover text-white shadow-sm',
        secondary: 'bg-white text-gray-700 border border-gray-200 hover:border-primary hover:text-primary',
        outline: 'border border-primary text-primary hover:bg-green-50',
        danger: 'bg-red-600 hover:bg-red-700 text-white shadow-sm',
        success: 'bg-primary hover:bg-primary-hover text-white shadow-sm',
        warning: 'bg-primary hover:bg-primary-hover text-white shadow-sm',
        ghost: 'text-gray-700 hover:bg-green-50 hover:text-primary',
    };

    const sizeClasses = {
        sm: 'px-3 py-1.5 text-sm font-medium rounded-md',
        md: 'px-4 py-2 text-sm font-semibold rounded-lg',
        lg: 'px-6 py-3 text-base font-semibold rounded-lg',
    };

    const motionClasses = disabled
        ? 'opacity-50 cursor-not-allowed'
        : 'transition-[transform,background-color,border-color,color,box-shadow,opacity] duration-150 ease-out active:scale-[0.98]';

    const baseClass = `inline-flex items-center justify-center gap-2 select-none ${variantClasses[variant]} ${sizeClasses[size]} ${motionClasses} ${className}`;

    if (href && !disabled) {
        return (
            <Link
                href={href}
                className={baseClass}
                {...props}
            >
                {icon && <span>{icon}</span>}
                {children}
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
        view: 'bg-green-50 text-primary hover:bg-green-100 border border-green-100',
        edit: 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200',
        delete: 'bg-red-50 text-red-700 hover:bg-red-100 border border-red-200',
        copy: 'bg-gray-50 text-gray-700 hover:bg-green-50 hover:text-primary border border-gray-200',
        download: 'bg-green-50 text-primary hover:bg-green-100 border border-green-100',
    };

    const motionClasses = disabled
        ? 'opacity-50 cursor-not-allowed'
        : 'transition-[transform,background-color,border-color,color] duration-150 ease-out active:scale-[0.96]';

    const baseClass = `inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full select-none ${
        actionStyles[action]
    } ${motionClasses}`;

    if (href && !disabled) {
        return (
            <Link href={href} className={baseClass}>
                {icon && <span>{icon}</span>}
                {label}
            </Link>
        );
    }

    return (
        <button
            className={baseClass}
            onClick={onClick}
            disabled={disabled}
            type="button"
        >
            {icon && <span>{icon}</span>}
            {label}
        </button>
    );
}
