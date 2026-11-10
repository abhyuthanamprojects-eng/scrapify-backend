import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({ active = false, className = '', children, ...props }) {
    return (
        <Link
            {...props}
            className={
                'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium transition-[border-color,background-color,color] duration-150 ease-out ' +
                (active
                    ? 'border-primary text-primary bg-green-50 font-semibold focus:outline-none focus:text-primary-hover focus:bg-green-100 focus:border-primary-hover'
                    : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300') +
                className
            }
        >
            {children}
        </Link>
    );
}
