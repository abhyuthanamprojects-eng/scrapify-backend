import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    Activity, Banknote, Bell, CalendarDays, ChevronDown, ClipboardCheck,
    ClipboardList, FileCheck2, FileText, Gift, Grid2X2, Handshake,
    LayoutDashboard, LifeBuoy, ListChecks, LogOut, Mail, MapPinned, Menu,
    Moon, PackageCheck, Search, Settings2, ShieldCheck, Sun, Tags, UserRound,
    Users, WalletCards, Warehouse, X,
} from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';

function NavigationItem({ href, label, icon: Icon, active, onClick }) {
    return (
        <Link href={href} onClick={onClick} className={`group flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13px] font-medium transition-all ${active ? 'bg-primary text-white shadow-[0_8px_18px_rgba(47,125,79,0.26)]' : 'text-[#b8c9bf] hover:bg-white/10 hover:text-white'}`}>
            <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${active ? 'bg-white/15' : 'bg-white/[0.04] group-hover:bg-white/10'}`}><Icon size={16} strokeWidth={active ? 2.4 : 2} /></span>
            <span className="truncate">{label}</span>
        </Link>
    );
}

function NavigationSection({ label, items, onNavigate }) {
    if (!items.length) return null;
    return <div className="mb-5"><p className="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-[#789184]">{label}</p><div className="space-y-1">{items.map((item) => <NavigationItem key={item.label} {...item} onClick={onNavigate} />)}</div></div>;
}

export default function AdminLayout({ children }) {
    const { auth, flash = {} } = usePage().props;
    const user = auth.user;
    const [showingSidebar, setShowingSidebar] = useState(false);
    const [showingUserMenu, setShowingUserMenu] = useState(false);
    const hasRole = (role) => user.roles?.some((item) => item.name === role);
    const isAdmin = hasRole('admin');
    const isWarehouse = hasRole('warehouse');
    const isPartner = hasRole('channel_partner');
    const isPickupBoy = hasRole('pickup_boy');
    const isPaymentAdmin = hasRole('payment_admin');
    const initials = user.name?.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase() || 'AU';
    const closeSidebar = () => setShowingSidebar(false);

    const navigation = [
        { label: 'Main menu', items: [
            { label: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, active: route().current('dashboard') },
            ...(isAdmin ? [
                { label: 'Users', href: route('admin.users.index'), icon: Users, active: route().current('admin.users.*') },
                { label: 'Pickup Boys', href: route('admin.pickup-boys.index'), icon: UserRound, active: route().current('admin.pickup-boys.*') },
                { label: 'Waitlist', href: route('admin.waitlist.index'), icon: ClipboardList, active: route().current('admin.waitlist.*') },
            ] : []),
            ...((isAdmin || isWarehouse || isPartner || isPickupBoy || isPaymentAdmin) ? [{ label: 'Booking Requests', href: route('admin.pickups.index'), icon: CalendarDays, active: route().current('admin.pickups.*') }] : []),
            ...((isAdmin || isWarehouse || isPaymentAdmin) ? [{ label: 'Payments', href: route('admin.payments.index'), icon: WalletCards, active: route().current('admin.payments.*') }] : []),
            ...(isPartner ? [
                { label: 'Customers', href: route('admin.partner-customers.index'), icon: Users, active: route().current('admin.partner-customers.*') },
                { label: 'Create Pickup', href: route('admin.partner-pickups.create'), icon: PackageCheck, active: route().current('admin.partner-pickups.*') },
            ] : []),
        ]},
        { label: 'Product', items: isAdmin ? [
            { label: 'Categories', href: route('admin.categories.index'), icon: Tags, active: route().current('admin.categories.*') },
            { label: 'Category Types', href: route('admin.category-types.index'), icon: Grid2X2, active: route().current('admin.category-types.*') },
            { label: 'Attributes', href: route('admin.attributes.index'), icon: ListChecks, active: route().current('admin.attributes.*') },
            { label: 'Warehouses', href: route('admin.warehouses.index'), icon: Warehouse, active: route().current('admin.warehouses.*') },
        ] : []},
        { label: 'Administration', items: isAdmin ? [
            { label: 'Partners', href: route('admin.partners.index'), icon: Handshake, active: route().current('admin.partners.*') },
            { label: 'Partner Requests', href: route('admin.channel-partners.index'), icon: ShieldCheck, active: route().current('admin.channel-partners.*') },
            { label: 'Onboarding Approvals', href: route('admin.approval-requests.index'), icon: ClipboardCheck, active: route().current('admin.approval-requests.*') },
            { label: 'Settlements', href: route('admin.settlements.index'), icon: Banknote, active: route().current('admin.settlements.*') },
            { label: 'Withdrawals', href: route('admin.withdrawals.index'), icon: WalletCards, active: route().current('admin.withdrawals.*') },
            { label: 'Referrals & Rewards', href: route('admin.referrals.index'), icon: Gift, active: route().current('admin.referrals.*') },
            { label: 'KYC Verification', href: route('admin.kyc.index'), icon: FileCheck2, active: route().current('admin.kyc.*') },
            { label: 'Locations', href: route('admin.locations.index'), icon: MapPinned, active: route().current('admin.locations.*') },
            { label: 'Activity Logs', href: route('admin.logs.index'), icon: Activity, active: route().current('admin.logs.*') },
            { label: 'App Settings', href: route('admin.app-settings.index'), icon: Settings2, active: route().current('admin.app-settings.*') },
        ] : []},
        { label: 'Content', items: isAdmin ? [
            { label: 'Static Pages', href: route('admin.pages.index'), icon: FileText, active: route().current('admin.pages.*') },
            { label: 'Contact Queries', href: route('admin.contacts.index'), icon: Mail, active: route().current('admin.contacts.*') },
            { label: 'Help & Support', href: route('admin.help-support.index'), icon: LifeBuoy, active: route().current('admin.help-support.*') },
        ] : []},
    ];

    return (
        <div className="admin-panel flex min-h-screen bg-white font-roboto text-[#1d2b24]">
            <div className={`fixed inset-0 z-40 bg-[#07150e]/60 backdrop-blur-sm transition-opacity lg:hidden ${showingSidebar ? 'opacity-100' : 'pointer-events-none opacity-0'}`} onClick={closeSidebar} />
            <aside className={`fixed inset-y-0 left-0 z-50 flex w-[260px] -translate-x-full flex-col bg-[#11271e] text-white transition-transform duration-300 lg:static lg:translate-x-0 ${showingSidebar ? 'translate-x-0' : ''}`}>
                <div className="flex h-[88px] items-center justify-between border-b border-white/10 px-5">
                    <Link href={route('dashboard')} className="flex items-center gap-3" onClick={closeSidebar}>
                        <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-white shadow-[0_8px_20px_rgba(0,0,0,0.14)]"><ApplicationLogo className="h-9 w-auto object-contain" /></span>
                        <span><span className="block text-sm font-bold tracking-tight">Scrapify</span><span className="mt-0.5 block text-[10px] font-medium uppercase tracking-[0.16em] text-[#8db49a]">Admin portal</span></span>
                    </Link>
                    <button type="button" onClick={closeSidebar} className="rounded-lg p-2 text-[#9bb5a5] hover:bg-white/10 lg:hidden"><X size={18} /></button>
                </div>
                <nav className="flex-1 overflow-y-auto px-3 py-5 [scrollbar-width:thin]">{navigation.map((section) => <NavigationSection key={section.label} {...section} onNavigate={closeSidebar} />)}</nav>
                <div className="border-t border-white/10 p-3"><Link href={route('logout')} method="post" as="button" className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-[13px] font-semibold text-[#f2aaa2] transition hover:bg-white/10 hover:text-white"><span className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#f2aaa2]/10"><LogOut size={16} /></span>Log out</Link></div>
            </aside>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-30 flex h-[78px] items-center gap-4 border-b border-[#e5ece7] bg-white/95 px-4 shadow-[0_1px_0_rgba(31,92,57,0.03)] backdrop-blur sm:px-6 lg:px-8">
                    <button type="button" onClick={() => setShowingSidebar(true)} className="rounded-xl border border-[#e1eae4] p-2.5 text-[#617268] lg:hidden"><Menu size={19} /></button>
                    <div className="relative flex min-w-0 flex-1"><Search className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#93a198]" size={18} /><input type="search" placeholder="Search data, users, or reports" className="h-11 w-full max-w-[620px] rounded-xl border border-[#e1eae4] bg-[#f7faf8] pl-11 pr-4 text-sm text-[#26372d] outline-none transition placeholder:text-[#9aa9a0] focus:border-primary focus:bg-white focus:ring-4 focus:ring-green-100" /></div>
                    <div className="flex items-center gap-2 sm:gap-3">
                        <Link href={route('admin.logs.index')} className="relative rounded-xl p-2.5 text-[#62746a] transition hover:bg-green-50 hover:text-primary" title="Activity logs"><Bell size={19} /><span className="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-[#e26d65] ring-2 ring-white" /></Link>
                        <button type="button" className="hidden items-center gap-1 rounded-xl border border-[#e1eae4] bg-[#f7faf8] p-2.5 text-[#62746a] sm:flex" title="Theme options"><Sun size={16} /><Moon size={15} className="text-[#a4b0a9]" /></button>
                        <div className="relative border-l border-[#e5ece7] pl-2 sm:pl-3"><button type="button" onClick={() => setShowingUserMenu((value) => !value)} className="flex items-center gap-2 rounded-xl px-1.5 py-1.5 transition hover:bg-green-50"><span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#e6f1ea] text-xs font-bold text-primary">{initials}</span><span className="hidden text-left sm:block"><span className="block max-w-[120px] truncate text-xs font-semibold text-[#26372d]">{user.name}</span><span className="block text-[10px] text-[#87978d]">{isAdmin ? 'Super Admin' : 'Admin user'}</span></span><ChevronDown size={15} className="hidden text-[#8b9a91] sm:block" /></button>
                            {showingUserMenu && <><button type="button" aria-label="Close user menu" className="fixed inset-0 z-40 h-full w-full cursor-default" onClick={() => setShowingUserMenu(false)} /><div className="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-xl border border-[#e1eae4] bg-white p-1.5 shadow-xl"><Link href={route('profile.edit')} onClick={() => setShowingUserMenu(false)} className="block rounded-lg px-3 py-2 text-sm font-medium text-[#425449] hover:bg-green-50 hover:text-primary">Profile</Link><Link href={route('logout')} method="post" as="button" onClick={() => setShowingUserMenu(false)} className="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-[#425449] hover:bg-red-50 hover:text-red-700">Log out</Link></div></>}
                        </div>
                    </div>
                </header>
                <main className="min-w-0 flex-1 overflow-x-hidden overflow-y-auto"><div className="mx-auto w-full max-w-[1540px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">{flash.success && <div className="mb-5 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="alert"><span className="h-2 w-2 rounded-full bg-primary" />{flash.success}</div>}{flash.error && <div className="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert"><span className="h-2 w-2 rounded-full bg-red-500" />{flash.error}</div>}{children}</div></main>
            </div>
        </div>
    );
}
