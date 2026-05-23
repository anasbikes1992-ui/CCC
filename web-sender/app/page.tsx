import Link from "next/link";
import { ArrowRight, BookOpen, LogIn, QrCode, ShieldCheck, Truck, UserPlus } from "lucide-react";

export default function LandingPage() {
  const trackingUrl = process.env.NEXT_PUBLIC_TRACKING_URL ?? "http://localhost:3001";

  return (
    <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col px-4 py-16 md:px-8 animate-fade-up">
      <section className="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
        <div>
          <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-muted shadow-sm">
            Colombo Cargo Connect
          </div>
          <h1 className="max-w-3xl text-5xl font-black tracking-tight text-foreground md:text-7xl">
            Book, track, and manage parcels on fixed Sri Lankan routes.
          </h1>
          <p className="mt-6 max-w-2xl text-lg leading-8 text-muted">
            Use one connected portal for sender signup, booking, parcel tracking, and operations handoff. The admin login stays separate and is intentionally not linked here.
          </p>

          <div className="mt-10 flex flex-wrap gap-4">
            <Link href="/login" className="inline-flex items-center gap-2 rounded-xl bg-accent px-6 py-3 text-base font-semibold text-white shadow-lg transition hover:brightness-110 active:scale-[0.97]">
              <LogIn size={18} /> Log In
            </Link>
            <Link href="/register" className="inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-6 py-3 text-base font-semibold text-foreground shadow-sm transition hover:bg-background active:scale-[0.97]">
              <UserPlus size={18} /> Create Account
            </Link>
            <Link href="/book" className="inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-6 py-3 text-base font-semibold text-foreground shadow-sm transition hover:bg-background active:scale-[0.97]">
              <BookOpen size={18} /> Book Parcel
            </Link>
          </div>
        </div>

        <div className="rounded-4xl border border-line bg-surface p-6 shadow-[0_20px_80px_rgba(16,42,67,0.12)]">
          <div className="grid gap-4 sm:grid-cols-2">
            {[
              { href: '/login', label: 'Sender Login', icon: LogIn, desc: 'Sign in to your sender account.' },
              { href: '/register', label: 'Sender Register', icon: UserPlus, desc: 'Create a new customer account.' },
              { href: '/dashboard', label: 'Dashboard', icon: Truck, desc: 'See your active shipments.' },
              { href: '/book', label: 'Book Parcel', icon: BookOpen, desc: 'Create a new shipment booking.' },
            ].map((item) => {
              const Icon = item.icon;
              return (
                <Link key={item.href} href={item.href} className="group rounded-2xl border border-line bg-white/70 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-accent/10 text-accent ring-1 ring-accent/15">
                      <Icon size={20} />
                    </div>
                    <ArrowRight size={16} className="mt-1 text-muted transition group-hover:translate-x-1 group-hover:text-foreground" />
                  </div>
                  <p className="mt-4 text-sm font-bold text-foreground">{item.label}</p>
                  <p className="mt-1 text-sm leading-6 text-muted">{item.desc}</p>
                </Link>
              );
            })}
          </div>

          <a href={trackingUrl} target="_blank" rel="noreferrer" className="mt-4 flex items-center justify-between rounded-2xl border border-dashed border-line bg-background px-4 py-4 text-sm text-muted transition hover:bg-white">
            <span className="inline-flex items-center gap-2 font-semibold text-foreground"><QrCode size={16} className="text-accent" /> Public tracking page</span>
            <ArrowRight size={16} />
          </a>
        </div>
      </section>

      <section className="mt-12 grid gap-4 md:grid-cols-3">
        {[
          { title: 'Fixed routes', text: 'Colombo ↔ Kandy style corridors with predictable schedules.' },
          { title: 'Parcel proof', text: 'QR tracking, scan history, and delivery verification on every booking.' },
          { title: 'No admin login link', text: 'Operations access stays separate from the public sender flow.' },
        ].map((card) => (
          <div key={card.title} className="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <ShieldCheck className="h-5 w-5 text-accent" aria-hidden="true" />
            <h2 className="mt-4 text-sm font-bold text-foreground">{card.title}</h2>
            <p className="mt-2 text-sm leading-6 text-muted">{card.text}</p>
          </div>
        ))}
      </section>
    </main>
  );
}
