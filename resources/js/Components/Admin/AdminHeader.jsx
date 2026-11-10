import { Link } from '@inertiajs/react';

export default function AdminHeader({
    title,
    subtitle,
    action = null,
    icon = null
}) {
    const actionBtnClass = "inline-flex items-center gap-2 select-none rounded-xl bg-primary px-4 py-2.5 text-sm font-bold text-white shadow-[0_4px_14px_rgba(47,125,79,0.22)] transition-[transform,background-color,box-shadow] duration-150 ease-out hover:bg-primary-hover active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-primary/15";

    return (
        <div className="admin-page-header mb-6">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div className="flex items-center gap-2.5 mb-1.5">
                        {icon && (
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#eaf3ed] ring-1 ring-[#d8e9dd]">
                                {icon}
                            </div>
                        )}
                        <h1 className="text-[30px] leading-8 font-bold tracking-[-0.03em] text-[#15241b]">
                            {title}
                        </h1>
                    </div>
                    {subtitle && (
                        <p className="text-sm text-[#718078]">{subtitle}</p>
                    )}
                </div>
                {action && (
                    <div className="shrink-0">
                        {action.href ? (
                            <Link href={action.href} className={actionBtnClass}>
                                <span>{action.icon || '+'}</span>
                                {action.label}
                            </Link>
                        ) : (
                            <button
                                type="button"
                                onClick={action.onClick}
                                className={actionBtnClass}
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
