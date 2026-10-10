import { Link } from '@inertiajs/react';

export default function SidebarLink({ active = false, className = '', children, ...props }) {
    return (
        <Link
            {...props}
            className={
                'flex items-center px-6 py-3 my-1 transition-all duration-200 rounded-lg mx-2 ' +
                (active
                    ? 'text-white bg-primary font-semibold shadow-sm'
                    : 'text-gray-600 hover:text-primary-hover hover:bg-green-50 font-normal') +
                ' ' + className
            }
        >
            {children}
        </Link>
    );
}
