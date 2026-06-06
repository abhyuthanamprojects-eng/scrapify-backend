import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

export default function PartnerRegisterSuccess() {
    return (
        <GuestLayout>
            <Head title="Registration Successful" />

            <div className="text-center py-12">
                <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-100 mb-6">
                    <svg className="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                
                <h1 className="text-3xl font-extrabold text-gray-900 mb-4">Application Submitted!</h1>
                <p className="text-lg text-gray-600 mb-8 max-w-md mx-auto">
                    Thank you for applying. Our team will review your business details and identity documents within 24-48 hours.
                </p>
                
                <div className="space-y-4">
                    <div className="bg-indigo-50 p-4 rounded-lg border border-indigo-100 text-sm text-indigo-800">
                        <p className="font-semibold">Next Steps:</p>
                        <ul className="list-disc list-inside mt-2 text-left space-y-1">
                            <li>Manual verification of Aadhaar and PAN.</li>
                            <li>Phone screening by our regional manager.</li>
                            <li>Approval & Fee confirmation detail email.</li>
                        </ul>
                    </div>

                    <div className="pt-6">
                        <Link
                            href={route('login')}
                            className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                        >
                            Back to Home
                        </Link>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
