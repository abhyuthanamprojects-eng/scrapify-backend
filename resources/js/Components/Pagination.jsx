import { Link } from '@inertiajs/react';

export default function Pagination({ links }) {
    return (
        <div className="flex flex-wrap items-center gap-1.5">
            {links.map((link, key) => (
                link.url === null ? (
                    <div
                        key={key}
                        className="select-none rounded-xl border border-[#dfe9e2] bg-[#f8fbf9] px-3.5 py-2 text-sm font-medium leading-4 text-[#a4b0a9] opacity-60"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <Link
                        key={key}
                        className={`select-none rounded-xl border px-3.5 py-2 text-sm font-medium leading-4 transition-[transform,background-color,border-color,color,box-shadow] duration-150 ease-out active:scale-[0.96] focus:outline-none focus:ring-2 focus:ring-primary/20 ${
                            link.active
                                ? 'border-primary bg-primary text-white shadow-sm hover:bg-primary-hover'
                                : 'border-[#dfe9e2] bg-white text-[#526359] hover:border-primary hover:bg-green-50 hover:text-primary'
                        }`}
                        href={link.url}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                )
            ))}
        </div>
    );
}
