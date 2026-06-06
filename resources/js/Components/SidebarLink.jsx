import { Link } from '@inertiajs/react';

export default function SidebarLink({ active = false, className = '', children, ...props }) {
    return (
        <Link
            {...props}
            className={
                'flex items-center px-6 py-3 my-1 transition-all duration-200 rounded-lg mx-2 ' +
                (active
                    ? 'text-white bg-gradient-to-r from-green-600 to-green-700 font-semibold shadow-md'
                    : 'text-gray-600 hover:text-green-700 hover:bg-green-50 font-normal') +
                ' ' + className
            }
        >
            {children}
        </Link>
    );
}
