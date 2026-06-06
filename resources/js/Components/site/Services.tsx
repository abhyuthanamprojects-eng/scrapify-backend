import { motion } from "framer-motion";
const paper = "/images/new/cat-paper.png";
const metal = "/images/new/cat-metal.png";
const plastic = "/images/new/cat-plastic.png";
const ewaste = "/images/new/cat-ewaste.png";
const glass = "/images/new/cat-glass.png";
const battery = "/images/new/cat-battery.png";

const services = [
  { name: "E-Waste", img: ewaste, price: "₹ 60/kg", tone: "from-primary/20 to-primary-glow/10" },
  { name: "Metals", img: metal, price: "₹ 45/kg", tone: "from-rupee/20 to-rupee/5" },
  { name: "Plastics", img: plastic, price: "₹ 18/kg", tone: "from-mint/40 to-mint/10" },
  { name: "Paper & Cardboard", img: paper, price: "₹ 12/kg", tone: "from-cream to-secondary" },
  { name: "Glass", img: glass, price: "₹ 8/kg", tone: "from-primary/15 to-mint/20" },
  { name: "Batteries", img: battery, price: "₹ 90/kg", tone: "from-navy/15 to-navy/5" },
];

export function Services() {
  return (
    <section id="services" className="py-20 md:py-28 gradient-mint">
      <div className="mx-auto w-[min(1200px,94%)] text-center">
        <motion.span
          initial={{ opacity: 0, y: 10 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="inline-flex items-center gap-2 rounded-full bg-primary/15 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep"
        >
          ♻ Our Services
        </motion.span>
        <motion.h2
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="mt-3 text-3xl md:text-5xl font-extrabold text-navy"
        >
          Book trusted scrap collection
        </motion.h2>
        <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
          From e-waste to old newspapers — pick a category, schedule a pickup,
          get paid the same day.
        </p>

        <div className="mt-12 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {services.map((s, i) => (
            <motion.div
              key={s.name}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-40px" }}
              transition={{ duration: 0.5, delay: i * 0.06 }}
              whileHover={{ y: -8 }}
              className="group relative cursor-pointer rounded-3xl bg-card p-5 shadow-card transition-spring hover:shadow-elegant"
            >
              <div
                className={`relative aspect-square overflow-hidden rounded-2xl bg-gradient-to-br ${s.tone}`}
              >
                <img
                  src={s.img}
                  alt={s.name}
                  loading="lazy"
                  className="absolute inset-0 h-full w-full object-contain p-3 transition-transform duration-500 group-hover:scale-110 group-hover:rotate-3"
                />
              </div>
              <div className="mt-4 text-center">
                <p className="text-sm font-bold text-navy">{s.name}</p>
                <p className="text-xs font-semibold text-primary-deep">{s.price}</p>
              </div>
            </motion.div>
          ))}
        </div>

        <p className="mt-8 text-sm italic text-muted-foreground">…and many more</p>
      </div>
    </section>
  );
}
