import { Link } from '@inertiajs/react';

export default function DropdownLink({ className = '', children, ...props }) {
    return (
        <Link
            {...props}
            className={
                'block w-full px-4 py-2 text-start text-sm font-medium leading-5 text-gray-700 transition-[background-color,color] duration-100 ease-out hover:bg-green-50 hover:text-primary active:bg-green-100 focus:bg-green-50 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800 ' +
                className
            }
        >
            {children}
        </Link>
    );
}
