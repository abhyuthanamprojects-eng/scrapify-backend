import { motion } from "framer-motion";
import { Recycle, IndianRupee, Leaf } from "lucide-react";
const collectorMale = "/images/new/collector-male.png";
const collectorFemale = "/images/new/collector-female.png";
const appMockup = "/images/new/app-mockup.png";
import { StoreBadges } from "./StoreBadges";

export function Hero() {
  return (
    <section className="relative pt-28 md:pt-36 pb-12 md:pb-20 overflow-hidden gradient-hero">
      <div className="absolute inset-0 bg-grid pointer-events-none opacity-60" />

      {/* floating decor */}
      <motion.div
        animate={{ y: [0, -14, 0], rotate: [0, 8, 0] }}
        transition={{ duration: 7, repeat: Infinity, ease: "easeInOut" }}
        className="absolute left-[6%] top-32 hidden md:flex h-14 w-14 items-center justify-center rounded-2xl bg-card shadow-card text-primary"
      >
        <Recycle className="h-7 w-7" />
      </motion.div>
      <motion.div
        animate={{ y: [0, 14, 0], rotate: [0, -8, 0] }}
        transition={{ duration: 6, repeat: Infinity, ease: "easeInOut" }}
        className="absolute right-[7%] top-44 hidden md:flex h-14 w-14 items-center justify-center rounded-2xl bg-card shadow-card text-rupee"
      >
        <IndianRupee className="h-7 w-7" />
      </motion.div>
      <motion.div
        animate={{ y: [0, -10, 0] }}
        transition={{ duration: 5, repeat: Infinity, ease: "easeInOut" }}
        className="absolute left-[12%] bottom-20 hidden md:flex h-12 w-12 items-center justify-center rounded-2xl bg-card shadow-card text-primary-deep"
      >
        <Leaf className="h-6 w-6" />
      </motion.div>

      <div className="relative mx-auto w-[min(1200px,94%)] text-center">
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6 }}
          className="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-card/80 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-primary-deep backdrop-blur"
        >
          <span className="h-2 w-2 rounded-full bg-primary animate-pulse" />
          India's Smartest Scrap Pickup
        </motion.div>

        <motion.h1
          initial={{ opacity: 0, y: 24 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.7, delay: 0.1 }}
          className="mx-auto mt-5 max-w-4xl text-balance text-4xl sm:text-6xl md:text-7xl font-extrabold leading-[1.05] text-navy"
        >
          Turn your <span className="text-gradient-primary">scrap</span>
          <br />
          into <span className="text-gradient-primary">cash</span> in minutes!
        </motion.h1>

        <motion.p
          initial={{ opacity: 0, y: 16 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.7, delay: 0.2 }}
          className="mx-auto mt-5 max-w-xl text-base md:text-lg text-muted-foreground"
        >
          कबाड़ हटाओ, कैश पाओ — Book a doorstep pickup in seconds and
          get paid instantly. Clean home. Cleaner planet.
        </motion.p>

        <motion.div
          initial={{ opacity: 0, y: 16 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.7, delay: 0.3 }}
          className="mt-7 mb-18"
        >
          <StoreBadges />
        </motion.div>

        {/* Hero visual: collectors + phone mockups */}
        <div className="relative mt-12 md:mt-16">
          <div className="grid grid-cols-12 items-end gap-2 md:gap-4">
            <motion.div
              initial={{ opacity: 0, x: -40 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8, delay: 0.4 }}
              className="col-span-3 md:col-span-3 flex justify-start items-end overflow-visible"
            >
              <img
                src={collectorMale}
                alt="Scrapify collector"
                className="w-full h-auto max-w-none scale-[1.4] md:scale-[1.6] origin-bottom drop-shadow-[0_20px_30px_rgba(0,0,0,0.15)] animate-float-slow"
              />
            </motion.div>

            <motion.div
              initial={{ opacity: 0, y: 60, scale: 0.9 }}
              animate={{ opacity: 1, y: 0, scale: 1 }}
              transition={{ duration: 0.9, delay: 0.5, ease: [0.34, 1.56, 0.64, 1] }}
              className="col-span-6 relative"
            >
              <div className="relative mx-auto max-w-3xl scale-110 md:scale-125">
                <div className="absolute -inset-8 rounded-[3rem] bg-primary/10 blur-3xl" />
                <div className="relative rounded-[2.5rem] bg-card/60 p-3 md:p-6 shadow-elegant backdrop-blur">
                  <img
                    src={appMockup}
                    alt="Scrapify app screens"
                    className="w-full h-auto"
                  />
                </div>
                {/* badges */}
                <motion.div
                  initial={{ opacity: 0, scale: 0.5 }}
                  animate={{ opacity: 1, scale: 1 }}
                  transition={{ delay: 1, type: "spring" }}
                  className="absolute -left-2 md:-left-10 top-1/3 rounded-2xl bg-card px-3 py-2 shadow-card flex items-center gap-2"
                >
                  <span className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/15 text-primary-deep">
                    <IndianRupee className="h-4 w-4" />
                  </span>
                  <div className="text-left">
                    <p className="text-[10px] text-muted-foreground">Earned today</p>
                    <p className="text-sm font-bold text-navy">₹ 1,240</p>
                  </div>
                </motion.div>
                <motion.div
                  initial={{ opacity: 0, scale: 0.5 }}
                  animate={{ opacity: 1, scale: 1 }}
                  transition={{ delay: 1.2, type: "spring" }}
                  className="absolute -right-2 md:-right-10 bottom-1/4 rounded-2xl bg-card px-3 py-2 shadow-card flex items-center gap-2"
                >
                  <span className="relative flex h-3 w-3">
                    <span className="absolute inline-flex h-full w-full rounded-full bg-primary opacity-75 animate-pulse-ring" />
                    <span className="relative inline-flex h-3 w-3 rounded-full bg-primary" />
                  </span>
                  <p className="text-xs font-semibold text-navy">Pickup arriving</p>
                </motion.div>
              </div>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 40 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.8, delay: 0.4 }}
              className="col-span-3 md:col-span-3 flex justify-end items-end overflow-visible"
            >
              <img
                src={collectorFemale}
                alt="Scrapify collector"
                className="w-full h-auto max-w-none scale-[1.4] md:scale-[1.6] origin-bottom drop-shadow-[0_20px_30px_rgba(0,0,0,0.15)] animate-float-slow [animation-delay:-3s]"
              />
            </motion.div>
          </div>
        </div>
      </div>
    </section>
  );
}
