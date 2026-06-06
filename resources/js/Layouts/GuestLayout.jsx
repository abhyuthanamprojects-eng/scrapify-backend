import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function Guest({ children, title }) {
    return (
        <div className="min-h-screen relative flex items-center justify-center bg-slate-900 overflow-hidden">
            {/* Background Image / Overlay */}
            <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center opacity-40 mix-blend-overlay"></div>
            <div className="absolute inset-0 bg-gradient-to-br from-emerald-900/90 to-slate-900/95"></div>

            {/* Decorative Blobs */}
            <div className="absolute top-10 left-10 w-72 h-72 bg-emerald-500 rounded-full mix-blend-screen filter blur-[100px] opacity-40 animate-pulse"></div>
            <div className="absolute bottom-10 right-10 w-96 h-96 bg-teal-600 rounded-full mix-blend-screen filter blur-[120px] opacity-30 animate-pulse delay-700"></div>

            <div className="relative z-10 w-full sm:max-w-md px-8 py-10 bg-white/10 backdrop-blur-xl shadow-2xl overflow-hidden sm:rounded-3xl border border-white/20">
                <div className="flex flex-col items-center mb-8">
                    <a href="/" className="flex flex-col items-center group">
                        <div className="bg-white rounded-2xl shadow-lg group-hover:scale-105 transition-transform duration-300">
                            <ApplicationLogo className="w-full h-24 text-emerald-600" />
                        </div>
                    </a>
                    {title && <h2 className="mt-2 text-md font-medium text-emerald-100">{title}</h2>}
                </div>

                {children}
            </div>
        </div>
    );
}
