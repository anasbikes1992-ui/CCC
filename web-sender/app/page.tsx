import Link from "next/link";

export default function LandingPage() {
  return (
    <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col items-center justify-center px-4 py-20 text-center animate-fade-up">
      <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-accent/10 ring-1 ring-accent/15">
        <svg width="40" height="40" viewBox="0 0 32 32" fill="none" aria-hidden>
          <rect x="3" y="8" width="18" height="14" rx="2" stroke="currentColor" strokeWidth="1.8" className="text-accent"/>
          <path d="M21 12h4l4 4v6h-8V12Z" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" className="text-accent"/>
          <circle cx="9" cy="24" r="2.5" stroke="currentColor" strokeWidth="1.8" className="text-accent"/>
          <circle cx="23" cy="24" r="2.5" stroke="currentColor" strokeWidth="1.8" className="text-accent"/>
        </svg>
      </div>
      <h1 className="text-5xl font-extrabold tracking-tight md:text-6xl text-foreground">
        Colombo Cargo Connect
      </h1>
      <p className="mt-6 max-w-2xl text-lg text-muted">
        Sri Lanka's most reliable hub-to-hub freight platform. 
        Send your parcels with fixed routes, per-package pricing, and real-time tracking.
      </p>
      
      <div className="mt-10 flex flex-wrap justify-center gap-4">
        <Link href="/login" className="rounded-xl bg-accent px-8 py-3.5 text-base font-semibold text-white shadow-lg transition hover:brightness-110 active:scale-[0.97]">
          Log In
        </Link>
        <Link href="/register" className="rounded-xl border border-line bg-surface px-8 py-3.5 text-base font-semibold text-foreground shadow-sm transition hover:bg-background active:scale-[0.97]">
          Create Account
        </Link>
      </div>
    </main>
  );
}
