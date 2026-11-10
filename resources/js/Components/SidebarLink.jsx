import { Link } from '@inertiajs/react';

export default function SidebarLink({ active = false, className = '', children, ...props }) {
    return (
        <Link
            {...props}
            className={
                'flex items-center px-4 py-2.5 my-0.5 select-none transition-[transform,background-color,color,box-shadow] duration-150 ease-out active:scale-[0.98] rounded-xl mx-3 text-[13px] ' +
                (active
                    ? 'text-white bg-primary font-semibold shadow-[0_4px_14px_rgba(47,125,79,0.25)]'
                    : 'text-gray-600 hover:text-primary-hover hover:bg-green-50 font-normal') +
                ' ' + className
            }
        >
            {children}
        </Link>
    );
}
