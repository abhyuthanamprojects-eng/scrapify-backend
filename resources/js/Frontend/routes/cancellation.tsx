import { createFileRoute, Link } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { XCircle, ArrowLeft, Clock, RefreshCcw, AlertTriangle } from "lucide-react";
import { Navbar } from "@/components/site/Navbar";
import { Footer } from "@/components/site/Footer";

export const Route = createFileRoute("/cancellation")({
  head: () => ({
    meta: [
      { title: "Cancellation & Refund Policy — Scrapify" },
      { name: "description", content: "How to cancel a Scrapify pickup, our refund policy, and timelines for processing refunds." },
      { property: "og:title", content: "Cancellation & Refund Policy — Scrapify" },
      { property: "og:description", content: "Cancel pickups easily and learn about our fair refund policy." },
    ],
  }),
  component: CancellationPage,
});

const sections = [
  {
    icon: Clock,
    title: "1. Free Cancellation Window",
    body: [
      "You can cancel a scheduled pickup free of charge up to 2 hours before the pickup slot.",
      "Cancellations can be made directly from the Scrapify app, website or by calling our support line.",
    ],
  },
  {
    icon: AlertTriangle,
    title: "2. Late Cancellations & No-Shows",
    body: [
      "Cancellations made within 2 hours of the slot may attract a nominal convenience fee of ₹50.",
      "If our partner reaches your address and you are unavailable or refuse pickup, a no-show fee of ₹100 may apply.",
      "Repeated no-shows may lead to temporary suspension of your account.",
    ],
  },
  {
    icon: RefreshCcw,
    title: "3. Refund Policy",
    body: [
      "All scrap payments are made instantly at the time of pickup, so refunds typically do not apply.",
      "If you were overcharged any fee in error, the amount will be refunded to your original payment method within 5-7 working days.",
      "For premium subscription services (where applicable), refunds are processed on a pro-rata basis for the unused period.",
    ],
  },
  {
    icon: XCircle,
    title: "4. Cancellations by Scrapify",
    body: [
      "We may cancel a booking due to unavailability of partners, weather, or operational reasons.",
      "In such cases, we will notify you in advance and reschedule at your convenience — no charges apply.",
    ],
  },
];

function CancellationPage() {
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
            <RefreshCcw className="h-3.5 w-3.5" />
            Cancellation & Refund
          </motion.div>
          <h1 className="mt-5 text-4xl md:text-6xl font-extrabold tracking-tight text-foreground">
            Plans changed? <span className="text-primary-deep">No worries.</span>
          </h1>
          <p className="mx-auto mt-4 max-w-2xl text-base md:text-lg text-muted-foreground">
            Cancel any pickup easily. Here's how our cancellation and refund policy works.
          </p>
        </div>
      </section>

      <section className="pb-16">
        <div className="mx-auto w-[min(900px,94%)] grid gap-5">
          {sections.map((s, i) => {
            const Icon = s.icon;
            return (
              <motion.article
                key={s.title}
                initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, margin: "-80px" }} transition={{ duration: 0.5, delay: i * 0.05 }}
                className="rounded-2xl border border-border/60 bg-card p-6 md:p-8 shadow-soft"
              >
                <div className="flex items-start gap-4">
                  <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl gradient-primary text-primary-foreground shadow-soft">
                    <Icon className="h-5 w-5" />
                  </div>
                  <div className="flex-1">
                    <h2 className="text-xl md:text-2xl font-bold text-foreground">{s.title}</h2>
                    <ul className="mt-3 space-y-2">
                      {s.body.map((line, idx) => (
                        <li key={idx} className="flex gap-3 text-sm md:text-base text-muted-foreground leading-relaxed">
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

          <Link to="/" className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary-deep hover:underline">
            <ArrowLeft className="h-4 w-4" /> Back to Home
          </Link>
        </div>
      </section>
      <Footer />
    </main>
  );
}
