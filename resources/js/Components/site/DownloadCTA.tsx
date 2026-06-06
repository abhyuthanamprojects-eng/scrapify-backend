import { motion } from "framer-motion";
const appMockup = "/images/new/app-mockup.png";
import { StoreBadges } from "./StoreBadges";

export function DownloadCTA() {
  return (
    <section id="download" className="py-20 md:py-28 gradient-mint">
      <div className="mx-auto w-[min(900px,94%)] text-center">
        <motion.div
          initial={{ opacity: 0, y: 30, scale: 0.95 }}
          whileInView={{ opacity: 1, y: 0, scale: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 0.7, ease: [0.34, 1.56, 0.64, 1] }}
          className="relative mx-auto max-w-md"
        >
          <div className="absolute inset-0 -z-10 rounded-full bg-primary/30 blur-3xl" />
          <div className="rounded-[2rem] bg-card p-4 shadow-elegant animate-float-slow">
            <img src={appMockup} alt="Scrapify app" loading="lazy" className="w-full h-auto" />
          </div>
        </motion.div>

        <motion.h2
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="mt-10 text-3xl md:text-5xl font-extrabold text-navy"
        >
          Get expert scrap collection in minutes.
          <br />
          Download <span className="text-gradient-primary">Scrapify!</span>
        </motion.h2>
        <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
          Thousands already trust us for hassle-free scrap selling.
        </p>

        <StoreBadges className="mt-10" />
      </div>
    </section>
  );
}
