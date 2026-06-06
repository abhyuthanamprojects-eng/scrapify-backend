import { motion, AnimatePresence } from "framer-motion";
import { Plus } from "lucide-react";
import { useState } from "react";

const faqs = [
  { q: "What is Scrapify?", a: "Scrapify is India's on-demand scrap pickup service. कबाड़ हटाओ, कैश पाओ — book a pickup from the app, our verified team arrives at your door, weighs your scrap and pays you instantly via UPI or cash." },
  { q: "How do I schedule a pickup?", a: "Open the Scrapify app, choose your scrap category, enter your address and pick a time slot. You'll receive an instant booking confirmation." },
  { q: "How much does the pickup cost?", a: "Pickups are 100% FREE. We pay you for your scrap based on the latest market rates — visible inside the app before you confirm." },
  { q: "What kind of scrap do you accept?", a: "Paper & cardboard, plastics, metals, e-waste, glass, batteries, old appliances and more. If you're not sure, ask our support — we accept almost everything." },
  { q: "Where does my scrap go?", a: "Every kilo collected is sent to certified recyclers powered by Abhyuthanam Industries Pvt. Ltd. — ensuring it's processed responsibly and never sent to landfills." },
  { q: "How can I trust the weighing?", a: "Our collectors carry digital, calibrated weighing scales. You see the live weight and rate before payment is processed. Full transparency." },
];

export function FAQ() {
  const [open, setOpen] = useState<number | null>(0);

  return (
    <section id="faqs" className="py-20 md:py-28">
      <div className="mx-auto w-[min(820px,94%)]">
        <div className="text-center">
          <span className="inline-flex items-center gap-2 rounded-full bg-primary/15 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep">
            ? FAQs
          </span>
          <motion.h2
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="mt-3 text-3xl md:text-5xl font-extrabold text-navy"
          >
            Frequently Asked Questions
          </motion.h2>
        </div>

        <div className="mt-12 space-y-3">
          {faqs.map((f, i) => {
            const isOpen = open === i;
            return (
              <motion.div
                key={f.q}
                initial={{ opacity: 0, y: 16 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.4, delay: i * 0.05 }}
                className="rounded-2xl border border-border bg-card shadow-soft overflow-hidden"
              >
                <button
                  onClick={() => setOpen(isOpen ? null : i)}
                  className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                >
                  <span className="text-sm md:text-base font-bold text-navy">{f.q}</span>
                  <motion.span
                    animate={{ rotate: isOpen ? 45 : 0 }}
                    className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full gradient-primary text-primary-foreground"
                  >
                    <Plus className="h-4 w-4" />
                  </motion.span>
                </button>
                <AnimatePresence initial={false}>
                  {isOpen && (
                    <motion.div
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: "auto", opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      transition={{ duration: 0.3 }}
                      className="overflow-hidden"
                    >
                      <p className="px-5 pb-5 text-sm text-muted-foreground leading-relaxed">{f.a}</p>
                    </motion.div>
                  )}
                </AnimatePresence>
              </motion.div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
