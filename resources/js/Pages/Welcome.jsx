import { Link, Head } from '@inertiajs/react';
import React, { useState } from 'react';

const googlePlayUrl = 'https://play.google.com/store/apps/details?id=com.abhyuthanam.scrapify&pcampaignid=web_share';
const appStoreUrl = 'https://apps.apple.com/us/app/scrapify/id6775160804';

const mockups = {
    hero: '/images/scrapify_mockup_1776327057800.png',
    workerLeft: '/images/scrapify_worker_left_1776327029560.png',
    workerRight: '/images/scrapify_worker_right_1776327044313.png',
};

const NavLink = ({ href, children }) => (
    <a href={href} className="text-gray-600 hover:text-scrapify-green font-medium transition-colors">
        {children}
    </a>
);

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    const [openFaq, setOpenFaq] = useState(null);

    const toggleFaq = (index) => {
        setOpenFaq(openFaq === index ? null : index);
    };

    const faqs = [
        { q: "What is Scrapify?", a: "Scrapify is an on-demand scrap collection platform that allows you to sell your recyclable waste from your doorstep safely and conveniently." },
        { q: "How do I schedule a pickup?", a: "Simply download the Scrapify app, choose your scrap categories, enter your details, and select a convenient time slot." },
        { q: "How much does the pickup cost?", a: "The pickup service is completely free! In fact, we pay you for the scrap materials based on standard market rates." },
        { q: "What kind of scrap do you accept?", a: "We accept e-waste, metals, plastics, paper, cardboard, and other recyclable materials." },
        { q: "Where does the scrap go?", a: "We partner with certified recycling facilities to ensure your scrap is processed responsibly and sustainably." },
    ];

    return (
        <>
            <Head title="Welcome | Scrapify" />
            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
                .font-outfit { font-family: 'Outfit', sans-serif; }
            `}</style>

            <div className="min-h-screen bg-white font-outfit text-gray-800">
                {/* Navbar */}
                <nav className="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-sm">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 h-[4rem] md:h-[5rem] flex items-center justify-between">
                        <img src="/images/logo.png" alt="Scrapify Desktop Logo" className="h-full w-auto object-contain" />
                        <div className="hidden md:flex items-center gap-8 text-sm uppercase tracking-wide">
                            <NavLink href="#about">About us</NavLink>
                            <NavLink href="#services">Services</NavLink>
                            <NavLink href="#how-it-works">How it works</NavLink>
                            <NavLink href="#faqs">FAQs</NavLink>
                        </div>
                        <div>
                            {auth && auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="bg-scrapify-green text-white px-6 py-2.5 rounded-full font-semibold hover:bg-scrapify-blue transition-all shadow-md hover:shadow-lg"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <Link
                                    href={route('login')}
                                    className="bg-scrapify-green text-white px-6 py-2.5 rounded-full font-semibold hover:bg-scrapify-blue transition-all shadow-md hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-scrapify-green/30"
                                >
                                    Login / App
                                </Link>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <header className="relative pt-20 pb-32 overflow-hidden">
                    <div className="absolute inset-0 bg-gradient-to-b from-white via-green-50/50 to-[#e2f0e3] -z-10"></div>
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 text-center relative z-10">
                        <h1 className="text-5xl md:text-6xl lg:text-7xl font-extrabold text-scrapify-blue mb-6 tracking-tight leading-tight">
                            Turn your scrap <br className="hidden md:block" /> into cash in minutes!
                        </h1>
                        <p className="text-lg md:text-xl text-gray-600 mb-10">
                            Download the app now and start earning.
                        </p>
                        <div className="flex justify-center gap-4 mb-20 relative z-20">
                            <a href={appStoreUrl} target="_blank" rel="noopener noreferrer" className="bg-black text-white px-6 py-3 rounded-lg flex items-center gap-2 hover:scale-105 transition-transform">
                                <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.054 11.666c-.015-2.616 2.137-3.864 2.238-3.929-1.218-1.782-3.111-2.022-3.79-2.046-1.611-.161-3.141.95-3.966.95-.826 0-2.083-.934-3.415-.909-1.722.025-3.321.999-4.218 2.55-1.821 3.155-.466 7.828 1.309 10.388.868 1.252 1.895 2.651 3.255 2.602 1.309-.049 1.831-.842 3.395-.842 1.554 0 2.035.842 3.42.817 1.408-.025 2.292-1.252 3.141-2.499 1.002-1.454 1.41-2.868 1.428-2.941-.031-.013-2.75-.105-2.762-2.743zm-2.036-6.071c.749-.912 1.253-2.179 1.116-3.447-1.092.045-2.408.729-3.176 1.636-.688.802-1.294 2.091-1.135 3.336 1.222.095 2.445-.615 3.195-1.525z" /></svg>
                                <div className="text-left">
                                    <div className="text-[10px] leading-tight">Download on the</div>
                                    <div className="text-sm font-semibold leading-tight">App Store</div>
                                </div>
                            </a>
                            <a href={googlePlayUrl} target="_blank" rel="noopener noreferrer" className="bg-black text-white px-6 py-3 rounded-lg flex items-center gap-2 hover:scale-105 transition-transform">
                                <svg className="w-6 h-6" viewBox="0 0 512 512" fill="currentColor"><path d="M325.3 234.3L104.6 13l280.8 161.2-60.1 60.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58.9-34.1-65.7 64.5 65.7 64.5 60.1-34.1c18-14.3 18-46.5-1.2-60.8zM104.6 499l280.8-161.2-60.1-60.1L104.6 499z" /></svg>
                                <div className="text-left">
                                    <div className="text-[10px] leading-tight">GET IT ON</div>
                                    <div className="text-sm font-semibold leading-tight">Google Play</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </header>

                {/* Hero Images Showcase */}
                <div className="relative -mt-32 pb-24 z-20 flex justify-center items-end hidden md:flex">
                    {/* Fake character placeholders - In production, replace with actual clean cutouts */}
                    <img src={mockups.workerLeft} alt="Scrapify Worker Left" className="h-auto max-h-[400px] lg:max-h-[500px] object-contain drop-shadow-2xl z-10 -mr-16 lg:-mr-24 rounded-3xl" />
                    <img src={mockups.hero} alt="Scrapify App" className="w-[80%] max-w-[450px] h-auto object-contain z-30 drop-shadow-[0_30px_60px_rgba(0,0,0,0.2)] rounded-[3rem] border-[8px] border-white bg-white" />
                    <img src={mockups.workerRight} alt="Scrapify Worker Right" className="h-auto max-h-[400px] lg:max-h-[500px] object-contain drop-shadow-2xl z-10 -ml-16 lg:-ml-24 rounded-3xl" />
                </div>
                {/* Mobile version for Hero Images */}
                <div className="md:hidden flex justify-center -mt-16 pb-16 z-20 px-4">
                    <img src={mockups.hero} alt="Scrapify App" className="w-full max-w-sm h-auto object-contain z-30 drop-shadow-[0_20px_40px_rgba(0,0,0,0.15)] rounded-3xl border-8 border-white bg-white" />
                </div>

                {/* Stats Section */}
                <section className="py-20 bg-white" id="about">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 text-center">
                        <h2 className="text-3xl font-bold text-scrapify-blue mb-4">On-demand professional scrap collection</h2>
                        <p className="text-gray-500 mb-12">The fastest growing network of scrap collection professionals. Our teams are always on time.</p>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {[
                                { number: "400,000+", label: "Homes Cleared" },
                                { number: "250,000+", label: "KGs Recycled" },
                                { number: "1,500+", label: "Pin codes covered" }
                            ].map((stat, i) => (
                                <div key={i} className="bg-gray-50 border border-gray-100 rounded-2xl p-8 hover:shadow-lg transition-shadow duration-300">
                                    <div className="text-4xl font-extrabold text-scrapify-blue mb-2">{stat.number}</div>
                                    <div className="text-sm font-semibold tracking-wide text-gray-500 uppercase">{stat.label}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Services Section */}
                <section className="py-24 bg-gray-50" id="services">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 text-center">
                        <div className="flex justify-center mb-4">
                            <span className="bg-green-100 text-scrapify-green text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Our Services</span>
                        </div>
                        <h2 className="text-3xl md:text-4xl font-bold text-scrapify-blue mb-4">Book trusted scrap collection</h2>
                        <p className="text-gray-500 mb-16 max-w-2xl mx-auto">From e-waste to daily recyclables, Scrapify has you covered!</p>

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8">
                            {[
                                { name: "E-Waste", emoji: "💻", color: "bg-blue-100 text-blue-600" },
                                { name: "Metals", emoji: "⚙️", color: "bg-orange-100 text-orange-600" },
                                { name: "Plastics", emoji: "♻️", color: "bg-teal-100 text-teal-600" },
                                { name: "Paper Cardboard", emoji: "📦", color: "bg-yellow-100 text-yellow-600" }
                            ].map((service, i) => (
                                <div key={i} className="group flex flex-col items-center bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 cursor-pointer hover:-translate-y-2">
                                    <div className={`w-20 h-20 rounded-2xl ${service.color} flex items-center justify-center text-4xl mb-6 group-hover:scale-110 transition-transform`}>
                                        {service.emoji}
                                    </div>
                                    <h3 className="text-lg font-bold text-scrapify-blue">{service.name}</h3>
                                </div>
                            ))}
                        </div>
                        <div className="mt-12 text-gray-400 italic text-xl font-light">and many more...</div>
                    </div>
                </section>

                {/* How it works Section */}
                <section className="py-24 bg-white overflow-hidden relative" id="how-it-works">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 text-center relative z-10">
                        <div className="flex justify-center mb-4">
                            <span className="bg-green-100 text-scrapify-green text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">How IT Works</span>
                        </div>
                        <h2 className="text-3xl md:text-4xl font-bold text-scrapify-blue mb-4">Simple steps to a cleaner environment</h2>
                        <p className="text-gray-500 mb-16">Follow these simple steps to get lightning-fast scrap collection</p>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {[
                                { step: "STEP 01", title: "Pick from a range of scrap categories", desc: "Select the type of materials you want to sell." },
                                { step: "STEP 02", title: "Add details into your cart", desc: "Provide rough estimates to help us serve you better." },
                                { step: "STEP 03", title: "Schedule, Handover & Get Paid!", desc: "Choose a time slot, handover the scrap, and instantly receive cash." }
                            ].map((item, i) => (
                                <div key={i} className="bg-scrapify-green rounded-[2.5rem] p-8 text-white text-center transform transition duration-500 hover:scale-105 shadow-2xl relative overflow-hidden group">
                                    <div className="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                                        <svg className="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" /></svg>
                                    </div>
                                    <div className="bg-white/20 inline-block px-4 py-1 rounded-full text-xs font-bold tracking-widest mb-6 backdrop-blur-sm">{item.step}</div>
                                    <h3 className="text-2xl font-bold mb-4">{item.title}</h3>
                                    <p className="opacity-90">{item.desc}</p>
                                    <div className="mt-8 bg-black/10 rounded-2xl h-48 flex items-center justify-center border border-white/20 shadow-inner">
                                        <span className="text-white/50 text-sm">App UI Example {i + 1}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Reviews Section */}
                <section className="py-24 bg-gray-50 border-t border-b border-gray-100">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl font-bold text-scrapify-blue mb-4">User reviews and feedback</h2>
                            <p className="text-gray-500">See how Scrapify has transformed user's lives through their own words.</p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            {[
                                { name: "Neha S.", title: "Sector 14", content: "The service was simple and direct. Cleared out all my old appliances without any hassle. Great overall experience." },
                                { name: "Prashant K.", title: "Gomti Nagar", content: "Great work. The pickup guys were polite and weighed everything correctly right in front of me. Highly recommended!" },
                                { name: "Ritika M.", title: "Phase 2", content: "Seamless experience! They arrived right on time and paid the exact amount promised. Definite 5 stars." },
                                { name: "Aditya V.", title: "Andheri West", content: "Finally an organized platform for scrap. Very professional process from booking to payment." }
                            ].map((review, i) => (
                                <div key={i} className="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 relative">
                                    <div className="absolute top-6 right-6 text-gray-200 text-4xl font-serif">"</div>
                                    <div className="flex items-center gap-4 mb-6">
                                        <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-scrapify-green font-bold text-xl">
                                            {review.name.charAt(0)}
                                        </div>
                                        <div>
                                            <h4 className="font-bold text-scrapify-blue text-sm">{review.name}</h4>
                                            <p className="text-xs text-gray-500">{review.title}</p>
                                        </div>
                                    </div>
                                    <p className="text-gray-600 text-sm leading-relaxed">{review.content}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* FAQs Section */}
                <section className="py-24 bg-white" id="faqs">
                    <div className="max-w-3xl mx-auto px-6 lg:px-8">
                        <div className="text-center mb-12">
                            <div className="flex justify-center mb-4">
                                <span className="bg-gray-100 text-gray-600 border border-gray-200 text-xs font-bold px-4 py-1 rounded-full uppercase tracking-wider flex items-center gap-2">
                                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    FAQs
                                </span>
                            </div>
                            <h2 className="text-3xl font-bold text-scrapify-blue">Frequently Asked Questions</h2>
                        </div>

                        <div className="space-y-4">
                            {faqs.map((faq, i) => (
                                <div key={i} className="bg-gray-50 rounded-2xl overflow-hidden transition-all duration-300">
                                    <button
                                        className="w-full text-left px-6 py-5 font-semibold text-scrapify-blue flex justify-between items-center focus:outline-none"
                                        onClick={() => toggleFaq(i)}
                                    >
                                        {faq.q}
                                        <svg className={`w-5 h-5 text-gray-400 transform transition-transform duration-300 ${openFaq === i ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                    <div className={`px-6 text-gray-600 text-sm overflow-hidden transition-all duration-300 ease-in-out ${openFaq === i ? 'max-h-40 pb-5 opacity-100' : 'max-h-0 opacity-0'}`}>
                                        {faq.a}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Main CTA */}
                <section className="py-24 bg-white border-t border-gray-100">
                    <div className="max-w-4xl mx-auto px-6 text-center">
                        <div className="mb-8 flex justify-center">
                            <img src={mockups.hero} alt="Scrapify App mockups" className="w-80 h-auto rounded-[2rem] drop-shadow-2xl border-4 border-gray-100" />
                        </div>
                        <h2 className="text-4xl md:text-5xl font-extrabold text-scrapify-blue mb-4 tracking-tight">
                            Get expert scrap collection in minutes. <br /> Download Scrapify!
                        </h2>
                        <p className="text-gray-500 mb-10 text-lg">Thousands already trust us for hassle-free scrap selling.</p>

                        <div className="flex justify-center gap-4 mb-20">
                            <a href={appStoreUrl} target="_blank" rel="noopener noreferrer" className="bg-black text-white px-8 py-3.5 rounded-xl flex items-center gap-3 hover:scale-105 transition-transform">
                                <svg className="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M17.054 11.666c-.015-2.616 2.137-3.864 2.238-3.929-1.218-1.782-3.111-2.022-3.79-2.046-1.611-.161-3.141.95-3.966.95-.826 0-2.083-.934-3.415-.909-1.722.025-3.321.999-4.218 2.55-1.821 3.155-.466 7.828 1.309 10.388.868 1.252 1.895 2.651 3.255 2.602 1.309-.049 1.831-.842 3.395-.842 1.554 0 2.035.842 3.42.817 1.408-.025 2.292-1.252 3.141-2.499 1.002-1.454 1.41-2.868 1.428-2.941-.031-.013-2.75-.105-2.762-2.743zm-2.036-6.071c.749-.912 1.253-2.179 1.116-3.447-1.092.045-2.408.729-3.176 1.636-.688.802-1.294 2.091-1.135 3.336 1.222.095 2.445-.615 3.195-1.525z" /></svg>
                                <div className="text-left">
                                    <div className="text-[11px] leading-tight">Download on the</div>
                                    <div className="text-base font-semibold leading-tight">App Store</div>
                                </div>
                            </a>
                            <a href={googlePlayUrl} target="_blank" rel="noopener noreferrer" className="bg-black text-white px-8 py-3.5 rounded-xl flex items-center gap-3 hover:scale-105 transition-transform">
                                <svg className="w-7 h-7" viewBox="0 0 512 512" fill="currentColor"><path d="M325.3 234.3L104.6 13l280.8 161.2-60.1 60.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58.9-34.1-65.7 64.5 65.7 64.5 60.1-34.1c18-14.3 18-46.5-1.2-60.8zM104.6 499l280.8-161.2-60.1-60.1L104.6 499z" /></svg>
                                <div className="text-left">
                                    <div className="text-[11px] leading-tight">GET IT ON</div>
                                    <div className="text-base font-semibold leading-tight">Google Play</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-white border-t border-gray-100 pt-16 pb-8">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12 border-b border-gray-100 pb-12">
                            <div className="md:col-span-1">
                                <img src="/images/logo.png" alt="Scrapify Footer Logo" className="h-22 w-auto object-contain mb-4 inline-block" />
                            </div>

                            <div>
                                <h4 className="font-bold text-scrapify-blue mb-6 uppercase tracking-wider text-xs">Company</h4>
                                <ul className="space-y-4 text-sm text-gray-500">
                                    <li><a href="#" className="hover:text-scrapify-green transition-colors">About Us</a></li>
                                    <li><a href={route('support')} className="hover:text-scrapify-green transition-colors">Contact Us</a></li>
                                    <li><a href="#" className="hover:text-scrapify-green transition-colors">Become a Partner</a></li>
                                </ul>
                            </div>

                            <div>
                                <h4 className="font-bold text-scrapify-blue mb-6 uppercase tracking-wider text-xs">Legal</h4>
                                <ul className="space-y-4 text-sm text-gray-500">
                                    <li><a href="#" className="hover:text-scrapify-green transition-colors">Terms & Conditions</a></li>
                                    <li><a href="#" className="hover:text-scrapify-green transition-colors">Privacy Policy</a></li>
                                    <li><a href="#" className="hover:text-scrapify-green transition-colors">Cancellation Policy</a></li>
                                </ul>
                            </div>

                            <div>
                                <h4 className="font-bold text-scrapify-blue mb-6 uppercase tracking-wider text-xs">Get Support</h4>
                                <ul className="space-y-4 text-sm text-gray-500">
                                    <li><a href="mailto:support@scrapify.com" className="hover:text-scrapify-green transition-colors">support@scrapify.com</a></li>
                                    <li><a href="tel:+919870291813" className="hover:text-scrapify-green transition-colors">+91 98702 91813</a></li>
                                    <li><a href={route('support')} className="hover:text-scrapify-green transition-colors">Help Center</a></li>
                                    <div className="flex gap-4 mt-6">
                                        <a href="#" className="bg-gray-100 p-2 rounded-full text-gray-600 hover:bg-scrapify-green hover:text-white transition-all">
                                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" /></svg>
                                        </a>
                                        <a href="#" className="bg-gray-100 p-2 rounded-full text-gray-600 hover:bg-scrapify-green hover:text-white transition-all">
                                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" /></svg>
                                        </a>
                                        <a href="#" className="bg-gray-100 p-2 rounded-full text-gray-600 hover:bg-scrapify-green hover:text-white transition-all">
                                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" /></svg>
                                        </a>
                                    </div>
                                </ul>
                            </div>
                        </div>
                        <div className="flex flex-col md:flex-row justify-between items-center text-xs text-gray-400">
                            <div>© {new Date().getFullYear()} Scrapify | Abhyuthanam Industries Pvt. Ltd.</div>
                            <div className="mt-2 md:mt-0 opacity-50">Built with <span className="text-red-500">♥</span></div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
