import { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import SidebarLink from '@/Components/SidebarLink';
import Dropdown from '@/Components/Dropdown';
import DropdownLink from '@/Components/DropdownLink';

export default function AdminLayout({ children }) {
    const [showingSidebar, setShowingSidebar] = useState(false);
    const [showingUserDropdown, setShowingUserDropdown] = useState(false);
    const user = usePage().props.auth.user;
    const flash = usePage().props.flash || {};

    const handleLogout = (e) => {
        e.preventDefault();
        router.post(route('logout'));
    };

    const isAdmin = user.roles.some(r => r.name === 'admin');
    const isWarehouse = user.roles.some(r => r.name === 'warehouse');
    const isPartner = user.roles.some(r => r.name === 'channel_partner');
    const isPickupBoy = user.roles.some(r => r.name === 'pickup_boy');

    return (
        <div className="flex h-screen bg-bg-light font-roboto">
            {/* Sidebar Overlay for Mobile */}
            <div
                className={`fixed z-20 inset-0 bg-black opacity-50 transition-opacity lg:hidden ${
                    showingSidebar ? 'block' : 'hidden'
                }`}
                onClick={() => setShowingSidebar(false)}
            ></div>

            {/* Sidebar */}
            <div
                className={`fixed z-30 inset-y-0 left-0 w-64 transition duration-300 transform bg-gradient-to-b from-white to-gray-50 border-r border-gray-200 overflow-y-auto lg:translate-x-0 lg:static lg:inset-0 ${
                    showingSidebar ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'
                }`}
            >
                <div className="flex items-center justify-center mt-6 mb-8">
                    <a href="/" className="flex items-center group">
                        <div className="w-10 h-10 bg-gradient-to-br from-green-600 to-green-700 rounded-lg flex items-center justify-center group-hover:shadow-lg transition-all duration-200 transform group-hover:scale-110">
                            <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                               <path d="M12 2L2 22h20L12 2zm0 4.5l6.5 13h-13L12 6.5z"/>
                            </svg>
                        </div>
                        <span className="text-gray-800 text-xl font-bold ml-3 tracking-tight bg-gradient-to-r from-green-600 to-green-700 bg-clip-text text-transparent">Scrapify</span>
                    </a>
                </div>

                <nav className="mt-4 pb-10 flex flex-col gap-1">
                    <div className="px-6 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Main menu</div>
                    
                    <SidebarLink href={route('dashboard')} active={route().current('dashboard')}>
                         <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span className="mx-3">Dashboard</span>
                    </SidebarLink>

                    {isAdmin && (
                        <>
                            <SidebarLink href={route('admin.users.index')} active={route().current('admin.users.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span className="mx-3">Users</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.pickup-boys.index')} active={route().current('admin.pickup-boys.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span className="mx-3">Pickup Boys</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.waitlist.index')} active={route().current('admin.waitlist.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span className="mx-3">Waitlist</span>
                            </SidebarLink>
                        </>
                    )}

                    {(isAdmin || isWarehouse || isPartner || isPickupBoy) && (
                        <SidebarLink href={route('admin.pickups.index')} active={route().current('admin.pickups.*')}>
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span className="mx-3">Booking Requests</span>
                        </SidebarLink>
                    )}

                    {(isAdmin || isWarehouse) && (
                        <SidebarLink href={route('admin.payments.index')} active={route().current('admin.payments.*')}>
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="mx-3">Payments</span>
                        </SidebarLink>
                    )}

                    {isPartner && (
                        <>
                            <SidebarLink href={route('admin.partner-customers.index')} active={route().current('admin.partner-customers.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span className="mx-3">Customers</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.partner-pickups.create')} active={route().current('admin.partner-pickups.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span className="mx-3">Create Pickup</span>
                            </SidebarLink>
                        </>
                    )}

                    {isAdmin && (
                        <>
                            <div className="px-6 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Product</div>

                            <SidebarLink href={route('admin.categories.index')} active={route().current('admin.categories.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span className="mx-3">Categories</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.category-types.index')} active={route().current('admin.category-types.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span className="mx-3">Category Types</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.attributes.index')} active={route().current('admin.attributes.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                <span className="mx-3">Attributes</span>
                            </SidebarLink>
                        </>
                    )}

                    {(isAdmin || isWarehouse) && (
                        <SidebarLink href={route('admin.warehouses.index')} active={route().current('admin.warehouses.*')}>
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span className="mx-3">Warehouses</span>
                        </SidebarLink>
                    )}

                    {isAdmin && (
                        <div className="px-6 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Admin</div>
                    )}

                    {isAdmin && (
                        <>
                            <SidebarLink href={route('admin.partners.index')} active={route().current('admin.partners.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span className="mx-3">Partners</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.channel-partners.index')} active={route().current('admin.channel-partners.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span className="mx-3">Partner Requests</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.approval-requests.index')} active={route().current('admin.approval-requests.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                <span className="mx-3">Onboarding Approvals</span>
                            </SidebarLink>
                        </>
                    )}

                    {(isAdmin || isPartner) && (
                        <SidebarLink href={route('admin.settlements.index')} active={route().current('admin.settlements.*')}>
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span className="mx-3">Settlements</span>
                        </SidebarLink>
                    )}

                    {isAdmin && (
                        <>
                            <SidebarLink href={route('admin.withdrawals.index')} active={route().current('admin.withdrawals.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span className="mx-3">Withdrawals</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.referrals.index')} active={route().current('admin.referrals.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                                <span className="mx-3">Referrals & Rewards</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.kyc.index')} active={route().current('admin.kyc.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span className="mx-3">KYC Verification</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.locations.index')} active={route().current('admin.locations.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span className="mx-3">Locations</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.logs.index')} active={route().current('admin.logs.*')}>
                                 <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                   <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span className="mx-3">Activity Logs</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.app-settings.index')} active={route().current('admin.app-settings.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span className="mx-3">App Settings</span>
                            </SidebarLink>

                            <div className="px-6 py-2 mt-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Content</div>

                            <SidebarLink href={route('admin.pages.index')} active={route().current('admin.pages.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span className="mx-3">Static Pages</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.contacts.index')} active={route().current('admin.contacts.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span className="mx-3">Contact Queries</span>
                            </SidebarLink>

                            <SidebarLink href={route('admin.help-support.index')} active={route().current('admin.help-support.*')}>
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span className="mx-3">Help & Support</span>
                            </SidebarLink>
                        </>
                    )}
                </nav>
            </div>

            <div className="flex-1 flex flex-col overflow-hidden">
                <header className="flex justify-between items-center py-4 px-6 bg-white border-b border-gray-100 z-10 w-full relative">
                    <div className="flex items-center flex-1">
                        <button onClick={() => setShowingSidebar(true)} className="text-gray-500 focus:outline-none lg:hidden mr-4">
                            <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </button>
                        
                        {/* Search Bar */}
                        <div className="hidden md:flex relative w-full max-w-xl">
                            <span className="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg className="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none">
                                    <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path>
                                </svg>
                            </span>
                            <input type="text" className="w-full py-2 pl-10 pr-4 text-gray-700 bg-gray-100 border border-gray-200 rounded-lg focus:bg-white focus:border-green-500 focus:ring-2 focus:ring-green-200 focus:outline-none transition-all" placeholder="Search data, users, or reports" />
                        </div>
                    </div>

                    <div className="flex items-center pl-6 ml-auto gap-4">
                        {/* Notifications */}
                        <Link href={route('admin.logs.index')} className="flex items-center text-gray-600 hover:text-green-600 focus:outline-none relative transition-colors p-2 hover:bg-green-50 rounded-lg" title="View Activity Logs">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span className="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border border-white animate-pulse"></span>
                        </Link>

                        {/* Theme Toggle (Mock) */}
                        <button className="flex items-center text-gray-600 hover:text-green-600 focus:outline-none bg-gray-100 rounded-lg p-2 border border-gray-200 transition-all hover:border-green-300 hover:bg-green-50">
                             <div className="bg-white rounded-full p-1 shadow-sm">
                                <svg className="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                             </div>
                             <div className="p-1">
                                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                             </div>
                        </button>

                        <div className="w-px h-6 bg-gray-200 mx-1"></div>

                        {/* User Dropdown */}
                        <div className="relative">
                            <button 
                                onClick={() => setShowingUserDropdown(!showingUserDropdown)} 
                                className="flex items-center focus:outline-none cursor-pointer"
                            >
                                <img 
                                    className="w-9 h-9 object-cover rounded-full border border-gray-200 hover:shadow-sm transition-shadow" 
                                    src={user.profile_photo_url || `https://ui-avatars.com/api/?name=${user.name}&background=f4f7f6&color=4faa74`} 
                                    alt={user.name} 
                                />
                            </button>

                            {showingUserDropdown && (
                                <>
                                    {/* Click outside overlay */}
                                    <div className="fixed inset-0 z-40" onClick={() => setShowingUserDropdown(false)}></div>
                                    
                                    {/* Dropdown Menu */}
                                    <div className="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white border border-gray-100 z-50 animate-in fade-in-50 zoom-in-95 duration-100">
                                        <Link 
                                            href={route('profile.edit')} 
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left font-medium"
                                            onClick={() => setShowingUserDropdown(false)}
                                        >
                                            Profile
                                        </Link>
                                        <Link 
                                            href={route('logout')} 
                                            method="post" 
                                            as="button" 
                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left font-medium cursor-pointer"
                                            onClick={() => setShowingUserDropdown(false)}
                                        >
                                            Log Out
                                        </Link>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100">
                    <div className="w-full px-6 py-8 max-w-7xl mx-auto">
                         {flash.success && (
                            <div className="mb-4 bg-green-50 border border-green-200 text-green-700 px-5 py-4 rounded-xl relative shadow-sm animate-in slide-in-from-top" role="alert">
                                <div className="flex items-center gap-3">
                                    <svg className="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" /></svg>
                                    <span className="block sm:inline font-medium">{flash.success}</span>
                                </div>
                            </div>
                        )}
                         {flash.error && (
                             <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl relative shadow-sm animate-in slide-in-from-top" role="alert">
                                <div className="flex items-center gap-3">
                                    <svg className="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" /></svg>
                                    <span className="block sm:inline font-medium">{flash.error}</span>
                                </div>
                            </div>
                        )}
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
