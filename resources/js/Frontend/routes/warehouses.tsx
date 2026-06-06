import { createFileRoute } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { useEffect, useState } from "react";
import { MapPin, Warehouse, Phone, Info, Globe, Shield } from "lucide-react";
import { Navbar } from "@/components/site/Navbar";
import { Footer } from "@/components/site/Footer";

export const Route = createFileRoute("/warehouses")({
  head: () => ({
    meta: [
      { title: "Our Warehouses — Scrapify | Nationwide Collection Network" },
      { name: "description", content: "Explore Scrapify's network of professional scrap collection warehouses. Find a facility near you for secure and sustainable recycling." },
      { property: "og:title", content: "Scrapify Warehouses" },
      { property: "og:description", content: "India's smartest doorstep scrap pickup network has professional processing hubs across major cities." },
    ],
  }),
  component: WarehousesPage,
});

interface WarehouseData {
  id: number;
  name: string;
  address: string;
  city: {
    name: string;
    state: {
      name: string;
    };
  };
  area: string;
  zone: string;
  code: string;
  status: boolean;
  capacity?: string;
}

function WarehousesPage() {
  const [warehouses, setWarehouses] = useState<WarehouseData[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("/api/warehouses")
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          setWarehouses(data.data);
        }
      })
      .catch((err) => console.error("Error fetching warehouses:", err))
      .finally(() => setLoading(false));
  }, []);

  return (
    <main className="min-h-screen bg-background text-foreground">
      <Navbar />
      
      {/* Hero Section */}
      <section className="relative overflow-hidden pt-32 pb-16">
        <div className="absolute inset-0 -z-10 gradient-primary opacity-10" />
        <div className="absolute -top-20 right-0 -z-10 h-72 w-72 rounded-full bg-primary/20 blur-3xl" />
        <div className="absolute -bottom-20 left-0 -z-10 h-72 w-72 rounded-full bg-accent/20 blur-3xl" />

        <div className="mx-auto w-[min(1100px,94%)] text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="inline-flex items-center gap-2 rounded-full border border-border/60 bg-background/70 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep backdrop-blur"
          >
            <Warehouse className="h-3.5 w-3.5" />
            Processing Network
          </motion.div>
          <h1 className="mt-5 text-4xl md:text-6xl font-extrabold tracking-tight">
            Our <span className="text-primary-deep">Warehouses.</span>
          </h1>
          <p className="mx-auto mt-4 max-w-2xl text-base md:text-lg text-muted-foreground">
            Strategically located processing hubs ensuring efficient, safe, and sustainable scrap management across India.
          </p>
        </div>
      </section>

      {/* Grid Section */}
      <section className="pb-24">
        <div className="mx-auto w-[min(1100px,94%)]">
          {loading ? (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {[1, 2, 3, 4, 5, 6].map((i) => (
                <div key={i} className="h-64 animate-pulse rounded-3xl bg-muted" />
              ))}
            </div>
          ) : warehouses.length > 0 ? (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {warehouses.map((w, i) => (
                <motion.div
                  key={w.id}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5, delay: i * 0.05 }}
                  className="group relative flex flex-col justify-between overflow-hidden rounded-3xl border border-border/60 bg-card p-6 shadow-soft transition-spring hover:shadow-glow hover:-translate-y-1"
                >
                  <div>
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex h-12 w-12 items-center justify-center rounded-2xl gradient-primary text-primary-foreground shadow-soft">
                        <Warehouse className="h-6 w-6" />
                      </div>
                      <div className="rounded-full bg-primary-deep/10 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-primary-deep">
                        {w.code || `WH-${w.id}`}
                      </div>
                    </div>

                    <h3 className="mt-5 text-xl font-bold leading-tight group-hover:text-primary-deep transition-colors">
                      {w.name}
                    </h3>
                    
                    <div className="mt-4 space-y-3">
                      <div className="flex items-start gap-3 text-sm text-muted-foreground">
                        <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-primary-deep" />
                        <span>{w.address}</span>
                      </div>
                      <div className="flex items-center gap-3 text-sm text-muted-foreground">
                        <Globe className="h-4 w-4 shrink-0 text-primary-deep" />
                        <span>{w.city.name}, {w.city.state.name}</span>
                      </div>
                      {(w.area || w.zone) && (
                        <div className="flex items-center gap-3 text-sm text-muted-foreground">
                          <Info className="h-4 w-4 shrink-0 text-primary-deep" />
                          <span>{w.area || w.zone}</span>
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="mt-8 flex items-center justify-between border-t border-border/60 pt-4">
                    <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-navy">
                      <Shield className="h-3.5 w-3.5" />
                      Verified Hub
                    </div>
                    {w.capacity && (
                        <span className="text-xs font-medium text-muted-foreground">
                            Cap: {w.capacity}
                        </span>
                    )}
                  </div>
                </motion.div>
              ))}
            </div>
          ) : (
            <div className="rounded-3xl border border-dashed border-border p-20 text-center">
              <Warehouse className="mx-auto h-12 w-12 text-muted-foreground/30" />
              <h3 className="mt-4 text-lg font-bold text-muted-foreground">No warehouses found</h3>
              <p className="text-sm text-muted-foreground">We are rapidly expanding. Check back soon!</p>
            </div>
          )}
        </div>
      </section>

      <Footer />
    </main>
  );
}
