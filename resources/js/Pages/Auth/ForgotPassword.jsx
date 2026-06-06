import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout title="Password Recovery">
            <Head title="Forgot Password" />

            <div className="mb-6 text-sm text-emerald-50 leading-relaxed text-center">
                Forgot your password? No problem. Just let us know your email address and we will email you a password
                reset link that will allow you to choose a new one.
            </div>

            {status && <div className="mb-6 font-medium text-sm text-teal-300 text-center bg-teal-900/40 py-2 rounded-lg border border-teal-500/30">{status}</div>}

            <form onSubmit={submit}>
                <div>
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full bg-white/5 border-white/20 text-white placeholder-white/50 focus:border-emerald-500 focus:ring-emerald-500 rounded-xl py-3"
                        isFocused={true}
                        placeholder="Enter your registered email"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2 text-red-300" />
                </div>

                <div className="mt-6 flex flex-col gap-4">
                    <PrimaryButton className="w-full h-12 flex items-center justify-center bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 border border-transparent rounded-xl text-white font-bold transition-all transform hover:scale-[1.02]" disabled={processing}>
                        {processing ? 'Queuing request...' : 'Email Reset Link'}
                    </PrimaryButton>
                    
                    <div className="text-center">
                        <a href={route('login')} className="text-sm font-medium text-emerald-400 hover:text-emerald-300 transition-colors">
                            Return to Sign In
                        </a>
                    </div>
                </div>
            </form>
        </GuestLayout>
    );
}
