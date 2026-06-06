import { motion } from "framer-motion";
import { Truck, ListChecks, Wallet } from "lucide-react";

const steps = [
  {
    step: "STEP 01",
    title: "Pick a scrap category",
    desc: "Open the app and choose what you'd like us to pick up — e-waste, metals, plastics, paper and more.",
    icon: ListChecks,
  },
  {
    step: "STEP 02",
    title: "Schedule a free pickup",
    desc: "Add details, pick a slot that works for you. Our verified collectors arrive at your doorstep on time.",
    icon: Truck,
  },
  {
    step: "STEP 03",
    title: "Handover & get paid",
    desc: "We weigh on the spot, give a transparent quote and pay instantly to UPI / cash. Zero hassle.",
    icon: Wallet,
  },
];

export function HowItWorks() {
  return (
    <section id="how" className="py-20 md:py-28">
      <div className="mx-auto w-[min(1200px,94%)] text-center">
        <span className="inline-flex items-center gap-2 rounded-full bg-primary/15 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep">
          How it works
        </span>
        <motion.h2
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="mt-3 text-3xl md:text-5xl font-extrabold text-navy"
        >
          Simple steps to a <span className="text-gradient-primary">cleaner</span> environment
        </motion.h2>
        <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
          A few taps stand between your scrap and instant cash.
        </p>

        <div className="relative mt-14 grid grid-cols-1 md:grid-cols-3 gap-6">
          {/* dotted connector */}
          <div className="hidden md:block absolute top-24 left-[16%] right-[16%] h-0.5 border-t-2 border-dashed border-primary/40" />
          {steps.map((s, i) => (
            <motion.div
              key={s.step}
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6, delay: i * 0.15 }}
              whileHover={{ y: -6 }}
              className="relative rounded-3xl gradient-primary p-1 shadow-elegant transition-spring"
            >
              <div className="relative h-full rounded-[1.4rem] bg-card p-7 text-left">
                <div className="flex items-start justify-between">
                  <span className="rounded-full bg-primary/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-primary-deep">
                    {s.step}
                  </span>
                  <motion.div
                    whileHover={{ rotate: 12, scale: 1.1 }}
                    className="flex h-14 w-14 items-center justify-center rounded-2xl gradient-primary text-primary-foreground shadow-soft"
                  >
                    <s.icon className="h-7 w-7" />
                  </motion.div>
                </div>
                <h3 className="mt-6 text-xl font-bold text-navy">{s.title}</h3>
                <p className="mt-2 text-sm text-muted-foreground">{s.desc}</p>

                <div className="mt-6 rounded-2xl bg-secondary/60 p-4">
                  <div className="flex items-center justify-between text-xs">
                    <span className="font-semibold text-primary-deep">App preview</span>
                    <span className="text-muted-foreground">step {i + 1}/3</span>
                  </div>
                  <div className="mt-3 space-y-2">
                    <div className="h-2 w-full rounded bg-card" />
                    <div className="h-2 w-2/3 rounded bg-card" />
                    <div className="h-2 w-1/2 rounded bg-card" />
                  </div>
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
