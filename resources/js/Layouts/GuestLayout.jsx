import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function Guest({ children, title }) {
    return (
        <div className="min-h-screen relative flex items-center justify-center bg-bg-light px-4 py-10 overflow-hidden">
            <div className="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_10%_20%,rgba(47,125,79,0.10),transparent_28%),radial-gradient(circle_at_90%_85%,rgba(47,125,79,0.08),transparent_30%)]" />

            <div className="relative z-10 w-full max-w-[520px]">
                <div className="flex justify-center mb-8">
                    <a href="/" className="group flex items-center justify-center">
                        <ApplicationLogo className="w-auto h-20 object-contain transition-transform duration-200 group-hover:scale-[1.02]" />
                    </a>
                </div>

                <div className="bg-white rounded-2xl border border-card-border/70 shadow-[0_18px_55px_rgba(31,92,57,0.10)] px-7 py-8 sm:px-10 sm:py-10">
                    {title && (
                        <div className="text-center mb-8">
                            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">{title}</h1>
                            <p className="mt-2 text-sm text-gray-500">Sign in to your Scrapify admin account to continue</p>
                        </div>
                    )}
                    {children}
                </div>
            </div>
        </div>
    );
}
