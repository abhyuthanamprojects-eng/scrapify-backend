import { motion } from "framer-motion";
import { Star } from "lucide-react";

const reviews = [
  { name: "Rahul S.", city: "Sector 14", text: "The pickup was on time and the rate was honest. Got cash in 10 minutes — best app for scrap selling!", color: "bg-primary/15 text-primary-deep" },
  { name: "Prashant K.", city: "Sector 22", text: "Loved the doorstep service. The collector was polite, weighed everything correctly. Highly recommended.", color: "bg-rupee/20 text-navy" },
  { name: "Ritika M.", city: "Sector 7", text: "Seamless experience. The app made it super simple to schedule and the rates are very competitive.", color: "bg-navy/15 text-navy" },
  { name: "Aditya V.", city: "Sector 19", text: "Finally an app that actually shows up! My e-waste was picked up the same day. Loving it.", color: "bg-mint text-primary-deep" },
  { name: "Komal G.", city: "Sector 5", text: "Booked a pickup for old newspapers. Whole process took less than 15 minutes. Will use again!", color: "bg-primary/15 text-primary-deep" },
  { name: "Rohan P.", city: "Sector 30", text: "Pricing was way higher than my local kabaadiwala. Plus instant UPI transfer. Solid product.", color: "bg-rupee/20 text-navy" },
];

export function Reviews() {
  return (
    <section className="py-20 md:py-28 gradient-mint">
      <div className="mx-auto w-[min(1200px,94%)]">
        <div className="text-center">
          <span className="inline-flex items-center gap-2 rounded-full bg-primary/15 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep">
            ★ Reviews
          </span>
          <motion.h2
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="mt-3 text-3xl md:text-5xl font-extrabold text-navy"
          >
            User reviews and feedback
          </motion.h2>
          <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
            See how Scrapify has transformed users' lives through their own words.
          </p>
        </div>

        <div className="mt-12 overflow-hidden">
          <div className="flex gap-5 marquee w-max">
            {[...reviews, ...reviews].map((r, i) => (
              <motion.div
                key={i}
                whileHover={{ y: -6, scale: 1.02 }}
                className="w-[300px] shrink-0 rounded-3xl bg-card p-6 shadow-card transition-spring"
              >
                <div className="flex items-center gap-3">
                  <div className={`flex h-10 w-10 items-center justify-center rounded-full font-bold ${r.color}`}>
                    {r.name[0]}
                  </div>
                  <div>
                    <p className="text-sm font-bold text-navy">{r.name}</p>
                    <p className="text-xs text-muted-foreground">{r.city}</p>
                  </div>
                </div>
                <div className="mt-3 flex gap-0.5 text-rupee">
                  {Array.from({ length: 5 }).map((_, k) => (
                    <Star key={k} className="h-3.5 w-3.5 fill-current" />
                  ))}
                </div>
                <p className="mt-3 text-sm text-foreground/80 leading-relaxed">{r.text}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
