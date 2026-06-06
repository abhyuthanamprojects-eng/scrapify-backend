import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/Components/Pagination';

export default function Index({ settings, referrals, coupons, filters }) {
    const [activeTab, setActiveTab] = useState(filters.tab || 'settings');

    const handleTabChange = (tab) => {
        setActiveTab(tab);
        router.get(route('admin.referrals.index'), { ...filters, tab }, { preserveState: true });
    };

    const { data, setData, post, processing, reset, errors } = useForm({
        campaign_name: '',
        reward_type: 'fixed',
        reward_value: '',
        coupon_expiry_days: 30,
        min_booking_value: 0,
        is_active: true,
    });

    const submitSetting = (e) => {
        e.preventDefault();
        post(route('admin.referrals.store-setting'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <AdminLayout>
            <Head title="Referral Management" />

            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Referrals & Rewards</h1>
                    <p className="text-gray-500 text-sm">Manage referral campaigns, track user invitations, and monitor reward coupons.</p>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="flex border-b border-gray-100">
                        {['settings', 'referrals', 'coupons'].map((tab) => (
                            <button
                                key={tab}
                                onClick={() => handleTabChange(tab)}
                                className={`px-6 py-4 text-sm font-semibold capitalize transition-colors ${activeTab === tab ? 'text-primary border-b-2 border-primary' : 'text-gray-500 hover:text-gray-700'}`}
                            >
                                {tab}
                            </button>
                        ))}
                    </div>

                    <div className="p-6">
                        {activeTab === 'settings' && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <div className="lg:col-span-1">
                                    <div className="bg-gray-50 p-6 rounded-xl border border-gray-100">
                                        <h3 className="font-bold text-gray-800 mb-4">New Campaign</h3>
                                        <form onSubmit={submitSetting} className="space-y-4">
                                            <div className="space-y-1">
                                                <label className="text-xs font-bold text-gray-500 uppercase">Campaign Name</label>
                                                <input value={data.campaign_name} onChange={e => setData('campaign_name', e.target.value)} type="text" className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary text-sm" placeholder="Summer Referral Drive" />
                                                {errors.campaign_name && <p className="text-xs text-red-500">{errors.campaign_name}</p>}
                                            </div>
                                            <div className="grid grid-cols-2 gap-3">
                                                <div className="space-y-1">
                                                    <label className="text-xs font-bold text-gray-500 uppercase">Type</label>
                                                    <select value={data.reward_type} onChange={e => setData('reward_type', e.target.value)} className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary text-sm">
                                                        <option value="fixed">Flat Amount</option>
                                                        <option value="percentage">Percentage</option>
                                                        <option value="extra_value">Extra Value</option>
                                                    </select>
                                                </div>
                                                <div className="space-y-1">
                                                    <label className="text-xs font-bold text-gray-500 uppercase">Value</label>
                                                    <input value={data.reward_value} onChange={e => setData('reward_value', e.target.value)} type="number" className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary text-sm" />
                                                </div>
                                            </div>
                                            <div className="space-y-1">
                                                <label className="text-xs font-bold text-gray-500 uppercase">Coupon Expiry (Days)</label>
                                                <input value={data.coupon_expiry_days} onChange={e => setData('coupon_expiry_days', e.target.value)} type="number" className="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-primary text-sm" />
                                            </div>
                                            <button type="submit" disabled={processing} className="w-full py-3 bg-primary text-white rounded-lg font-bold hover:bg-opacity-90 transition-all disabled:opacity-50">
                                                Launch Campaign
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div className="lg:col-span-2">
                                    <h3 className="font-bold text-gray-800 mb-4">Active & Past Campaigns</h3>
                                    <div className="space-y-3">
                                        {settings.map((s) => (
                                            <div key={s.id} className="p-4 bg-white border border-gray-200 rounded-xl flex items-center justify-between">
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <h4 className="font-bold text-gray-800">{s.campaign_name}</h4>
                                                        {s.is_active ? (
                                                            <span className="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded-full uppercase">Active</span>
                                                        ) : (
                                                            <span className="px-2 py-0.5 bg-gray-100 text-gray-500 text-[10px] font-bold rounded-full uppercase">Paused</span>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        Reward: {s.reward_type === 'fixed' ? '₹' : ''}{s.reward_value}{s.reward_type === 'percentage' ? '%' : ''} • Expiry: {s.coupon_expiry_days} days
                                                    </p>
                                                </div>
                                                <button onClick={() => router.post(route('admin.referrals.update-setting', s.id), { is_active: !s.is_active })} className="text-sm font-semibold text-primary hover:underline">
                                                    {s.is_active ? 'Pause' : 'Activate'}
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeTab === 'referrals' && (
                            <div className="overflow-x-auto -mx-6">
                                <table className="w-full text-left">
                                    <thead className="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold tracking-widest border-b border-gray-100">
                                        <tr>
                                            <th className="px-6 py-4">Referrer</th>
                                            <th className="px-6 py-4">Referred User</th>
                                            <th className="px-6 py-4">Status</th>
                                            <th className="px-6 py-4">Reward</th>
                                            <th className="px-6 py-4">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody className="text-sm">
                                        {referrals.data.length === 0 ? (
                                            <tr><td colSpan="5" className="px-6 py-10 text-center text-gray-400">No referrals recorded yet.</td></tr>
                                        ) : (
                                            referrals.data.map(r => (
                                                <tr key={r.id} className="border-b border-gray-100">
                                                    <td className="px-6 py-4">
                                                        <div className="font-semibold text-gray-800">{r.referrer?.name}</div>
                                                        <div className="text-xs text-gray-500">{r.referrer?.phone}</div>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <div className="font-semibold text-gray-800">{r.referred?.name}</div>
                                                        <div className="text-xs text-gray-500">{r.referred?.phone}</div>
                                                    </td>
                                                    <td className="px-6 py-4 capitalize">{r.status}</td>
                                                    <td className="px-6 py-4">
                                                        <span className={r.reward_status === 'granted' ? 'text-green-600 font-bold' : 'text-gray-400'}>
                                                            {r.reward_status}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 text-gray-500">{new Date(r.created_at).toLocaleDateString()}</td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                                <div className="px-6 py-4"><Pagination links={referrals.links} /></div>
                            </div>
                        )}

                        {activeTab === 'coupons' && (
                            <div className="overflow-x-auto -mx-6">
                                <table className="w-full text-left">
                                    <thead className="bg-gray-50 text-gray-500 text-[10px] uppercase font-bold tracking-widest border-b border-gray-100">
                                        <tr>
                                            <th className="px-6 py-4">Code</th>
                                            <th className="px-6 py-4">User</th>
                                            <th className="px-6 py-4">Value</th>
                                            <th className="px-6 py-4">Status</th>
                                            <th className="px-6 py-4 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody className="text-sm">
                                        {coupons.data.length === 0 ? (
                                            <tr><td colSpan="5" className="px-6 py-10 text-center text-gray-400">No reward coupons found.</td></tr>
                                        ) : (
                                            coupons.data.map(c => (
                                                <tr key={c.id} className="border-b border-gray-100">
                                                    <td className="px-6 py-4 font-mono font-bold text-primary">{c.coupon_code}</td>
                                                    <td className="px-6 py-4">
                                                        <div className="font-semibold text-gray-800">{c.user?.name}</div>
                                                        <div className="text-xs text-gray-500">{c.user?.phone}</div>
                                                    </td>
                                                    <td className="px-6 py-4">₹{c.coupon_value}</td>
                                                    <td className="px-6 py-4 capitalize">
                                                        <span className={`px-2 py-1 rounded-full text-[10px] font-bold ${c.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                                                            {c.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 text-right">
                                                        {c.status === 'active' && (
                                                            <button onClick={() => router.post(route('admin.referrals.cancel-coupon', c.id))} className="text-red-600 hover:underline font-semibold">Cancel</button>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                                <div className="px-6 py-4"><Pagination links={coupons.links} /></div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
