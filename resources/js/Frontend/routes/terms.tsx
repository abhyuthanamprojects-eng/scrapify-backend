import { createFileRoute, Link } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { ScrollText, ArrowLeft } from "lucide-react";
import { Navbar } from "@/components/site/Navbar";
import { Footer } from "@/components/site/Footer";

export const Route = createFileRoute("/terms")({
  head: () => ({
    meta: [
      { title: "Terms & Conditions — Scrapify" },
      { name: "description", content: "Read the Terms & Conditions for using Scrapify's doorstep scrap pickup service in India." },
      { property: "og:title", content: "Terms & Conditions — Scrapify" },
      { property: "og:description", content: "The rules and conditions that govern your use of the Scrapify platform and services." },
    ],
  }),
  component: TermsPage,
});

const sections = [
  {
    title: "1. Acceptance of Terms",
    body: [
      "By accessing or using the Scrapify website, app or services, you agree to be bound by these Terms & Conditions.",
      "If you do not agree with any part of these terms, please do not use our services.",
    ],
  },
  {
    title: "2. Eligibility",
    body: [
      "You must be at least 18 years old and a resident of India to use Scrapify.",
      "You agree to provide accurate, current and complete information when booking pickups or registering as a partner.",
    ],
  },
  {
    title: "3. Pickup Services",
    body: [
      "Scrapify schedules doorstep pickups based on availability of partners in your area.",
      "Final scrap weight and rates are determined at the time of pickup using calibrated digital scales.",
      "We reserve the right to refuse pickup of hazardous, restricted or contaminated materials.",
    ],
  },
  {
    title: "4. Payments",
    body: [
      "Payments are made instantly via UPI, bank transfer or cash, based on the option selected at pickup.",
      "Rates published on the app are indicative and may vary based on quality, quantity and market conditions.",
      "Scrapify is not liable for delays caused by your bank or UPI provider.",
    ],
  },
  {
    title: "5. Partner Obligations",
    body: [
      "Collection partners must complete KYC (Aadhaar, PAN) and undergo verification before onboarding.",
      "Partners agree to follow Scrapify's pricing, conduct and safety guidelines while servicing customers.",
      "Any misconduct, fraud or violation of guidelines may result in immediate termination.",
    ],
  },
  {
    title: "6. Intellectual Property",
    body: [
      "All trademarks, logos, content and software on the Scrapify platform are the property of Abhyuthanam Industries Pvt. Ltd.",
      "You may not copy, modify, distribute or reverse-engineer any part of the platform without prior written consent.",
    ],
  },
  {
    title: "7. Limitation of Liability",
    body: [
      "Scrapify is not liable for indirect, incidental or consequential damages arising from use of the service.",
      "Our maximum liability for any claim shall not exceed the value of the specific transaction in question.",
    ],
  },
  {
    title: "8. Governing Law",
    body: [
      "These terms are governed by the laws of India. Any disputes shall be subject to the exclusive jurisdiction of the courts at New Delhi.",
    ],
  },
  {
    title: "9. Changes to Terms",
    body: [
      "We may update these terms from time to time. Continued use of the service after changes constitutes acceptance of the revised terms.",
    ],
  },
];

function TermsPage() {
  return (
    <main className="min-h-screen bg-background">
      <Navbar />
      <section className="relative overflow-hidden pt-32 pb-12">
        <div className="absolute inset-0 -z-10 gradient-primary opacity-10" />
        <div className="mx-auto w-[min(900px,94%)] text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6 }}
            className="inline-flex items-center gap-2 rounded-full border border-border/60 bg-background/70 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep backdrop-blur"
          >
            <ScrollText className="h-3.5 w-3.5" />
            Terms & Conditions
          </motion.div>
          <h1 className="mt-5 text-4xl md:text-6xl font-extrabold tracking-tight text-foreground">
            The <span className="text-primary-deep">fine print</span>, made simple.
          </h1>
          <p className="mt-4 text-xs text-muted-foreground">Last updated: April 2026</p>
        </div>
      </section>

      <section className="pb-16">
        <div className="mx-auto w-[min(900px,94%)] grid gap-5">
          {sections.map((s, i) => (
            <motion.article
              key={s.title}
              initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-80px" }} transition={{ duration: 0.5, delay: i * 0.04 }}
              className="rounded-2xl border border-border/60 bg-card p-6 md:p-8 shadow-soft"
            >
              <h2 className="text-xl md:text-2xl font-bold text-foreground">{s.title}</h2>
              <ul className="mt-3 space-y-2">
                {s.body.map((line, idx) => (
                  <li key={idx} className="flex gap-3 text-sm md:text-base text-muted-foreground leading-relaxed">
                    <span className="mt-2 inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                    <span>{line}</span>
                  </li>
                ))}
              </ul>
            </motion.article>
          ))}

          <Link to="/" className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary-deep hover:underline">
            <ArrowLeft className="h-4 w-4" /> Back to Home
          </Link>
        </div>
      </section>
      <Footer />
    </main>
  );
}
