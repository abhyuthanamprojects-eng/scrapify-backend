import { createFileRoute, Link } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { ShieldCheck, Lock, Eye, Database, UserCheck, Mail, ArrowLeft } from "lucide-react";
import { Navbar } from "@/components/site/Navbar";
import { Footer } from "@/components/site/Footer";

export const Route = createFileRoute("/privacy")({
  head: () => ({
    meta: [
      { title: "Privacy Policy — Scrapify | How We Protect Your Data" },
      {
        name: "description",
        content:
          "Read Scrapify's Privacy Policy. Learn how we collect, use, and safeguard your personal information when you book doorstep scrap pickups.",
      },
      { property: "og:title", content: "Privacy Policy — Scrapify" },
      {
        property: "og:description",
        content:
          "Transparency on how Scrapify collects, stores and protects your data across our doorstep scrap pickup service.",
      },
      { property: "og:type", content: "article" },
    ],
  }),
  component: PrivacyPage,
});

const sections = [
  {
    icon: Database,
    title: "1. Information We Collect",
    body: [
      "Personal details you provide when booking a pickup: name, mobile number, email address, pickup address and pincode.",
      "Transaction data: scrap categories, weight, pickup date/time, payout amount and UPI ID for instant payments.",
      "Device & usage data: device type, IP address, app interactions and crash logs to improve our service.",
      "Location data (with your permission) to match you with the nearest collection partner.",
    ],
  },
  {
    icon: Eye,
    title: "2. How We Use Your Information",
    body: [
      "To schedule, fulfil and confirm your doorstep scrap pickups.",
      "To process instant payments via UPI/bank transfer for the scrap you sell.",
      "To send booking updates, receipts, and customer support communications.",
      "To improve our routes, pricing, and overall service quality through analytics.",
      "To comply with legal, tax and regulatory obligations in India.",
    ],
  },
  {
    icon: UserCheck,
    title: "3. Sharing of Information",
    body: [
      "We share limited details (name, address, phone, pickup items) with our verified collection partners only to fulfil your booking.",
      "We may share data with payment gateways, SMS providers and analytics tools that act as our processors under strict confidentiality.",
      "We never sell your personal data to advertisers or third parties.",
      "We may disclose information when required by law, court order, or to protect the rights and safety of our users.",
    ],
  },
  {
    icon: Lock,
    title: "4. Data Security",
    body: [
      "All data is transmitted over encrypted HTTPS connections and stored in access-controlled servers in India.",
      "Payment information is handled via PCI-DSS compliant gateways — we do not store your full bank or card details.",
      "Access to personal data is restricted to authorised Scrapify personnel on a need-to-know basis.",
    ],
  },
  {
    icon: ShieldCheck,
    title: "5. Your Rights & Choices",
    body: [
      "You can access, update or correct your personal information directly from the Scrapify app.",
      "You may request deletion of your account and associated data by writing to our support team.",
      "You can opt out of marketing communications at any time via the unsubscribe link or app settings.",
      "Transactional messages (booking confirmations, payment receipts) cannot be opted out of while your account is active.",
    ],
  },
  {
    icon: Mail,
    title: "6. Cookies & Tracking",
    body: [
      "Our website uses essential cookies to keep the site working and analytics cookies to understand usage patterns.",
      "You can control cookies through your browser settings. Disabling them may affect site functionality.",
    ],
  },
];

function PrivacyPage() {
  return (
    <main className="min-h-screen bg-background">
      <Navbar />

      {/* Hero */}
      <section className="relative overflow-hidden pt-32 pb-16">
        <div className="absolute inset-0 -z-10 gradient-primary opacity-10" />
        <div className="absolute -top-20 right-0 -z-10 h-72 w-72 rounded-full bg-primary/20 blur-3xl" />
        <div className="absolute -bottom-20 left-0 -z-10 h-72 w-72 rounded-full bg-accent/20 blur-3xl" />

        <div className="mx-auto w-[min(900px,94%)] text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="inline-flex items-center gap-2 rounded-full border border-border/60 bg-background/70 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep backdrop-blur"
          >
            <ShieldCheck className="h-3.5 w-3.5" />
            Privacy Policy
          </motion.div>
          <motion.h1
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="mt-5 text-4xl md:text-6xl font-extrabold tracking-tight text-foreground"
          >
            Your data, <span className="text-primary-deep">handled with care.</span>
          </motion.h1>
          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.2 }}
            className="mx-auto mt-4 max-w-2xl text-base md:text-lg text-muted-foreground"
          >
            We respect your privacy as much as the planet. Here's exactly what we collect,
            why we collect it, and the rights you have over your information.
          </motion.p>
          <motion.p
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.6, delay: 0.3 }}
            className="mt-4 text-xs text-muted-foreground"
          >
            Last updated: April 2026
          </motion.p>
        </div>
      </section>

      {/* Sections */}
      <section className="pb-16">
        <div className="mx-auto w-[min(900px,94%)] grid gap-5">
          {sections.map((s, i) => {
            const Icon = s.icon;
            return (
              <motion.article
                key={s.title}
                initial={{ opacity: 0, y: 24 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, margin: "-80px" }}
                transition={{ duration: 0.5, delay: i * 0.05 }}
                className="group relative overflow-hidden rounded-2xl border border-border/60 bg-card p-6 md:p-8 shadow-soft hover:shadow-glow transition-spring"
              >
                <div className="flex items-start gap-4">
                  <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl gradient-primary text-primary-foreground shadow-soft">
                    <Icon className="h-5 w-5" />
                  </div>
                  <div className="flex-1">
                    <h2 className="text-xl md:text-2xl font-bold text-foreground">
                      {s.title}
                    </h2>
                    <ul className="mt-3 space-y-2">
                      {s.body.map((line, idx) => (
                        <li
                          key={idx}
                          className="flex gap-3 text-sm md:text-base text-muted-foreground leading-relaxed"
                        >
                          <span className="mt-2 inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                          <span>{line}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                </div>
              </motion.article>
            );
          })}

          {/* Contact card */}
          <motion.div
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
            className="mt-4 rounded-2xl gradient-primary p-8 md:p-10 text-primary-foreground shadow-glow"
          >
            <h3 className="text-2xl md:text-3xl font-extrabold">Questions about your privacy?</h3>
            <p className="mt-2 max-w-xl text-primary-foreground/90">
              Our team responds to every privacy request within 7 working days. Reach us at{" "}
              <a href="mailto:privacy@scrapify.in" className="underline underline-offset-4 font-semibold">
                privacy@scrapify.in
              </a>
              .
            </p>
            <div className="mt-6">
              <Link
                to="/"
                className="inline-flex items-center gap-2 rounded-full bg-background px-5 py-2.5 text-sm font-bold text-primary-deep shadow-soft hover:-translate-y-0.5 transition-spring"
              >
                <ArrowLeft className="h-4 w-4" />
                Back to Home
              </Link>
            </div>
          </motion.div>
        </div>
      </section>

      <Footer />
    </main>
  );
}
