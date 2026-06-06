import { createFileRoute, Link } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { Mail, Phone, MapPin, MessageCircle, ArrowLeft, Send } from "lucide-react";
import { useRef, useState } from "react";
import { z } from "zod";
import { toast } from "sonner";
import { Navbar } from "@/components/site/Navbar";
import { Footer } from "@/components/site/Footer";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

export const Route = createFileRoute("/contact")({
  head: () => ({
    meta: [
      { title: "Contact Us — Scrapify | Get in Touch" },
      { name: "description", content: "Reach the Scrapify team for support, partnerships or feedback. Call, email or send us a message." },
      { property: "og:title", content: "Contact Scrapify" },
      { property: "og:description", content: "Get in touch with India's smartest doorstep scrap pickup service." },
    ],
  }),
  component: ContactPage,
});

const contactSchema = z.object({
  name: z.string().trim().min(2, "Name is too short").max(100),
  email: z.string().trim().email("Enter a valid email").max(255),
  subject: z.string().trim().min(3, "Subject is too short").max(150),
  message: z.string().trim().min(10, "Message must be at least 10 characters").max(1000),
});

const channels = [
  { icon: Phone, title: "Call Us", value: "+91 11 3574 8627", href: "tel:+911135748627", note: "Mon–Sat, 9 AM – 7 PM" },
  { icon: Mail, title: "Email Us", value: "support@scrapify.in", href: "mailto:support@scrapify.in", note: "Reply within 24 hours" },
  { icon: MessageCircle, title: "WhatsApp / Mobile", value: "+91 98702 91813", href: "https://wa.me/919870291813", note: "Chat anytime" },
  { icon: MapPin, title: "Office", value: "E-44/3 Okhla Industrial Area Phase - 2, New Delhi - 110020", href: "#", note: "Visit by appointment" },
];

function ContactPage() {
  const [submitting, setSubmitting] = useState(false);
  const formRef = useRef<HTMLFormElement>(null);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const data = {
      name: String(fd.get("name") || ""),
      email: String(fd.get("email") || ""),
      subject: String(fd.get("subject") || ""),
      message: String(fd.get("message") || ""),
    };
    const parsed = contactSchema.safeParse(data);
    if (!parsed.success) {
      toast.error(parsed.error.issues[0]?.message ?? "Please check the form");
      return;
    }
    setSubmitting(true);
    await new Promise((r) => setTimeout(r, 700));
    setSubmitting(false);
    toast.success("Message sent! We'll get back to you within 24 hours.");
    formRef.current?.reset();
  };

  return (
    <main className="min-h-screen bg-background">
      <Navbar />
      <section className="relative overflow-hidden pt-32 pb-12">
        <div className="absolute inset-0 -z-10 gradient-primary opacity-10" />
        <div className="absolute -top-20 right-0 -z-10 h-72 w-72 rounded-full bg-primary/20 blur-3xl" />
        <div className="absolute -bottom-20 left-0 -z-10 h-72 w-72 rounded-full bg-accent/20 blur-3xl" />

        <div className="mx-auto w-[min(1100px,94%)] text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6 }}
            className="inline-flex items-center gap-2 rounded-full border border-border/60 bg-background/70 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep backdrop-blur"
          >
            <MessageCircle className="h-3.5 w-3.5" />
            Contact Us
          </motion.div>
          <h1 className="mt-5 text-4xl md:text-6xl font-extrabold tracking-tight text-foreground">
            We'd love to <span className="text-primary-deep">hear from you.</span>
          </h1>
          <p className="mx-auto mt-4 max-w-2xl text-base md:text-lg text-muted-foreground">
            Questions, feedback, partnership ideas — our team is here to help.
          </p>
        </div>
      </section>

      <section className="pb-20">
        <div className="mx-auto w-[min(1100px,94%)] grid gap-6 lg:grid-cols-5">
          {/* Channels */}
          <div className="lg:col-span-2 grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
            {channels.map((c, i) => {
              const Icon = c.icon;
              return (
                <motion.a
                  key={c.title} href={c.href} target={c.href.startsWith("http") ? "_blank" : undefined} rel="noopener noreferrer"
                  initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }} transition={{ duration: 0.5, delay: i * 0.05 }}
                  className="group block rounded-2xl border border-border/60 bg-card p-5 shadow-soft hover:shadow-glow transition-spring hover:-translate-y-0.5"
                >
                  <div className="flex items-start gap-4">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl gradient-primary text-primary-foreground shadow-soft">
                      <Icon className="h-5 w-5" />
                    </div>
                    <div>
                      <p className="text-xs font-bold uppercase tracking-widest text-navy">{c.title}</p>
                      <p className="mt-1 font-semibold text-foreground">{c.value}</p>
                      <p className="text-xs text-muted-foreground">{c.note}</p>
                    </div>
                  </div>
                </motion.a>
              );
            })}
          </div>

          {/* Form */}
          <motion.form
            ref={formRef} onSubmit={handleSubmit}
            initial={{ opacity: 0, y: 24 }} whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }} transition={{ duration: 0.5 }}
            className="lg:col-span-3 rounded-3xl border border-border/60 bg-card p-6 md:p-10 shadow-soft"
          >
            <h2 className="text-2xl md:text-3xl font-extrabold text-foreground">Send us a message</h2>
            <p className="mt-1 text-sm text-muted-foreground">We respond within 24 hours.</p>

            <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <Label htmlFor="name" className="text-sm font-semibold">Name *</Label>
                <Input id="name" name="name" required maxLength={100} placeholder="Your name" className="mt-2" />
              </div>
              <div>
                <Label htmlFor="email" className="text-sm font-semibold">Email *</Label>
                <Input id="email" name="email" type="email" required maxLength={255} placeholder="you@example.com" className="mt-2" />
              </div>
              <div className="md:col-span-2">
                <Label htmlFor="subject" className="text-sm font-semibold">Subject *</Label>
                <Input id="subject" name="subject" required maxLength={150} placeholder="How can we help?" className="mt-2" />
              </div>
              <div className="md:col-span-2">
                <Label htmlFor="message" className="text-sm font-semibold">Message *</Label>
                <Textarea id="message" name="message" required rows={5} maxLength={1000} placeholder="Tell us a bit more…" className="mt-2" />
              </div>
            </div>

            <button
              type="submit" disabled={submitting}
              className="mt-6 inline-flex items-center justify-center gap-2 rounded-full gradient-primary px-8 py-3 text-sm font-bold text-primary-foreground shadow-soft hover:shadow-glow transition-spring hover:-translate-y-0.5 disabled:opacity-60"
            >
              <Send className="h-4 w-4" />
              {submitting ? "Sending…" : "Send Message"}
            </button>

            <div className="mt-6">
              <Link to="/" className="inline-flex items-center gap-2 text-sm font-semibold text-primary-deep hover:underline">
                <ArrowLeft className="h-4 w-4" /> Back to Home
              </Link>
            </div>
          </motion.form>
        </div>
      </section>

      <Footer />
    </main>
  );
}
