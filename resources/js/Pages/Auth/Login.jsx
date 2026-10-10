import Checkbox from '@/Components/Checkbox';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout title="Sign In">
            <Head title="Log in" />

            {status && <div className="mb-6 rounded-lg bg-green-50 border border-green-100 px-4 py-3 font-medium text-sm text-green-700">{status}</div>}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email Address" className="text-gray-700" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-2 block w-full bg-gray-50 border-gray-200 text-gray-900 placeholder-gray-400 focus:border-primary focus:ring-primary rounded-xl py-3 px-4"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" className="text-gray-700" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-2 block w-full bg-gray-50 border-gray-200 text-gray-900 placeholder-gray-400 focus:border-primary focus:ring-primary rounded-xl py-3 px-4"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="block mt-5 flex items-center justify-between">
                    <label className="flex items-center cursor-pointer">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="text-primary focus:ring-primary rounded border-gray-300"
                        />
                        <span className="ms-2 text-sm text-gray-500">Remember me</span>
                    </label>
                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-sm font-semibold text-primary hover:text-primary-hover rounded-md focus:outline-none"
                        >
                            Forgot password?
                        </Link>
                    )}
                </div>

                <div className="mt-7">
                    <PrimaryButton className="w-full h-12 flex items-center justify-center bg-primary hover:bg-primary-hover border border-transparent rounded-xl text-white font-bold transition-colors shadow-sm" disabled={processing}>
                        {processing ? 'Logging in...' : 'Sign In'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
