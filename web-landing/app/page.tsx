"use client";

// Component inspired by: https://21st.dev/community/components/isaiahbjork/agent-plan
import dynamic from "next/dynamic";
import { motion } from "framer-motion";
import { ArrowRight, CircleCheck, Smartphone, MapPin, Building2, ShieldCheck } from "lucide-react";

const CargoMap = dynamic(() => import("./components/cargo-map"), {
  ssr: false,
  loading: () => (
    <div className="h-[460px] animate-pulse rounded-3xl border border-black/10 bg-white/70" />
  ),
});

const APP_LINKS = [
  {
    title: "Sender Mobile App",
    href: "#",
    description: "Book, pay, and track parcels on Android.",
    icon: Smartphone,
  },
  {
    title: "Public Tracking",
    href: "https://web-tracking-sigma.vercel.app",
    description: "Track parcels live with just a parcel number.",
    icon: MapPin,
  },
  {
    title: "Driver Mobile App",
    href: "#",
    description: "Pickup, scan, and capture delivery proof.",
    icon: Smartphone,
  },
  {
    title: "Hub Console",
    href: "https://ccc-hub-seven.vercel.app",
    description: "Scan IN/OUT and manage branch inventory.",
    icon: Building2,
  },
  {
    title: "Admin Platform",
    href: "https://web-admin-rho-sepia.vercel.app",
    description: "Control routes, disputes, finance, and users.",
    icon: ShieldCheck,
  },
];

const FEATURES = [
  "Fixed route pricing with no surprise per-km math",
  "10-stage parcel tracking with QR scans",
  "Daily departures from Colombo at 6 AM and 2 PM",
  "Delivery proof: NIC, signature, and optional photo",
];

export default function Home() {
  return (
    <div className="grain-bg">
      <main className="mx-auto max-w-6xl px-6 pb-12 pt-12 md:px-10">
        <motion.section
          initial={{ opacity: 0, y: 24 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.45, ease: [0.4, 0, 0.2, 1] }}
          className="rounded-3xl border border-black/10 bg-surface px-8 py-12 shadow-2xl"
        >
          <div className="grid items-center gap-8 lg:grid-cols-2">
            <div className="space-y-5">
              <span className="inline-flex rounded-full border border-accent/20 bg-accent/10 px-4 py-1 text-sm font-semibold text-accent-ink">
                Colombo Cargo Connect
              </span>
              <h1 className="text-4xl font-bold tracking-tight text-[#121212] md:text-6xl">
                Sri Lanka&apos;s Scheduled Hub-to-Hub Freight Network
              </h1>
              <p className="max-w-2xl text-lg leading-8 text-black/70">
                Transparent parcel delivery built for Sri Lankan SMEs, retailers, and growing
                exporters. One booking, full visibility, predictable pricing.
              </p>
              <div className="flex flex-wrap gap-4">
                <a
                  href="https://web-sender.vercel.app"
                  className="inline-flex items-center gap-2 rounded-xl bg-accent px-5 py-3 text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#0a50bd]"
                >
                  Book a Parcel <ArrowRight size={18} />
                </a>
                <a
                  href="https://web-tracking-sigma.vercel.app"
                  className="inline-flex items-center gap-2 rounded-xl border border-black/15 bg-white px-5 py-3 transition-all duration-200 hover:-translate-y-0.5 hover:bg-black/5"
                >
                  Track Parcel <MapPin size={18} />
                </a>
              </div>
            </div>

            <motion.div
              whileHover={{ y: -4, boxShadow: "0 20px 40px rgba(0,0,0,0.12)" }}
              transition={{ type: "spring", stiffness: 300, damping: 20 }}
              className="rounded-2xl border border-black/10 bg-white p-6"
            >
              <h2 className="mb-4 text-lg font-semibold">How CCC Wins</h2>
              <ul className="space-y-3">
                {FEATURES.map((feature) => (
                  <li key={feature} className="flex items-start gap-3 text-sm leading-6 text-black/80">
                    <CircleCheck size={18} className="mt-1 text-[#238a55]" />
                    <span>{feature}</span>
                  </li>
                ))}
              </ul>
            </motion.div>
          </div>
        </motion.section>

        <motion.section
          initial={{ opacity: 0, y: 24 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{ duration: 0.45, ease: [0.4, 0, 0.2, 1] }}
          className="mt-12 space-y-4"
        >
          <div className="flex items-end justify-between gap-4">
            <div>
              <h2 className="text-3xl font-bold">Live Network Map</h2>
              <p className="text-black/65">
                Cargo moves from Colombo across Sri Lanka with spinning package markers powered by
                Leaflet.
              </p>
            </div>
          </div>
          <CargoMap />
        </motion.section>

        <motion.section
          initial={{ opacity: 0, y: 24 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{ duration: 0.45, ease: [0.4, 0, 0.2, 1] }}
          className="mt-14"
        >
          <h2 className="mb-5 text-3xl font-bold">Apps and Portals</h2>
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {APP_LINKS.map((link, index) => {
              const Icon = link.icon;
              return (
                <motion.a
                  key={link.title}
                  href={link.href}
                  target={link.href.startsWith("http") ? "_blank" : undefined}
                  rel={link.href.startsWith("http") ? "noopener noreferrer" : undefined}
                  initial={{ opacity: 0, y: 16 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ delay: index * 0.08, duration: 0.3 }}
                  whileHover={{ y: -4, boxShadow: "0 20px 40px rgba(0,0,0,0.12)" }}
                  className="rounded-xl border border-black/10 bg-surface p-5 shadow-md transition-all duration-200"
                >
                  <div className="mb-3 flex items-center gap-3">
                    <span className="rounded-lg bg-accent/10 p-2 text-accent">
                      <Icon size={20} />
                    </span>
                    <h3 className="font-semibold">{link.title}</h3>
                  </div>
                  <p className="text-sm text-black/70">{link.description}</p>
                </motion.a>
              );
            })}
          </div>
        </motion.section>
      </main>

      <motion.footer
        initial={{ opacity: 0, y: 24 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true, amount: 0.2 }}
        transition={{ duration: 0.45, ease: [0.4, 0, 0.2, 1] }}
        className="mt-10 border-t border-black/10 bg-[#131722] text-white"
      >
        <div className="mx-auto grid max-w-6xl gap-8 px-6 py-12 md:grid-cols-4 md:px-10">
          <div>
            <h3 className="text-lg font-semibold">Colombo Cargo Connect</h3>
            <p className="mt-3 text-sm text-white/70">
              A reliable logistics backbone for Sri Lanka, built for consistent schedules and full
              tracking transparency.
            </p>
          </div>
          <div>
            <h4 className="font-medium">Platform</h4>
            <ul className="mt-3 space-y-2 text-sm text-white/70">
              <li><a href="https://web-sender.vercel.app">Sender Portal</a></li>
              <li><a href="https://web-tracking-sigma.vercel.app">Tracking</a></li>
              <li><a href="https://ccc-hub-seven.vercel.app">Hub Console</a></li>
              <li><a href="https://web-admin-rho-sepia.vercel.app">Admin Console</a></li>
            </ul>
          </div>
          <div>
            <h4 className="font-medium">Business</h4>
            <ul className="mt-3 space-y-2 text-sm text-white/70">
              <li>Pricing by route and package size</li>
              <li>COD and insurance options</li>
              <li>WhatsApp delivery updates</li>
              <li>Proof-based delivery completion</li>
            </ul>
          </div>
          <div>
            <h4 className="font-medium">Contact</h4>
            <ul className="mt-3 space-y-2 text-sm text-white/70">
              <li>Colombo, Sri Lanka</li>
              <li>support@colombocargo.lk</li>
              <li>+94 11 000 0000</li>
            </ul>
          </div>
        </div>
        <div className="border-t border-white/10 px-6 py-4 text-center text-xs text-white/60 md:px-10">
          © 2026 Colombo Cargo Connect. All rights reserved.
        </div>
      </motion.footer>
    </div>
  );
}
