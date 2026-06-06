import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

const email = 'support@scrapify.com';
const phone = '+91 98702 91813';
const whatsapp = 'https://wa.me/919870291813';

const supportChannels = [
    {
        title: 'Email Support',
        value: email,
        href: `mailto:${email}`,
        description: 'Best for detailed issues, invoices, or request follow-ups.',
    },
    {
        title: 'Phone Support',
        value: phone,
        href: `tel:+919870291813`,
        description: 'Call us for urgent pickup, login, or account issues.',
    },
    {
        title: 'WhatsApp',
        value: 'Chat on WhatsApp',
        href: whatsapp,
        description: 'Fastest way to reach the team during business hours.',
    },
];

const faqs = [
    {
        q: 'How soon will I get a reply?',
        a: 'We usually respond within one business day. Urgent account and pickup issues are handled sooner.',
    },
    {
        q: 'Can I report a booking or pickup issue here?',
        a: 'Yes. Mention your order ID, registered mobile number, and a short summary of the issue.',
    },
    {
        q: 'Do you support corporate and warehouse requests?',
        a: 'Yes. Use this page for customer, partner, warehouse, and corporate support queries.',
    },
    {
        q: 'What information should I include?',
        a: 'Share your name, mobile number, email, subject, and a clear description so we can resolve it quickly.',
    },
];

export default function Support() {
    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState({ type: '', text: '' });
    const [openFaq, setOpenFaq] = useState(0);

    const phoneRegex = /^[6-9]\d{9}$/;

    const submit = async (e) => {
        e.preventDefault();
        setStatus({ type: '', text: '' });

        if (!form.name.trim()) {
            setStatus({ type: 'error', text: 'Please enter your name.' });
            return;
        }
        if (!form.email.trim()) {
            setStatus({ type: 'error', text: 'Please enter your email address.' });
            return;
        }
        if (form.phone.trim() && !phoneRegex.test(form.phone.trim())) {
            setStatus({ type: 'error', text: 'Please enter a valid 10-digit mobile number.' });
            return;
        }
        if (!form.message.trim()) {
            setStatus({ type: 'error', text: 'Please enter your message.' });
            return;
        }

        setLoading(true);
        try {
            const response = await fetch('/api/contact', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(form),
            });

            const json = await response.json();
            if (!response.ok || !json.success) {
                const firstError = json?.errors
                    ? Object.values(json.errors).flat().find(Boolean)
                    : null;
                setStatus({
                    type: 'error',
                    text: firstError || json.message_text || 'Unable to submit support request.',
                });
                return;
            }

            setForm({ name: '', email: '', phone: '', subject: '', message: '' });
            setStatus({
                type: 'success',
                text: 'Your support request has been sent. Our team will contact you soon.',
            });
        } catch {
            setStatus({
                type: 'error',
                text: 'Network error. Please try again or email us directly.',
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Support | Scrapify" />
            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
                .font-outfit { font-family: 'Outfit', sans-serif; }
            `}</style>

            <div className="min-h-screen bg-white font-outfit text-gray-800">
                <nav className="sticky top-0 z-50 bg-white/85 backdrop-blur-md border-b border-gray-100 shadow-sm">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 h-[4rem] md:h-[5rem] flex items-center justify-between gap-4">
                        <Link href="/" className="flex items-center gap-3">
                            <img src="/images/logo.png" alt="Scrapify" className="h-10 w-auto object-contain" />
                        </Link>
                        <div className="flex items-center gap-3 md:gap-4">
                            <Link href="/" className="text-sm font-semibold text-gray-600 hover:text-scrapify-green transition-colors">
                                Home
                            </Link>
                            <a
                                href="mailto:support@scrapify.com"
                                className="hidden sm:inline-flex bg-scrapify-green text-white px-5 py-2.5 rounded-full font-semibold hover:bg-scrapify-blue transition-all shadow-md hover:shadow-lg"
                            >
                                Email Support
                            </a>
                        </div>
                    </div>
                </nav>

                <header className="relative overflow-hidden">
                    <div className="absolute inset-0 bg-gradient-to-b from-white via-green-50/70 to-[#e2f0e3] -z-10" />
                    <div className="max-w-7xl mx-auto px-6 lg:px-8 py-16 md:py-24 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <span className="inline-flex items-center gap-2 bg-green-100 text-scrapify-green text-xs font-bold px-4 py-2 rounded-full uppercase tracking-wider mb-6">
                                Support Center
                            </span>
                            <h1 className="text-4xl md:text-6xl font-extrabold text-scrapify-blue leading-tight tracking-tight">
                                Need help?
                                <br />
                                We are here.
                            </h1>
                            <p className="mt-6 text-lg text-gray-600 max-w-xl leading-relaxed">
                                Contact the Scrapify team for pickup issues, account help, corporate requests, warehouse questions, or general support.
                            </p>
                            <div className="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl">
                                {supportChannels.map((channel) => (
                                    <a
                                        key={channel.title}
                                        href={channel.href}
                                        className="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all"
                                    >
                                        <div className="text-xs font-bold uppercase tracking-wider text-scrapify-green">{channel.title}</div>
                                        <div className="mt-2 text-sm font-semibold text-scrapify-blue">{channel.value}</div>
                                        <div className="mt-2 text-xs text-gray-500 leading-relaxed">{channel.description}</div>
                                    </a>
                                ))}
                            </div>
                        </div>

                        <div className="bg-white rounded-[2rem] shadow-[0_30px_80px_rgba(16,24,40,0.10)] border border-gray-100 p-6 md:p-8">
                            <div className="flex items-center justify-between mb-6">
                                <div>
                                    <h2 className="text-2xl font-bold text-scrapify-blue">Send a support request</h2>
                                    <p className="text-sm text-gray-500 mt-1">We will route this to the right team.</p>
                                </div>
                                <div className="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center text-scrapify-green font-bold text-xl">
                                    ?
                                </div>
                            </div>

                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <SupportInput
                                        label="Name"
                                        value={form.name}
                                        onChange={(value) => setForm((prev) => ({ ...prev, name: value }))}
                                        placeholder="Your full name"
                                        required
                                    />
                                    <SupportInput
                                        label="Email"
                                        type="email"
                                        value={form.email}
                                        onChange={(value) => setForm((prev) => ({ ...prev, email: value }))}
                                        placeholder="you@example.com"
                                        required
                                    />
                                </div>
                                <SupportInput
                                    label="Mobile Number (optional)"
                                    type="tel"
                                    inputMode="numeric"
                                    maxLength={10}
                                    value={form.phone}
                                    onChange={(value) => setForm((prev) => ({ ...prev, phone: value.replace(/\D/g, '').slice(0, 10) }))}
                                    placeholder="10-digit mobile number"
                                />
                                <SupportInput
                                    label="Subject"
                                    value={form.subject}
                                    onChange={(value) => setForm((prev) => ({ ...prev, subject: value }))}
                                    placeholder="What do you need help with?"
                                />
                                <SupportTextarea
                                    label="Message"
                                    value={form.message}
                                    onChange={(value) => setForm((prev) => ({ ...prev, message: value }))}
                                    placeholder="Describe the issue in a few lines"
                                    required
                                />

                                {status.text && (
                                    <div
                                        className={`rounded-2xl px-4 py-3 text-sm font-medium ${status.type === 'success'
                                            ? 'bg-green-50 text-scrapify-green border border-green-100'
                                            : 'bg-red-50 text-red-700 border border-red-100'
                                        }`}
                                    >
                                        {status.text}
                                    </div>
                                )}

                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="w-full bg-scrapify-green text-white px-6 py-4 rounded-2xl font-semibold hover:bg-scrapify-blue transition-all shadow-md hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    {loading ? 'Sending...' : 'Submit Support Request'}
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                <section className="py-20 bg-white">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8">
                        <div className="text-center mb-12">
                            <span className="bg-gray-100 text-gray-600 border border-gray-200 text-xs font-bold px-4 py-1 rounded-full uppercase tracking-wider inline-flex items-center gap-2">
                                <span className="w-2 h-2 rounded-full bg-scrapify-green" />
                                Fast help
                            </span>
                            <h2 className="mt-4 text-3xl md:text-4xl font-bold text-scrapify-blue">Quick contact options</h2>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {supportChannels.map((channel) => (
                                <a
                                    key={channel.title}
                                    href={channel.href}
                                    className="group bg-gray-50 rounded-[1.75rem] border border-gray-100 p-6 hover:shadow-xl hover:-translate-y-1 transition-all"
                                >
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <div className="text-xs font-bold uppercase tracking-wider text-gray-400">{channel.title}</div>
                                            <div className="mt-2 text-xl font-bold text-scrapify-blue">{channel.value}</div>
                                        </div>
                                        <div className="w-11 h-11 rounded-full bg-green-100 text-scrapify-green flex items-center justify-center group-hover:scale-105 transition-transform">
                                            →
                                        </div>
                                    </div>
                                    <p className="mt-4 text-sm text-gray-600 leading-relaxed">{channel.description}</p>
                                </a>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="py-20 bg-gray-50">
                    <div className="max-w-3xl mx-auto px-6 lg:px-8">
                        <div className="text-center mb-10">
                            <span className="bg-white text-gray-600 border border-gray-200 text-xs font-bold px-4 py-1 rounded-full uppercase tracking-wider inline-flex items-center gap-2 shadow-sm">
                                FAQ
                            </span>
                            <h2 className="mt-4 text-3xl font-bold text-scrapify-blue">Frequently asked questions</h2>
                        </div>

                        <div className="space-y-4">
                            {faqs.map((faq, index) => (
                                <div key={faq.q} className="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                                    <button
                                        type="button"
                                        onClick={() => setOpenFaq(openFaq === index ? -1 : index)}
                                        className="w-full px-6 py-5 flex items-center justify-between gap-4 text-left"
                                    >
                                        <span className="font-semibold text-scrapify-blue">{faq.q}</span>
                                        <span className={`text-scrapify-green transition-transform ${openFaq === index ? 'rotate-45' : ''}`}>+</span>
                                    </button>
                                    <div className={`px-6 text-sm text-gray-600 overflow-hidden transition-all duration-300 ${openFaq === index ? 'max-h-32 pb-5 opacity-100' : 'max-h-0 pb-0 opacity-0'}`}>
                                        {faq.a}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}

function SupportInput({ label, value, onChange, placeholder, type = 'text', inputMode, maxLength, required = false }) {
    return (
        <label className="block">
            <span className="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">{label}{required ? ' *' : ''}</span>
            <input
                type={type}
                inputMode={inputMode}
                maxLength={maxLength}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-scrapify-green focus:bg-white focus:ring-4 focus:ring-green-100 outline-none transition-all"
                required={required}
            />
        </label>
    );
}

function SupportTextarea({ label, value, onChange, placeholder, required = false }) {
    return (
        <label className="block">
            <span className="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">{label}{required ? ' *' : ''}</span>
            <textarea
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                rows={5}
                className="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-scrapify-green focus:bg-white focus:ring-4 focus:ring-green-100 outline-none transition-all resize-none"
                required={required}
            />
        </label>
    );
}
