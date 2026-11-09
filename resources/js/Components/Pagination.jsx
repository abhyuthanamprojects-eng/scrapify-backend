import { Link } from '@inertiajs/react';

export default function Pagination({ links }) {
    return (
        <div className="flex flex-wrap gap-1.5">
            {links.map((link, key) => (
                link.url === null ? (
                    <div
                        key={key}
                        className="rounded-xl border border-[#dfe9e2] bg-[#f8fbf9] px-3.5 py-2.5 text-sm leading-4 text-[#a4b0a9]"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <Link
                        key={key}
                        className={`rounded-xl border px-3.5 py-2.5 text-sm leading-4 transition hover:border-primary hover:bg-green-50 hover:text-primary focus:border-primary focus:text-primary ${link.active ? 'border-primary bg-primary text-white hover:bg-primary hover:text-white' : 'border-[#dfe9e2] bg-white text-[#526359]'}`}
                        href={link.url}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                )
            ))}
        </div>
    );
}
