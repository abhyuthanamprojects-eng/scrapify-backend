import { Link } from '@inertiajs/react';

export default function NavLink({ active = false, className = '', children, ...props }) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition-[border-color,color] duration-150 ease-out ' +
                (active
                    ? 'border-primary text-gray-900 focus:outline-none focus:border-primary-hover'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300') +
                className
            }
        >
            {children}
        </Link>
    );
}
