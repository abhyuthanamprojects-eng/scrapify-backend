import { Head, useForm, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AddressManager from './AddressManager';

export default function Form({ user, roles, states, warehouses = [] }) {
    const isEditing = !!user;

    const userStateId = user?.city?.state_id || '';

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        phone: user?.phone || '',
        state_id: userStateId || '',
        city_id: user?.city_id || '',
        password: '',
        password_confirmation: '',
        roles: user?.roles?.map(r => r.name) || [],
        warehouse_id: user?.warehouse_id || '',
        status: user?.status ?? true,
        daily_capacity: user?.daily_capacity || 4,
        is_manual_offline: user?.is_manual_offline || false,
    });

    // Get cities for the currently selected state
    const availableCities = states.find(s => s.id == data.state_id)?.cities || [];

    const handleRoleChange = (role) => {
        const newRoles = data.roles.includes(role)
            ? data.roles.filter(r => r !== role)
            : [...data.roles, role];
        setData('roles', newRoles);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (isEditing) {
            put(route('admin.users.update', user.id));
        } else {
            post(route('admin.users.store'));
        }
    };

    return (
        <AdminLayout>
            <Head title={isEditing ? 'Edit User' : 'Create User'} />

        <div className="max-w-4xl pb-12">
            <div className="mb-8 flex justify-between items-end">
                <div>
                    <h1 className="text-4xl font-extrabold text-gray-900 tracking-tight mb-2">
                        {isEditing ? 'Edit Profile' : 'New User Agent'}
                    </h1>
                    <p className="text-gray-500 font-medium">
                        {isEditing ? `Managing settings for ${user.name}` : 'Configure access and credentials for the new platform user.'}
                    </p>
                </div>
                <Link href={route('admin.users.index')}>
                    <button className="flex items-center px-4 py-2 text-sm font-bold text-gray-600 hover:text-primary transition-colors">
                        <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to Directory
                    </button>
                </Link>
            </div>

            <div className="bg-white shadow-xl shadow-gray-200/40 rounded-3xl border border-gray-100 overflow-hidden">
                <form onSubmit={handleSubmit}>
                    {/* Top Accent Bar */}
                    <div className="h-2 bg-gradient-to-r from-primary/40 via-primary to-primary/40"></div>
                    
                    <div className="p-8 md:p-10 space-y-12">
                        {/* Section: Identity */}
                        <section>
                            <div className="flex items-center gap-3 mb-8">
                                <div className="p-2 bg-primary/10 rounded-lg text-primary">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                </div>
                                <h2 className="text-xl font-bold text-gray-800">Identity Details</h2>
                            </div>

                            {isEditing && user.profile_photo_url && (
                                <div className="mb-8 flex items-center gap-6 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <img 
                                        src={user.profile_photo_url} 
                                        className="w-20 h-20 rounded-2xl object-cover shadow-sm border-2 border-white" 
                                        alt={user.name} 
                                    />
                                    <div>
                                        <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Current Photo</p>
                                        <p className="text-sm text-gray-600">This image is displayed across the platform.</p>
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div className="space-y-1.5 single-input-group">
                                    <InputLabel htmlFor="name" value="Full Name" className="text-gray-600 ml-1" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        className="w-full transition-all focus:ring-4 focus:ring-primary/10"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="e.g. Johnathan Doe"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="space-y-1.5 single-input-group">
                                    <InputLabel htmlFor="email" value="Email Address" className="text-gray-600 ml-1" />
                                    <TextInput
                                        id="email"
                                        type="email"
                                        className="w-full transition-all focus:ring-4 focus:ring-primary/10"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="john@example.com"
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="space-y-1.5 single-input-group">
                                    <InputLabel htmlFor="phone" value="Phone Axis" className="text-gray-600 ml-1" />
                                    <TextInput
                                        id="phone"
                                        type="tel"
                                        inputMode="numeric"
                                        maxLength={10}
                                        pattern="[6-9][0-9]{9}"
                                        className="w-full transition-all focus:ring-4 focus:ring-primary/10"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value.replace(/\D/g, '').slice(0, 10))}
                                        placeholder="Mobile contact"
                                        required
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                                
                                <div className="flex items-center pl-1 pt-8">
                                    <label className="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            className="sr-only peer"
                                            checked={data.status}
                                            onChange={(e) => setData('status', e.target.checked)}
                                        />
                                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        <span className="ml-3 text-sm font-bold text-gray-700">Account Active</span>
                                    </label>
                                    <InputError message={errors.status} />
                                </div>
                            </div>
                        </section>

                        <hr className="border-gray-100" />

                        {/* Section: Location */}
                        <section>
                            <div className="flex items-center gap-3 mb-8">
                                <div className="p-2 bg-primary/10 rounded-lg text-primary">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                                <h2 className="text-xl font-bold text-gray-800">Primary Location</h2>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div className="space-y-1.5">
                                    <InputLabel htmlFor="state_id" value="State Authority" className="text-gray-600 ml-1" />
                                    <select
                                        id="state_id"
                                        className="w-full border-gray-300 focus:border-primary focus:ring-4 focus:ring-primary/10 rounded-2xl shadow-sm transition-all py-3 px-4"
                                        value={data.state_id}
                                        onChange={(e) => {
                                            setData(data => ({ ...data, state_id: e.target.value, city_id: '' }));
                                        }}
                                    >
                                        <option value="">Select Region</option>
                                        {states.map(state => (
                                            <option key={state.id} value={state.id}>{state.name}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.state_id} />
                                </div>

                                <div className="space-y-1.5">
                                    <InputLabel htmlFor="city_id" value="Assigned City" className="text-gray-600 ml-1" />
                                    <select
                                        id="city_id"
                                        className="w-full border-gray-300 focus:border-primary focus:ring-4 focus:ring-primary/10 rounded-2xl shadow-sm transition-all py-3 px-4 disabled:opacity-50 disabled:bg-gray-50"
                                        value={data.city_id}
                                        onChange={(e) => setData('city_id', e.target.value)}
                                        disabled={!data.state_id}
                                    >
                                        <option value="">Select Locale</option>
                                        {availableCities.map(city => (
                                            <option key={city.id} value={city.id}>{city.name}</option>
                                        ))}
                                    </select>
                                    <InputError message={errors.city_id} />
                                </div>
                            </div>
                        </section>

                        <hr className="border-gray-100" />

                        {/* Section: Roles */}
                        <section>
                            <div className="flex items-center gap-3 mb-8">
                                <div className="p-2 bg-primary/10 rounded-lg text-primary">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                </div>
                                <h2 className="text-xl font-bold text-gray-800">Permissions & Access</h2>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                {roles.filter(r => r !== 'channel_partner').map(role => {
                                    const isSelected = data.roles.includes(role);
                                    return (
                                        <label 
                                            key={role} 
                                            className={`group relative flex flex-col p-5 rounded-2xl border-2 transition-all cursor-pointer ${
                                                isSelected 
                                                ? 'border-primary bg-primary/5 shadow-md shadow-primary/5' 
                                                : 'border-gray-100 bg-white hover:border-gray-200 hover:shadow-sm'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                className="hidden"
                                                checked={isSelected}
                                                onChange={() => handleRoleChange(role)}
                                            />
                                            <div className="flex justify-between items-start mb-3">
                                                <div className={`p-2 rounded-xl transition-colors ${isSelected ? 'bg-primary text-white' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'}`}>
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                </div>
                                                <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all ${isSelected ? 'border-primary bg-primary text-white' : 'border-gray-200'}`}>
                                                    {isSelected && <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7"></path></svg>}
                                                </div>
                                            </div>
                                            <span className={`text-sm font-bold capitalize ${isSelected ? 'text-primary' : 'text-gray-700'}`}>
                                                {role.replace(/_/g, ' ')}
                                            </span>
                                            <p className="text-[11px] text-gray-400 mt-1 leading-tight">
                                                Grant user {role.replace(/_/g, ' ')} level authority across system modules.
                                            </p>
                                        </label>
                                    );
                                })}
                            </div>
                            <InputError message={errors.roles} className="mt-4" />
                        </section>

                        <hr className="border-gray-100" />
                        
                        {(data.roles.includes('pickup_boy') || data.roles.includes('warehouse')) && (
                            <section className="animate-in fade-in slide-in-from-top-4 duration-500">
                                <div className="flex items-center gap-3 mb-8">
                                    <div className="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011-1v5m-4 0h4"></path></svg>
                                    </div>
                                    <h2 className="text-xl font-bold text-gray-800">Operational Context</h2>
                                </div>

                                <div className="bg-indigo-50/50 rounded-3xl p-6 border border-indigo-100/50">
                                    <div className="max-w-md">
                                        <InputLabel htmlFor="warehouse_id" value="Assigned Warehouse" className="text-indigo-900/60 ml-1 mb-2" />
                                        <select
                                            id="warehouse_id"
                                            className="w-full border-gray-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl shadow-sm transition-all py-3 px-4"
                                            value={data.warehouse_id}
                                            onChange={(e) => setData('warehouse_id', e.target.value)}
                                        >
                                            <option value="">Select Primary Warehouse</option>
                                            {warehouses.map(w => (
                                                <option key={w.id} value={w.id}>{w.name} ({w.code})</option>
                                            ))}
                                        </select>
                                        <p className="mt-2 text-[11px] text-indigo-400 ml-2">
                                            This user will be mapped to the selected facility for pickups and inventory management.
                                        </p>
                                        <InputError message={errors.warehouse_id} />
                                    </div>
                                    
                                    {data.roles.includes('pickup_boy') && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8 pt-8 border-t border-indigo-100/50">
                                            <div className="space-y-1.5">
                                                <InputLabel htmlFor="daily_capacity" value="Daily Pickup Capacity" className="text-indigo-900/60 ml-1" />
                                                <TextInput
                                                    id="daily_capacity"
                                                    type="number"
                                                    min="1"
                                                    className="w-full border-gray-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-2xl shadow-sm transition-all py-3 px-4"
                                                    value={data.daily_capacity}
                                                    onChange={(e) => setData('daily_capacity', e.target.value)}
                                                    placeholder="e.g. 4"
                                                />
                                                <p className="text-[11px] text-indigo-400 ml-2">Maximum pickups this driver can handle per day.</p>
                                                <InputError message={errors.daily_capacity} />
                                            </div>

                                            <div className="flex flex-col justify-center space-y-3">
                                                <div className="flex items-center pl-1">
                                                    <label className="relative inline-flex items-center cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            className="sr-only peer"
                                                            checked={data.is_manual_offline}
                                                            onChange={(e) => setData('is_manual_offline', e.target.checked)}
                                                        />
                                                        <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-500"></div>
                                                        <span className="ml-3 text-sm font-bold text-gray-700">Force Offline Mode</span>
                                                    </label>
                                                </div>
                                                <p className="text-[11px] text-gray-400 ml-1">
                                                    If enabled, driver will stay offline even during 08:00 AM - 07:00 PM.
                                                </p>
                                                <div className="flex items-center gap-2 px-3 py-1.5 bg-white rounded-xl border border-indigo-100 w-fit">
                                                    <div className={`w-2 h-2 rounded-full ${user?.is_online ? 'bg-green-500 animate-pulse' : 'bg-gray-300'}`}></div>
                                                    <span className="text-[10px] font-bold text-gray-500 uppercase tracking-tight">
                                                        System Status: {user?.is_online ? 'Online' : 'Offline'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </section>
                        )}

                        {/* Section: Security */}
                        <section>
                            <div className="flex items-center gap-3 mb-8">
                                <div className="p-2 bg-primary/10 rounded-lg text-primary">
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                </div>
                                <h2 className="text-xl font-bold text-gray-800">Security Credentials</h2>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div className="space-y-1.5 focus-within:z-10">
                                    <InputLabel htmlFor="password" value={isEditing ? 'New Password' : 'Password'} className="text-gray-600 ml-1" />
                                    <TextInput
                                        id="password"
                                        type="password"
                                        className="w-full focus:ring-4 focus:ring-primary/10"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder={isEditing ? "•••••••• (Leave blank to keep current)" : "••••••••"}
                                        required={!isEditing}
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="space-y-1.5">
                                    <InputLabel htmlFor="password_confirmation" value="Confirm Sequence" className="text-gray-600 ml-1" />
                                    <TextInput
                                        id="password_confirmation"
                                        type="password"
                                        className="w-full focus:ring-4 focus:ring-primary/10"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        placeholder="Retype password"
                                        required={!isEditing || data.password}
                                    />
                                    <InputError message={errors.password_confirmation} />
                                </div>
                            </div>
                        </section>
                    </div>

                    {/* Form Footer */}
                    <div className="bg-gray-50/50 p-8 md:px-10 flex items-center justify-between border-t border-gray-100">
                        <p className="text-xs text-gray-400 font-medium italic">
                            All information is encrypted and stored securely.
                        </p>
                        <div className="flex items-center gap-6">
                             <Link href={route('admin.users.index')} className="text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">
                                Discard Changes
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-10 py-4 bg-primary text-white font-black text-sm uppercase tracking-widest rounded-2xl hover:bg-primary-hover active:scale-[0.98] transition-all shadow-lg shadow-primary/20 disabled:opacity-50"
                            >
                                {isEditing ? 'Commit Updates' : 'Initialize Account'}
                                <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

            {isEditing && (
                <div className="max-w-2xl mt-8 mb-12">
                    <AddressManager user={user} states={states} />
                </div>
            )}
        </AdminLayout>
    );
}
