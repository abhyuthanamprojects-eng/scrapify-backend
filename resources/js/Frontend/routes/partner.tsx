import { createFileRoute, Link } from "@tanstack/react-router";
import { motion } from "framer-motion";
import { useState, useRef } from "react";
import { Handshake, Upload, CheckCircle2, IndianRupee, TrendingUp, Users, ArrowLeft, FileText } from "lucide-react";
import { z } from "zod";
import { toast } from "sonner";
import { Navbar } from "@/components/site/Navbar";
import { Footer } from "@/components/site/Footer";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

export const Route = createFileRoute("/partner")({
  head: () => ({
    meta: [
      { title: "Become a Partner — Scrapify | Earn with Doorstep Scrap Pickup" },
      {
        name: "description",
        content:
          "Join Scrapify's collection partner network. Submit your Aadhaar, PAN and GST details to start earning by collecting scrap in your area.",
      },
      { property: "og:title", content: "Become a Scrapify Partner" },
      {
        property: "og:description",
        content:
          "Earn ₹30,000+ per month by joining India's smartest doorstep scrap pickup network.",
      },
    ],
  }),
  component: PartnerPage,
});

const partnerSchema = z.object({
  fullName: z.string().trim().min(2, "Name must be at least 2 characters").max(100),
  businessName: z.string().trim().min(2, "Business/Firm name is required").max(255),
  mobile: z.string().trim().regex(/^[6-9]\d{9}$/, "Enter a valid 10-digit mobile number"),
  email: z.string().trim().email("Enter a valid email").max(255),
  city: z.string().trim().min(2, "City is required").max(80),
  state: z.string().trim().min(2, "State is required").max(80),
  pincode: z.string().trim().regex(/^\d{6}$/, "Pincode must be 6 digits"),
  address: z.string().trim().min(10, "Address must be at least 10 characters").max(300),
  aadhaar: z.string().trim().regex(/^\d{12}$/, "Aadhaar must be 12 digits"),
  pan: z.string().trim().regex(/^[A-Z]{5}[0-9]{4}[A-Z]$/, "Enter a valid PAN (e.g. ABCDE1234F)"),
  gst: z
    .string()
    .trim()
    .max(15)
    .optional()
    .or(z.literal(""))
    .refine(
      (v) => !v || /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(v),
      "Enter a valid GSTIN or leave blank",
    ),
  experience: z.string().trim().max(500).optional().or(z.literal("")),
});

const benefits = [
  { icon: IndianRupee, title: "Earn ₹30k+/month", desc: "Daily payouts, no commission cuts." },
  { icon: TrendingUp, title: "Steady Bookings", desc: "We send leads — you collect scrap." },
  { icon: Users, title: "Trusted Network", desc: "Join 500+ verified collection partners." },
];

function PartnerPage() {
  const [aadhaarFile, setAadhaarFile] = useState<File | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const formRef = useRef<HTMLFormElement>(null);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const data = {
      fullName: String(fd.get("fullName") || ""),
      businessName: String(fd.get("businessName") || ""),
      mobile: String(fd.get("mobile") || ""),
      email: String(fd.get("email") || ""),
      city: String(fd.get("city") || ""),
      state: String(fd.get("state") || ""),
      pincode: String(fd.get("pincode") || ""),
      address: String(fd.get("address") || ""),
      aadhaar: String(fd.get("aadhaar") || "").replace(/\s+/g, ""),
      pan: String(fd.get("pan") || "").toUpperCase(),
      gst: String(fd.get("gst") || "").toUpperCase(),
      experience: String(fd.get("experience") || ""),
    };

    const parsed = partnerSchema.safeParse(data);
    if (!parsed.success) {
      toast.error(parsed.error.issues[0]?.message ?? "Please check the form");
      return;
    }
    if (!aadhaarFile) {
      toast.error("Please upload your Aadhaar card photo");
      return;
    }
    if (aadhaarFile.size > 5 * 1024 * 1024) {
      toast.error("Aadhaar photo must be under 5 MB");
      return;
    }

    setSubmitting(true);
    try {
      const apiPayload = {
        full_name: data.fullName,
        business_name: data.businessName,
        phone: data.mobile,
        email: data.email,
        city: data.city,
        state: data.state,
        pincode: data.pincode,
        address: data.address,
        aadhaar_number: data.aadhaar,
        pan_number: data.pan,
        gst_number: data.gst || null,
        opening_location_name: data.city,
      };

      const res = await fetch("/api/channel-partner/registration/request", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(apiPayload),
      });

      const result = await res.json();

      if (!res.ok) {
        // Handle validation errors from backend
        if (result.errors) {
          const firstError = Object.values(result.errors).flat()[0] as string;
          toast.error(firstError || "Please check the form and try again.");
        } else {
          toast.error(result.message || "Something went wrong. Please try again.");
        }
        return;
      }

      toast.success("Application received! Our team will contact you within 48 hours.");
      setSubmitted(true);
      formRef.current?.reset();
      setAadhaarFile(null);
    } catch (err) {
      toast.error("Network error. Please check your connection and try again.");
    } finally {
      setSubmitting(false);
    }
  };

  if (submitted) {
    return (
      <main className="min-h-screen bg-background">
        <Navbar />
        <section className="pt-32 pb-20">
          <div className="mx-auto w-[min(700px,94%)] text-center">
            <motion.div
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 0.5 }}
              className="rounded-3xl border border-border/60 bg-card p-10 shadow-soft"
            >
              <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 mb-6">
                <CheckCircle2 className="h-10 w-10 text-primary-deep" />
              </div>
              <h1 className="text-3xl font-extrabold text-foreground mb-4">Application Submitted!</h1>
              <p className="text-base text-muted-foreground mb-8 max-w-md mx-auto">
                Thank you for applying. Our team will review your business details and identity documents within 24-48 hours.
              </p>
              <div className="bg-primary/5 p-5 rounded-2xl border border-primary/10 text-sm text-foreground mb-8">
                <p className="font-bold mb-2">Next Steps:</p>
                <ul className="list-disc list-inside text-left space-y-1 text-muted-foreground">
                  <li>Manual verification of Aadhaar and PAN.</li>
                  <li>Phone screening by our regional manager.</li>
                  <li>Approval & Fee confirmation detail email.</li>
                </ul>
              </div>
              <Link
                to="/"
                className="inline-flex items-center gap-2 rounded-full gradient-primary px-8 py-3 text-sm font-bold text-primary-foreground shadow-soft hover:shadow-glow transition-spring hover:-translate-y-0.5"
              >
                <ArrowLeft className="h-4 w-4" />
                Back to Home
              </Link>
            </motion.div>
          </div>
        </section>
        <Footer />
      </main>
    );
  }

  return (
    <main className="min-h-screen bg-background">
      <Navbar />

      {/* Hero */}
      <section className="relative overflow-hidden pt-32 pb-12">
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
            <Handshake className="h-3.5 w-3.5" />
            Become a Partner
          </motion.div>
          <motion.h1
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="mt-5 text-4xl md:text-6xl font-extrabold tracking-tight text-foreground"
          >
            Earn with Scrapify. <span className="text-primary-deep">Be your own boss.</span>
          </motion.h1>
          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.2 }}
            className="mx-auto mt-4 max-w-2xl text-base md:text-lg text-muted-foreground"
          >
            Become a verified Scrapify collection partner. We bring you customers — you bring the
            hustle. Daily payouts, zero commission cuts.
          </motion.p>

          <div className="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-4">
            {benefits.map((b, i) => {
              const Icon = b.icon;
              return (
                <motion.div
                  key={b.title}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: 0.3 + i * 0.1 }}
                  className="rounded-2xl border border-border/60 bg-card p-5 text-left shadow-soft"
                >
                  <div className="flex h-11 w-11 items-center justify-center rounded-xl gradient-primary text-primary-foreground shadow-soft">
                    <Icon className="h-5 w-5" />
                  </div>
                  <h3 className="mt-3 font-bold text-foreground">{b.title}</h3>
                  <p className="text-sm text-muted-foreground">{b.desc}</p>
                </motion.div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Form */}
      <section className="pb-20">
        <div className="mx-auto w-[min(900px,94%)]">
          <motion.form
            ref={formRef}
            onSubmit={handleSubmit}
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
            className="rounded-3xl border border-border/60 bg-card p-6 md:p-10 shadow-soft"
          >
            <h2 className="text-2xl md:text-3xl font-extrabold text-foreground">
              Partner Application
            </h2>
            <p className="mt-1 text-sm text-muted-foreground">
              Fill the details below. All information is kept strictly confidential.
            </p>

            <div className="mt-8 grid grid-cols-1 md:grid-cols-2 gap-5">
              <Field label="Full Name" name="fullName" placeholder="Ramesh Kumar" required />
              <Field label="Business / Firm Name" name="businessName" placeholder="Kumar E-Waste Solutions" required />
              <Field label="Mobile Number" name="mobile" type="tel" placeholder="9876543210" maxLength={10} required />
              <Field label="Email" name="email" type="email" placeholder="you@example.com" required />
              <Field label="City" name="city" placeholder="New Delhi" required />
              <Field label="State" name="state" placeholder="Delhi" required />
              <Field label="Pincode" name="pincode" placeholder="110001" maxLength={6} required />
              <div />{/* spacer for grid alignment */}
              <div className="md:col-span-2">
                <Label htmlFor="address" className="text-sm font-semibold">
                  Full Address <span className="text-destructive">*</span>
                </Label>
                <Textarea
                  id="address"
                  name="address"
                  required
                  rows={3}
                  maxLength={300}
                  placeholder="House no., street, area, landmark"
                  className="mt-2"
                />
              </div>
            </div>

            <div className="mt-8 rounded-2xl bg-secondary/60 p-5 md:p-6">
              <div className="flex items-center gap-2">
                <FileText className="h-4 w-4 text-primary-deep" />
                <h3 className="text-sm font-bold uppercase tracking-widest text-navy">
                  KYC Documents
                </h3>
              </div>

              <div className="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                <Field
                  label="Aadhaar Number"
                  name="aadhaar"
                  placeholder="1234 5678 9012"
                  maxLength={14}
                  required
                />
                <Field
                  label="PAN Number"
                  name="pan"
                  placeholder="ABCDE1234F"
                  maxLength={10}
                  className="uppercase"
                  required
                />
                <Field
                  label="GSTIN (Optional)"
                  name="gst"
                  placeholder="22ABCDE1234F1Z5"
                  maxLength={15}
                  className="uppercase"
                />
                <div>
                  <Label className="text-sm font-semibold">
                    Aadhaar Card Photo <span className="text-destructive">*</span>
                  </Label>
                  <label
                    htmlFor="aadhaarFile"
                    className="mt-2 flex cursor-pointer items-center justify-between gap-3 rounded-md border border-dashed border-input bg-background px-3 py-2.5 text-sm hover:border-primary transition-colors"
                  >
                    <span className="flex items-center gap-2 truncate">
                      <Upload className="h-4 w-4 text-primary-deep shrink-0" />
                      <span className="truncate text-muted-foreground">
                        {aadhaarFile ? aadhaarFile.name : "Upload JPG/PNG/PDF (max 5 MB)"}
                      </span>
                    </span>
                    {aadhaarFile && <CheckCircle2 className="h-4 w-4 text-primary-deep shrink-0" />}
                  </label>
                  <input
                    id="aadhaarFile"
                    type="file"
                    accept="image/png,image/jpeg,application/pdf"
                    className="sr-only"
                    onChange={(e) => setAadhaarFile(e.target.files?.[0] ?? null)}
                  />
                </div>
              </div>
            </div>

            <div className="mt-6">
              <Label htmlFor="experience" className="text-sm font-semibold">
                Previous Experience (Optional)
              </Label>
              <Textarea
                id="experience"
                name="experience"
                rows={3}
                maxLength={500}
                placeholder="Tell us about your scrap collection or logistics experience"
                className="mt-2"
              />
            </div>

            <p className="mt-6 text-xs text-muted-foreground">
              By submitting, you agree to our{" "}
              <Link to="/terms" className="text-primary-deep underline underline-offset-2">
                Terms & Conditions
              </Link>{" "}
              and{" "}
              <Link to="/privacy" className="text-primary-deep underline underline-offset-2">
                Privacy Policy
              </Link>
              .
            </p>

            <button
              type="submit"
              disabled={submitting}
              className="mt-6 w-full md:w-auto inline-flex items-center justify-center gap-2 rounded-full gradient-primary px-8 py-3 text-sm font-bold text-primary-foreground shadow-soft hover:shadow-glow transition-spring hover:-translate-y-0.5 disabled:opacity-60 disabled:hover:translate-y-0"
            >
              {submitting ? "Submitting…" : "Submit Application"}
            </button>

            <div className="mt-6">
              <Link
                to="/"
                className="inline-flex items-center gap-2 text-sm font-semibold text-primary-deep hover:underline"
              >
                <ArrowLeft className="h-4 w-4" />
                Back to Home
              </Link>
            </div>
          </motion.form>
        </div>
      </section>

      <Footer />
    </main>
  );
}

function Field({
  label,
  name,
  type = "text",
  placeholder,
  required,
  maxLength,
  className,
}: {
  label: string;
  name: string;
  type?: string;
  placeholder?: string;
  required?: boolean;
  maxLength?: number;
  className?: string;
}) {
  return (
    <div>
      <Label htmlFor={name} className="text-sm font-semibold">
        {label} {required && <span className="text-destructive">*</span>}
      </Label>
      <Input
        id={name}
        name={name}
        type={type}
        placeholder={placeholder}
        required={required}
        maxLength={maxLength}
        className={`mt-2 ${className ?? ""}`}
      />
    </div>
  );
}
