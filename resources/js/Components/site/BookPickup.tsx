import { useMemo, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import {
  MapPin,
  Phone,
  User,
  Calendar,
  Clock,
  Check,
  ArrowRight,
  ArrowLeft,
  IndianRupee,
  Sparkles,
  PartyPopper,
} from "lucide-react";
import { toast } from "sonner";
const paper = "/images/new/cat-paper.png";
const metal = "/images/new/cat-metal.png";
const plastic = "/images/new/cat-plastic.png";
const ewaste = "/images/new/cat-ewaste.png";
const glass = "/images/new/cat-glass.png";
const battery = "/images/new/cat-battery.png";

type CategoryKey = "ewaste" | "metal" | "plastic" | "paper" | "glass" | "battery";

const CATEGORIES: {
  key: CategoryKey;
  name: string;
  hint: string;
  pricePerKg: number;
  img: string;
  tone: string;
}[] = [
  { key: "ewaste", name: "E-Waste", hint: "Phones, laptops, wires", pricePerKg: 60, img: ewaste, tone: "from-primary/20 to-primary-glow/10" },
  { key: "metal", name: "Metals", hint: "Iron, steel, aluminium", pricePerKg: 45, img: metal, tone: "from-rupee/20 to-rupee/5" },
  { key: "battery", name: "Batteries", hint: "Inverter, UPS, lead-acid", pricePerKg: 90, img: battery, tone: "from-navy/15 to-navy/5" },
  { key: "plastic", name: "Plastics", hint: "Bottles, containers", pricePerKg: 18, img: plastic, tone: "from-mint/40 to-mint/10" },
  { key: "paper", name: "Paper & Cardboard", hint: "Newspapers, boxes", pricePerKg: 12, img: paper, tone: "from-cream to-secondary" },
  { key: "glass", name: "Glass", hint: "Bottles, jars", pricePerKg: 8, img: glass, tone: "from-primary/15 to-mint/20" },
];

const TIME_SLOTS = ["9–11 AM", "11 AM–1 PM", "1–3 PM", "3–5 PM", "5–7 PM"];
const STEPS = ["Location", "Scrap Type", "Schedule", "Estimate"];

type Selections = Partial<Record<CategoryKey, number>>;

function todayPlus(days: number) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d;
}

export function BookPickup() {
  const [step, setStep] = useState(0);
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [pincode, setPincode] = useState("");
  const [address, setAddress] = useState("");
  const [selections, setSelections] = useState<Selections>({});
  const [date, setDate] = useState<string>(todayPlus(1).toISOString().slice(0, 10));
  const [slot, setSlot] = useState<string>(TIME_SLOTS[1]);
  const [submitted, setSubmitted] = useState(false);

  const lineItems = useMemo(
    () =>
      CATEGORIES.filter((c) => (selections[c.key] ?? 0) > 0).map((c) => ({
        ...c,
        qty: selections[c.key] ?? 0,
        amount: (selections[c.key] ?? 0) * c.pricePerKg,
      })),
    [selections],
  );

  const totalKg = lineItems.reduce((s, l) => s + l.qty, 0);
  const subtotal = lineItems.reduce((s, l) => s + l.amount, 0);
  const pickupFee = subtotal > 0 && subtotal < 200 ? 30 : 0;
  const estimate = Math.max(0, subtotal - pickupFee);

  const updateQty = (key: CategoryKey, delta: number) => {
    setSelections((prev) => {
      const next = Math.max(0, (prev[key] ?? 0) + delta);
      const copy = { ...prev };
      if (next === 0) delete copy[key];
      else copy[key] = next;
      return copy;
    });
  };

  const setQty = (key: CategoryKey, val: number) => {
    setSelections((prev) => {
      const next = Math.max(0, Math.min(500, Math.floor(val) || 0));
      const copy = { ...prev };
      if (next === 0) delete copy[key];
      else copy[key] = next;
      return copy;
    });
  };

  const validate = (s: number): string | null => {
    if (s === 0) {
      if (!name.trim()) return "Please enter your name";
      if (!/^[6-9]\d{9}$/.test(phone)) return "Enter a valid 10-digit mobile number";
      if (!/^\d{6}$/.test(pincode)) return "Enter a valid 6-digit pincode";
      if (address.trim().length < 8) return "Please enter your full address";
    }
    if (s === 1 && totalKg === 0) return "Add at least one scrap item";
    if (s === 2) {
      if (!date) return "Pick a date";
      if (!slot) return "Pick a time slot";
    }
    return null;
  };

  const next = () => {
    const err = validate(step);
    if (err) {
      toast.error(err);
      return;
    }
    setStep((s) => Math.min(STEPS.length - 1, s + 1));
  };
  const prev = () => setStep((s) => Math.max(0, s - 1));

  const submit = () => {
    setSubmitted(true);
    toast.success("Pickup booked! A collector will reach out shortly.");
  };

  const reset = () => {
    setStep(0);
    setSelections({});
    setSubmitted(false);
  };

  const progress = ((step + 1) / STEPS.length) * 100;

  return (
    <section id="book" className="py-20 md:py-28 relative overflow-hidden">
      <div className="absolute inset-0 bg-grid opacity-40 pointer-events-none" />
      <div className="relative mx-auto w-[min(1100px,94%)]">
        <div className="text-center">
          <motion.span
            initial={{ opacity: 0, y: 10 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="inline-flex items-center gap-2 rounded-full bg-primary/15 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary-deep"
          >
            <Sparkles className="h-3.5 w-3.5" /> Book a Pickup
          </motion.span>
          <motion.h2
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
            className="mt-3 text-3xl md:text-5xl font-extrabold text-navy"
          >
            Get an instant <span className="text-gradient-primary">cash estimate</span>
          </motion.h2>
          <p className="mx-auto mt-3 max-w-xl text-muted-foreground">
            Three quick steps. Tell us where, what, and when — we'll show you exactly
            how much you'll earn.
          </p>
        </div>

        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-80px" }}
          transition={{ duration: 0.7 }}
          className="mt-10 rounded-[2rem] bg-card shadow-elegant border border-border/60 overflow-hidden"
        >
          {/* Stepper */}
          <div className="px-5 md:px-8 pt-6 md:pt-8">
            <div className="flex items-center justify-between gap-2">
              {STEPS.map((label, i) => {
                const active = i === step;
                const done = i < step || submitted;
                return (
                  <div key={label} className="flex-1 flex items-center gap-2">
                    <div className="flex flex-col items-center gap-1.5 min-w-0">
                      <motion.div
                        animate={{
                          scale: active ? 1.1 : 1,
                          backgroundColor: done
                            ? "var(--primary)"
                            : active
                              ? "var(--primary-deep)"
                              : "var(--muted)",
                        }}
                        transition={{ duration: 0.3 }}
                        className="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-primary-foreground shadow-soft"
                      >
                        {done ? <Check className="h-4 w-4" /> : i + 1}
                      </motion.div>
                      <span
                        className={`hidden sm:block text-[11px] font-semibold uppercase tracking-wider truncate ${
                          active || done ? "text-navy" : "text-muted-foreground"
                        }`}
                      >
                        {label}
                      </span>
                    </div>
                    {i < STEPS.length - 1 && (
                      <div className="flex-1 h-1 rounded-full bg-muted overflow-hidden">
                        <motion.div
                          initial={{ width: 0 }}
                          animate={{ width: i < step || submitted ? "100%" : "0%" }}
                          transition={{ duration: 0.5 }}
                          className="h-full bg-primary"
                        />
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
            <div className="mt-4 h-1 w-full overflow-hidden rounded-full bg-muted sm:hidden">
              <motion.div
                animate={{ width: `${progress}%` }}
                transition={{ duration: 0.4 }}
                className="h-full gradient-primary"
              />
            </div>
          </div>

          {/* Body */}
          <div className="p-5 md:p-8">
            <AnimatePresence mode="wait">
              {submitted ? (
                <motion.div
                  key="success"
                  initial={{ opacity: 0, scale: 0.95 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0 }}
                  className="py-10 text-center"
                >
                  <motion.div
                    initial={{ scale: 0 }}
                    animate={{ scale: 1 }}
                    transition={{ type: "spring", stiffness: 200, damping: 12 }}
                    className="mx-auto flex h-20 w-20 items-center justify-center rounded-full gradient-primary text-primary-foreground shadow-glow"
                  >
                    <PartyPopper className="h-10 w-10" />
                  </motion.div>
                  <h3 className="mt-5 text-2xl md:text-3xl font-extrabold text-navy">
                    Pickup confirmed!
                  </h3>
                  <p className="mt-2 text-muted-foreground">
                    Hi {name.split(" ")[0] || "there"}, your collector will arrive on{" "}
                    <span className="font-semibold text-navy">
                      {new Date(date).toLocaleDateString("en-IN", {
                        weekday: "short",
                        day: "numeric",
                        month: "short",
                      })}
                    </span>{" "}
                    between <span className="font-semibold text-navy">{slot}</span>.
                  </p>
                  <div className="mt-6 inline-flex items-center gap-2 rounded-2xl bg-primary/10 px-5 py-3 text-primary-deep font-bold">
                    <IndianRupee className="h-5 w-5" />
                    Estimated earnings: ₹ {estimate.toLocaleString("en-IN")}
                  </div>
                  <div className="mt-6">
                    <button
                      onClick={reset}
                      className="rounded-full border-2 border-primary px-6 py-2.5 text-sm font-bold text-primary-deep hover:bg-primary/10 transition-smooth"
                    >
                      Book another pickup
                    </button>
                  </div>
                </motion.div>
              ) : step === 0 ? (
                <StepLocation
                  key="loc"
                  name={name}
                  setName={setName}
                  phone={phone}
                  setPhone={setPhone}
                  pincode={pincode}
                  setPincode={setPincode}
                  address={address}
                  setAddress={setAddress}
                />
              ) : step === 1 ? (
                <StepCategory
                  key="cat"
                  selections={selections}
                  updateQty={updateQty}
                  setQty={setQty}
                  totalKg={totalKg}
                  subtotal={subtotal}
                />
              ) : step === 2 ? (
                <StepSchedule
                  key="sched"
                  date={date}
                  setDate={setDate}
                  slot={slot}
                  setSlot={setSlot}
                />
              ) : (
                <StepEstimate
                  key="est"
                  name={name}
                  phone={phone}
                  pincode={pincode}
                  address={address}
                  date={date}
                  slot={slot}
                  lineItems={lineItems}
                  totalKg={totalKg}
                  subtotal={subtotal}
                  pickupFee={pickupFee}
                  estimate={estimate}
                />
              )}
            </AnimatePresence>

            {/* Footer actions */}
            {!submitted && (
              <div className="mt-8 flex items-center justify-between gap-3 border-t border-border/60 pt-6">
                <button
                  onClick={prev}
                  disabled={step === 0}
                  className="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-navy hover:bg-muted disabled:opacity-30 disabled:pointer-events-none transition-smooth"
                >
                  <ArrowLeft className="h-4 w-4" /> Back
                </button>

                {step < STEPS.length - 1 ? (
                  <button
                    onClick={next}
                    className="inline-flex items-center gap-2 rounded-full gradient-primary px-6 py-3 text-sm font-bold text-primary-foreground shadow-soft hover:shadow-glow hover:-translate-y-0.5 transition-spring"
                  >
                    Continue <ArrowRight className="h-4 w-4" />
                  </button>
                ) : (
                  <button
                    onClick={submit}
                    className="inline-flex items-center gap-2 rounded-full gradient-primary px-6 py-3 text-sm font-bold text-primary-foreground shadow-soft hover:shadow-glow hover:-translate-y-0.5 transition-spring"
                  >
                    Confirm Pickup <Check className="h-4 w-4" />
                  </button>
                )}
              </div>
            )}
          </div>
        </motion.div>
      </div>
    </section>
  );
}

/* ---------------- Steps ---------------- */

function fadeProps() {
  return {
    initial: { opacity: 0, y: 16 },
    animate: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: -16 },
    transition: { duration: 0.35 },
  };
}

function Field({
  icon,
  label,
  children,
}: {
  icon: React.ReactNode;
  label: string;
  children: React.ReactNode;
}) {
  return (
    <label className="block">
      <span className="mb-1.5 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
        <span className="text-primary-deep">{icon}</span> {label}
      </span>
      {children}
    </label>
  );
}

const inputCls =
  "w-full rounded-xl border border-input bg-background px-4 py-3 text-sm font-medium text-navy outline-none transition-smooth focus:border-primary focus:ring-2 focus:ring-primary/30";

function StepLocation(props: {
  name: string;
  setName: (v: string) => void;
  phone: string;
  setPhone: (v: string) => void;
  pincode: string;
  setPincode: (v: string) => void;
  address: string;
  setAddress: (v: string) => void;
}) {
  return (
    <motion.div {...fadeProps()} className="grid gap-4 md:grid-cols-2">
      <Field icon={<User className="h-3.5 w-3.5" />} label="Full name">
        <input
          value={props.name}
          onChange={(e) => props.setName(e.target.value)}
          placeholder="Aarav Sharma"
          maxLength={60}
          className={inputCls}
        />
      </Field>
      <Field icon={<Phone className="h-3.5 w-3.5" />} label="Mobile number">
        <input
          value={props.phone}
          onChange={(e) => props.setPhone(e.target.value.replace(/\D/g, "").slice(0, 10))}
          placeholder="98xxxxxxxx"
          inputMode="numeric"
          className={inputCls}
        />
      </Field>
      <Field icon={<MapPin className="h-3.5 w-3.5" />} label="Pincode">
        <input
          value={props.pincode}
          onChange={(e) => props.setPincode(e.target.value.replace(/\D/g, "").slice(0, 6))}
          placeholder="560001"
          inputMode="numeric"
          className={inputCls}
        />
      </Field>
      <Field icon={<MapPin className="h-3.5 w-3.5" />} label="Pickup address">
        <input
          value={props.address}
          onChange={(e) => props.setAddress(e.target.value)}
          placeholder="House / Flat, Street, Area"
          maxLength={150}
          className={inputCls}
        />
      </Field>
      <div className="md:col-span-2 flex items-start gap-3 rounded-2xl bg-mint/40 p-4 text-sm text-primary-deep">
        <MapPin className="mt-0.5 h-5 w-5 shrink-0" />
        <p>
          We currently service <strong>50+ cities</strong> across India. Your data is
          private and used only to dispatch your collector.
        </p>
      </div>
    </motion.div>
  );
}

function StepCategory(props: {
  selections: Selections;
  updateQty: (k: CategoryKey, d: number) => void;
  setQty: (k: CategoryKey, v: number) => void;
  totalKg: number;
  subtotal: number;
}) {
  return (
    <motion.div {...fadeProps()}>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {CATEGORIES.map((c) => {
          const qty = props.selections[c.key] ?? 0;
          const active = qty > 0;
          return (
            <motion.div
              key={c.key}
              whileHover={{ y: -3 }}
              className={`group relative rounded-2xl border-2 p-4 transition-spring ${
                active
                  ? "border-primary bg-primary/5 shadow-card"
                  : "border-border bg-card hover:border-primary/40"
              }`}
            >
              <div className="flex items-center gap-3">
                <div
                  className={`relative h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br ${c.tone}`}
                >
                  <img src={c.img} alt={c.name} className="absolute inset-0 h-full w-full object-contain p-1.5" />
                </div>
                <div className="min-w-0 flex-1 text-left">
                  <p className="text-sm font-bold text-navy truncate">{c.name}</p>
                  <p className="text-[11px] text-muted-foreground truncate">{c.hint}</p>
                  <p className="mt-0.5 text-xs font-bold text-primary-deep">
                    ₹ {c.pricePerKg}/kg
                  </p>
                </div>
              </div>

              <div className="mt-3 flex items-center justify-between gap-2 rounded-xl bg-muted/60 p-1">
                <button
                  onClick={() => props.updateQty(c.key, -1)}
                  className="flex h-8 w-8 items-center justify-center rounded-lg bg-card text-navy font-bold shadow-soft hover:bg-primary hover:text-primary-foreground transition-smooth"
                  aria-label={`Decrease ${c.name}`}
                >
                  −
                </button>
                <div className="flex items-center gap-1 text-navy">
                  <input
                    type="number"
                    min={0}
                    value={qty}
                    onChange={(e) => props.setQty(c.key, Number(e.target.value))}
                    className="w-14 bg-transparent text-center text-base font-extrabold outline-none"
                  />
                  <span className="text-xs font-semibold text-muted-foreground">kg</span>
                </div>
                <button
                  onClick={() => props.updateQty(c.key, 1)}
                  className="flex h-8 w-8 items-center justify-center rounded-lg gradient-primary text-primary-foreground font-bold shadow-soft hover:shadow-glow transition-smooth"
                  aria-label={`Increase ${c.name}`}
                >
                  +
                </button>
              </div>
              <AnimatePresence>
                {active && (
                  <motion.div
                    initial={{ opacity: 0, scale: 0.6 }}
                    animate={{ opacity: 1, scale: 1 }}
                    exit={{ opacity: 0, scale: 0.6 }}
                    className="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-soft"
                  >
                    <Check className="h-3.5 w-3.5" />
                  </motion.div>
                )}
              </AnimatePresence>
            </motion.div>
          );
        })}
      </div>

      <motion.div
        layout
        className="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl gradient-mint p-4"
      >
        <p className="text-sm font-semibold text-primary-deep">
          {props.totalKg > 0
            ? `${props.totalKg} kg selected`
            : "Tip: Approximate weights are okay — we re-weigh on pickup"}
        </p>
        <p className="text-base font-extrabold text-navy">
          Subtotal: ₹ {props.subtotal.toLocaleString("en-IN")}
        </p>
      </motion.div>
    </motion.div>
  );
}

function StepSchedule(props: {
  date: string;
  setDate: (v: string) => void;
  slot: string;
  setSlot: (v: string) => void;
}) {
  const days = Array.from({ length: 7 }, (_, i) => todayPlus(i));
  return (
    <motion.div {...fadeProps()} className="space-y-6">
      <div>
        <p className="mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
          <Calendar className="h-3.5 w-3.5 text-primary-deep" /> Pick a date
        </p>
        <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
          {days.map((d, i) => {
            const iso = d.toISOString().slice(0, 10);
            const active = props.date === iso;
            return (
              <button
                key={iso}
                onClick={() => props.setDate(iso)}
                className={`min-w-[80px] rounded-2xl border-2 p-3 text-center transition-spring ${
                  active
                    ? "border-primary bg-primary text-primary-foreground shadow-card -translate-y-1"
                    : "border-border bg-card text-navy hover:border-primary/50"
                }`}
              >
                <p className="text-[10px] font-bold uppercase opacity-80">
                  {i === 0 ? "Today" : i === 1 ? "Tomorrow" : d.toLocaleDateString("en-IN", { weekday: "short" })}
                </p>
                <p className="text-xl font-extrabold">{d.getDate()}</p>
                <p className="text-[10px] font-semibold opacity-80">
                  {d.toLocaleDateString("en-IN", { month: "short" })}
                </p>
              </button>
            );
          })}
        </div>
      </div>

      <div>
        <p className="mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
          <Clock className="h-3.5 w-3.5 text-primary-deep" /> Pick a time slot
        </p>
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
          {TIME_SLOTS.map((s) => {
            const active = props.slot === s;
            return (
              <button
                key={s}
                onClick={() => props.setSlot(s)}
                className={`rounded-xl border-2 px-3 py-2.5 text-sm font-bold transition-spring ${
                  active
                    ? "border-primary bg-primary text-primary-foreground shadow-card"
                    : "border-border bg-card text-navy hover:border-primary/50"
                }`}
              >
                {s}
              </button>
            );
          })}
        </div>
      </div>
    </motion.div>
  );
}

function StepEstimate(props: {
  name: string;
  phone: string;
  pincode: string;
  address: string;
  date: string;
  slot: string;
  lineItems: { key: CategoryKey; name: string; qty: number; pricePerKg: number; amount: number; img: string }[];
  totalKg: number;
  subtotal: number;
  pickupFee: number;
  estimate: number;
}) {
  return (
    <motion.div {...fadeProps()} className="grid gap-5 md:grid-cols-5">
      <div className="md:col-span-3 space-y-4">
        <div className="rounded-2xl border border-border/70 p-5">
          <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
            Pickup details
          </p>
          <div className="mt-2 space-y-1 text-sm">
            <p className="font-bold text-navy">{props.name}</p>
            <p className="text-muted-foreground">+91 {props.phone}</p>
            <p className="text-muted-foreground">
              {props.address}, {props.pincode}
            </p>
            <p className="mt-2 inline-flex items-center gap-1.5 rounded-full bg-mint/50 px-3 py-1 text-xs font-bold text-primary-deep">
              <Calendar className="h-3 w-3" />
              {new Date(props.date).toLocaleDateString("en-IN", {
                weekday: "short",
                day: "numeric",
                month: "short",
              })}
              <span className="opacity-50">•</span>
              <Clock className="h-3 w-3" /> {props.slot}
            </p>
          </div>
        </div>

        <div className="rounded-2xl border border-border/70 p-5">
          <p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">
            Items ({props.totalKg} kg)
          </p>
          <ul className="mt-3 divide-y divide-border/60">
            {props.lineItems.map((l) => (
              <li key={l.key} className="flex items-center gap-3 py-2.5">
                <img src={l.img} alt={l.name} className="h-9 w-9 rounded-lg bg-mint/30 object-contain p-1" />
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-bold text-navy truncate">{l.name}</p>
                  <p className="text-xs text-muted-foreground">
                    {l.qty} kg × ₹ {l.pricePerKg}
                  </p>
                </div>
                <p className="text-sm font-extrabold text-navy">
                  ₹ {l.amount.toLocaleString("en-IN")}
                </p>
              </li>
            ))}
          </ul>
        </div>
      </div>

      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        transition={{ delay: 0.15 }}
        className="md:col-span-2 rounded-2xl gradient-primary p-6 text-primary-foreground shadow-elegant relative overflow-hidden"
      >
        <div className="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl" />
        <p className="text-xs font-bold uppercase tracking-widest opacity-80">
          Instant estimate
        </p>
        <div className="mt-3 flex items-baseline gap-1">
          <IndianRupee className="h-7 w-7" />
          <motion.span
            key={props.estimate}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className="text-5xl font-extrabold"
          >
            {props.estimate.toLocaleString("en-IN")}
          </motion.span>
        </div>
        <p className="mt-1 text-xs opacity-80">Paid via UPI on pickup</p>

        <div className="mt-5 space-y-1.5 text-sm">
          <Row label="Subtotal" value={`₹ ${props.subtotal.toLocaleString("en-IN")}`} />
          <Row
            label={props.pickupFee ? "Pickup fee (orders < ₹200)" : "Pickup fee"}
            value={props.pickupFee ? `− ₹ ${props.pickupFee}` : "FREE"}
          />
          <div className="my-2 h-px bg-white/20" />
          <Row label="You earn" value={`₹ ${props.estimate.toLocaleString("en-IN")}`} bold />
        </div>

        <p className="mt-4 text-[11px] opacity-75 leading-relaxed">
          * Final amount based on weight measured on-site. No hidden charges. Cancel anytime.
        </p>
      </motion.div>
    </motion.div>
  );
}

function Row({ label, value, bold = false }: { label: string; value: string; bold?: boolean }) {
  return (
    <div className={`flex items-center justify-between ${bold ? "text-base font-extrabold" : "opacity-90"}`}>
      <span>{label}</span>
      <span>{value}</span>
    </div>
  );
}
