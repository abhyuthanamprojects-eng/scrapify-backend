import { motion, useInView, useMotionValue, useTransform, animate } from "framer-motion";
import { useEffect, useRef } from "react";

function Counter({ to, suffix = "+" }: { to: number; suffix?: string }) {
  const ref = useRef<HTMLSpanElement>(null);
  const inView = useInView(ref, { once: true, margin: "-50px" });
  const count = useMotionValue(0);
  const rounded = useTransform(count, (v) => Math.floor(v).toLocaleString("en-IN"));

  useEffect(() => {
    if (inView) {
      const controls = animate(count, to, { duration: 2, ease: "easeOut" });
      return () => controls.stop();
    }
  }, [inView, to, count]);

  return (
    <span className="inline-flex items-baseline">
      <motion.span ref={ref}>{rounded}</motion.span>
      <span>{suffix}</span>
    </span>
  );
}

const stats = [
  { value: 400000, label: "Pickups Completed" },
  { value: 250000, label: "kg Recycled" },
  { value: 1500, label: "Pin codes Covered" },
];

export function Stats() {
  return (
    <section id="about" className="py-20 md:py-28">
      <div className="mx-auto w-[min(1200px,94%)] text-center">
        <motion.h2
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="text-3xl md:text-5xl font-extrabold text-navy"
        >
          On-demand professional <span className="text-gradient-primary">scrap collection</span>
        </motion.h2>
        <motion.p
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, delay: 0.1 }}
          className="mx-auto mt-3 max-w-xl text-muted-foreground"
        >
          The fastest growing network of trusted scrap collection professionals.
          Our teams are always on time.
        </motion.p>

        <div className="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
          {stats.map((s, i) => (
            <motion.div
              key={s.label}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6, delay: i * 0.1 }}
              whileHover={{ y: -4 }}
              className="rounded-3xl border border-border bg-card p-8 shadow-card transition-spring hover:shadow-elegant"
            >
              <p className="text-4xl md:text-5xl font-extrabold text-gradient-primary">
                <Counter to={s.value} />
              </p>
              <p className="mt-2 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                {s.label}
              </p>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
