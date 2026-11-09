import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, CalendarClock, CheckCircle2, Clock3, Gauge, Handshake, Users, WalletCards, Warehouse, Zap } from 'lucide-react';

const iconAssets = {
    customers: '/images/admin/3d/customers.png',
    pickups: '/images/admin/3d/pickups.png',
    warehouse: '/images/admin/3d/warehouse.png',
    settlement: '/images/admin/3d/settlement.png',
};

function MetricCard({ label, value, caption, icon, href }) {
    const card = (
        <div className="admin-dashboard-card group flex min-h-[154px] flex-col justify-between rounded-2xl border border-[#dde8e0] bg-white p-5 shadow-[0_8px_24px_rgba(31,92,57,0.05)] transition duration-200 hover:-translate-y-0.5 hover:border-[#bad5c2] hover:shadow-[0_12px_30px_rgba(31,92,57,0.1)]">
            <div className="flex items-start justify-between gap-3"><span className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#eaf3ed] ring-1 ring-[#d8e9dd]"><img src={icon} alt="" className="h-9 w-9 object-contain" /></span>{href && <span className="flex items-center gap-1 text-xs font-semibold text-[#94a39a] transition group-hover:text-primary">View <ArrowUpRight size={14} /></span>}</div>
            <div><p className="mt-5 text-[30px] font-bold leading-none tracking-tight text-[#15241b]">{value ?? 0}</p><p className="mt-2 text-sm font-bold text-primary">{label}</p><p className="mt-1 text-xs text-[#94a39a]">{caption}</p></div>
        </div>
    );
    return href ? <Link href={href} className="block">{card}</Link> : card;
}

function HealthItem({ icon: Icon, label, value, caption, tone = 'green' }) {
    const tones = { green: 'bg-[#eaf6ee] text-primary', blue: 'bg-[#edf3f7] text-[#46667b]', amber: 'bg-[#fff5e8] text-[#bc6a1b]' };
    return <div className="flex items-center gap-3 rounded-xl border border-[#e5ece7] bg-[#fbfdfb] px-3 py-3"><span className={`flex h-9 w-9 items-center justify-center rounded-lg ${tones[tone]}`}><Icon size={17} /></span><div className="min-w-0 flex-1"><p className="text-xs font-semibold text-[#607168]">{label}</p><p className="mt-0.5 text-xl font-bold leading-none text-[#1b2a21]">{value ?? 0}</p></div><span className="hidden text-[10px] text-[#9aa79f] sm:block">{caption}</span></div>;
}

function statusClass(status) {
    if (status === 'completed') return 'bg-[#e7f5eb] text-[#2f7d4f]';
    if (status === 'cancelled') return 'bg-[#fff0ef] text-[#bd4f48]';
    if (status === 'assigned' || status === 'accepted') return 'bg-[#edf3f7] text-[#46667b]';
    return 'bg-[#f1f4f2] text-[#68786f]';
}

export default function Dashboard({ stats = {}, selectedPeriod, isPickupBoy }) {
    const recentPickups = stats.recent_pickups || [];
    const periodLabel = selectedPeriod === 'today' ? 'Today' : selectedPeriod === 'overall' ? 'Overall' : 'This month';
    const metrics = [
        { label: 'Total Customers', value: stats.total_customers, caption: 'Partner/customer records', icon: iconAssets.customers, href: route('admin.users.index') },
        { label: 'Total Pickups', value: stats.total_pickups, caption: 'All pickup requests', icon: iconAssets.pickups, href: route('admin.pickups.index') },
        { label: 'Assigned Pickups', value: stats.assigned_pickups, caption: 'Assigned to drivers', icon: iconAssets.pickups, href: route('admin.pickups.index', { status: 'assigned' }) },
        { label: 'Delivered to Warehouse', value: stats.delivered_to_warehouse, caption: 'Warehouse handovers', icon: iconAssets.warehouse, href: route('admin.pickups.index', { status: 'delivered_to_warehouse' }) },
        { label: 'Pending Settlement', value: stats.pending_settlement, caption: 'Awaiting payout', icon: iconAssets.settlement, href: route('admin.settlements.index', { status: 'pending' }) },
        { label: 'Paid Settlement', value: stats.paid_settlement, caption: 'Paid payouts', icon: iconAssets.settlement, href: route('admin.settlements.index', { status: 'paid' }) },
    ];
    const attentionItems = [
        { value: stats.pending_requests, label: 'Pending pickup requests', caption: 'Open queue', href: route('admin.pickups.index', { status: 'pending' }), icon: CalendarClock, tone: 'blue' },
        { value: stats.partner_requests_count, label: 'Partner requests', caption: 'Open queue', href: route('admin.channel-partners.index', { status: 'pending' }), icon: Handshake, tone: 'green' },
        { value: stats.pending_approvals_count, label: 'Onboarding approvals', caption: 'Open queue', href: route('admin.approval-requests.index', { status: 'pending' }), icon: CheckCircle2, tone: 'amber' },
    ];

    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between"><div><p className="text-[11px] font-bold uppercase tracking-[0.18em] text-primary">Overview</p><h1 className="mt-2 text-[34px] font-bold leading-none tracking-[-0.03em] text-[#15241b]">Dashboard</h1><p className="mt-2 text-sm text-[#718078]">A quick view of your Scrapify operations.</p></div><Link href={route('admin.pickups.index')} className="inline-flex w-fit items-center gap-2 rounded-xl border border-[#cfe0d4] bg-white px-4 py-3 text-sm font-bold text-primary shadow-sm transition hover:border-primary hover:bg-green-50">View booking requests <ArrowUpRight size={17} /></Link></div>
                {isPickupBoy && <div className="flex flex-col gap-3 rounded-2xl border border-[#dde8e0] bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-sm font-bold text-[#25362b]">Pickup performance</p><p className="mt-1 text-xs text-[#88968e]">Showing {periodLabel} data</p></div><div className="flex flex-wrap gap-2">{[['today', 'Today'], ['month', 'This month'], ['overall', 'Overall']].map(([value, label]) => <Link key={value} href={route('dashboard', { period: value })} className={`rounded-lg border px-3 py-2 text-xs font-bold ${selectedPeriod === value ? 'border-primary bg-primary text-white' : 'border-[#dce7df] bg-white text-[#66756c] hover:border-primary hover:text-primary'}`}>{label}</Link>)}</div></div>}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">{metrics.map((metric) => <MetricCard key={metric.label} {...metric} />)}</div>
                <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <section className="rounded-2xl border border-[#dde8e0] bg-white p-5 shadow-[0_8px_24px_rgba(31,92,57,0.05)]"><div className="mb-4 flex items-start justify-between"><div><h2 className="text-base font-bold text-[#1d2b24]">Operations health</h2><p className="mt-1 text-xs text-[#8b9991]">Live operational counters</p></div><span className="rounded-lg bg-[#eaf3ed] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.1em] text-primary">Live</span></div><div className="grid grid-cols-1 gap-3 sm:grid-cols-2"><HealthItem icon={Users} label="Total users" value={stats.users_count} caption="registered" /><HealthItem icon={Clock3} label="Pending pickups" value={stats.pending_requests} caption="awaiting" tone="blue" /><HealthItem icon={Zap} label="Today's requests" value={stats.today_pickups_count} caption="since midnight" /><HealthItem icon={Gauge} label="Drivers online" value={stats.online_drivers_count} caption="ready" tone="blue" /><HealthItem icon={Warehouse} label="Capacity full" value={stats.capacity_full_drivers_count} caption="at limit" tone="amber" /><HealthItem icon={WalletCards} label="Paid settlements" value={stats.paid_settlement} caption="completed" /></div></section>
                    <section className="rounded-2xl border border-[#dde8e0] bg-white p-5 shadow-[0_8px_24px_rgba(31,92,57,0.05)]"><div className="mb-4 flex items-start justify-between"><div><h2 className="text-base font-bold text-[#1d2b24]">Needs attention</h2><p className="mt-1 text-xs text-[#8b9991]">Queues that may need an action</p></div><span className="rounded-lg bg-[#fff5e8] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.1em] text-[#b66b24]">{(stats.partner_requests_count || 0) + (stats.pending_approvals_count || 0) + (stats.pending_requests || 0)} open</span></div><div className="space-y-3">{attentionItems.map(({ value, label, caption, href, icon: Icon, tone }) => { const tones = { blue: 'border-l-[#6b91a8] bg-[#f7fafc] text-[#46667b]', green: 'border-l-primary bg-[#f7fbf8] text-primary', amber: 'border-l-[#d89a57] bg-[#fffaf3] text-[#b66b24]' }; return <Link key={label} href={href} className={`group flex items-center gap-3 rounded-xl border border-[#e5ece7] border-l-4 px-3.5 py-3 transition hover:border-[#b9d6c2] hover:shadow-sm ${tones[tone]}`}><span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/80"><Icon size={17} /></span><span className="min-w-0 flex-1"><span className="block text-sm font-semibold text-[#384b3e]">{label}</span><span className="mt-1 block text-xs text-[#94a39a]">{caption}</span></span><span className="flex items-center gap-2"><strong className="min-w-[38px] rounded-lg bg-white/80 px-2 py-1 text-right text-lg text-[#1d2b24]">{value || 0}</strong><ArrowUpRight size={16} className="text-[#9aaa9f] transition group-hover:text-primary" /></span></Link>; })}</div></section>
                </div>
                <section className="overflow-hidden rounded-2xl border border-[#dde8e0] bg-white shadow-[0_8px_24px_rgba(31,92,57,0.05)]"><div className="flex flex-col gap-3 border-b border-[#e7eee9] px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 className="text-base font-bold text-[#1d2b24]">Recent booking requests</h2><p className="mt-1 text-xs text-[#8b9991]">Latest activity across your operations</p></div><Link href={route('admin.pickups.index')} className="inline-flex items-center gap-1 text-sm font-bold text-primary hover:text-primary-hover">View all requests <ArrowUpRight size={15} /></Link></div><div className="overflow-x-auto"><table className="min-w-full text-left"><thead className="bg-[#f3f8f4] text-[10px] uppercase tracking-[0.13em] text-[#718078]"><tr><th className="px-5 py-3 font-bold">Request ID</th><th className="px-5 py-3 font-bold">User</th><th className="px-5 py-3 font-bold">Scheduled date</th><th className="px-5 py-3 font-bold">Type</th><th className="px-5 py-3 font-bold">Status</th><th className="px-5 py-3 text-right font-bold">Action</th></tr></thead><tbody className="divide-y divide-[#edf2ee] text-sm">{recentPickups.length === 0 ? <tr><td colSpan="6" className="px-5 py-14 text-center text-sm text-[#7d8b83]">No recent pickup requests found.</td></tr> : recentPickups.map((pickup) => <tr key={pickup.id} className="transition hover:bg-[#f8fbf9]"><td className="whitespace-nowrap px-5 py-3.5 font-bold text-[#25362b]">#{pickup.id}</td><td className="px-5 py-3.5 font-medium text-[#4d5e53]">{pickup.customer?.name || 'N/A'}</td><td className="whitespace-nowrap px-5 py-3.5 text-[#76857c]">{pickup.scheduled_at ? new Date(pickup.scheduled_at).toLocaleDateString() : 'TBD'}</td><td className="px-5 py-3.5 text-xs font-bold uppercase text-[#76857c]">{(pickup.request_type || 'unknown').replace('_', ' ')}</td><td className="px-5 py-3.5"><span className={`inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold capitalize ${statusClass(pickup.status)}`}>{(pickup.status || 'unknown').replace(/_/g, ' ')}</span></td><td className="px-5 py-3.5 text-right"><Link href={route('admin.pickups.show', pickup.id)} className="font-bold text-primary hover:text-primary-hover">View</Link></td></tr>)}</tbody></table></div></section>
            </div>
        </AdminLayout>
    );
}
