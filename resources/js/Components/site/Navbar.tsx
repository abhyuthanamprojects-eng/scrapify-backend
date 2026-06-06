import { motion } from "framer-motion";
import { Link } from "@tanstack/react-router";
import { Menu, X } from "lucide-react";
import { useState } from "react";
import logo from "@/assets/scrapify-logo-05.svg";

const navItems = [
  { label: "Services", href: "#services" },
  { label: "How It Works", href: "#how" },
  { label: "Warehouses", href: "/warehouses" },
  { label: "FAQs", href: "#faqs" },
];

export function Navbar() {
  const [open, setOpen] = useState(false);

  return (
    <motion.header
      initial={{ y: -40, opacity: 0 }}
      animate={{ y: 0, opacity: 1 }}
      transition={{ duration: 0.6, ease: "easeOut" }}
      className="fixed top-0 left-0 right-0 z-50"
    >
      <div className="mx-auto mt-3 w-[min(1200px,94%)] rounded-2xl border border-border/60 bg-background/70 px-4 py-2.5 backdrop-blur-xl shadow-soft">
        <div className="flex items-center justify-between gap-4">
          <Link to="/" className="flex items-center gap-2">
            <img src={logo} alt="Scrapify" className="h-10 w-auto" />
          </Link>

          <nav className="hidden md:flex items-center gap-1">
            {navItems.map((n) => (
              n.href.startsWith("/") ? (
                <Link
                  key={n.href}
                  to={n.href}
                  className="relative px-4 py-2 text-sm font-semibold text-foreground/75 hover:text-foreground transition-smooth group"
                >
                  {n.label}
                  <span className="absolute left-4 right-4 -bottom-0.5 h-0.5 origin-left scale-x-0 bg-primary transition-transform duration-300 group-hover:scale-x-100" />
                </Link>
              ) : (
                <a
                  key={n.href}
                  href={n.href}
                  className="relative px-4 py-2 text-sm font-semibold text-foreground/75 hover:text-foreground transition-smooth group"
                >
                  {n.label}
                  <span className="absolute left-4 right-4 -bottom-0.5 h-0.5 origin-left scale-x-0 bg-primary transition-transform duration-300 group-hover:scale-x-100" />
                </a>
              )
            ))}
          </nav>

          <div className="hidden md:flex items-center gap-2">
            <a
              href="#download"
              className="inline-flex items-center gap-2 rounded-full gradient-primary px-5 py-2.5 text-sm font-bold text-primary-foreground shadow-soft hover:shadow-glow transition-spring hover:-translate-y-0.5"
            >
              Login / App
            </a>
          </div>

          <button
            onClick={() => setOpen(!open)}
            className="md:hidden inline-flex h-10 w-10 items-center justify-center rounded-xl bg-secondary"
            aria-label="menu"
          >
            {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
        </div>

        {open && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: "auto", opacity: 1 }}
            className="md:hidden overflow-hidden mt-3 pt-3 border-t border-border/60"
          >
            <div className="flex flex-col gap-1">
              {navItems.map((n) => (
                n.href.startsWith("/") ? (
                  <Link
                    key={n.href}
                    to={n.href}
                    onClick={() => setOpen(false)}
                    className="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-secondary"
                  >
                    {n.label}
                  </Link>
                ) : (
                  <a
                    key={n.href}
                    href={n.href}
                    onClick={() => setOpen(false)}
                    className="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-secondary"
                  >
                    {n.label}
                  </a>
                )
              ))}
              <a
                href="#download"
                className="mt-2 rounded-full gradient-primary px-4 py-2.5 text-center text-sm font-bold text-primary-foreground"
              >
                Login / App
              </a>
            </div>
          </motion.div>
        )}
      </div>
    </motion.header>
  );
}
