"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

export default function Home() {
  const [parcelNumber, setParcelNumber] = useState("");
  const router = useRouter();

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const value = parcelNumber.trim();
    if (!value) return;
    router.push(`/${encodeURIComponent(value)}`);
  }

  return (
    <main className="mx-auto flex w-full max-w-4xl flex-1 flex-col justify-center gap-8 px-4 py-12 md:px-8 animate-fade-up">

      {/* Hero */}
      <div className="text-center">
        <div className="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-accent/10 ring-1 ring-accent/15">
          <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden>
            <rect x="3" y="8" width="18" height="14" rx="2" stroke="currentColor" strokeWidth="1.8" className="text-accent"/>
            <path d="M21 12h4l4 4v6h-8V12Z" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" className="text-accent"/>
            <circle cx="9" cy="24" r="2.5" stroke="currentColor" strokeWidth="1.8" className="text-accent"/>
            <circle cx="23" cy="24" r="2.5" stroke="currentColor" strokeWidth="1.8" className="text-accent"/>
          </svg>
        </div>
        <p className="text-[10px] font-bold uppercase tracking-[0.3em] text-muted">Colombo Cargo Connect</p>
        <h1 className="mt-2 text-4xl font-bold leading-tight md:text-5xl">Track Your Shipment</h1>
        <p className="mt-3 text-sm text-muted md:text-base">
          Enter your parcel number for live status, hub scans, and delivery progress.
        </p>
      </div>

      {/* Search Card */}
      <section className="rounded-3xl border border-line bg-surface p-8 shadow-[0_8px_40px_rgba(16,42,67,0.1)]">
        <form onSubmit={onSubmit} className="flex flex-col gap-4 sm:flex-row">
          <input
            value={parcelNumber}
            onChange={(event) => setParcelNumber(event.target.value)}
            placeholder="CCC-YYYYMMDD-NNNNNN-X"
            autoFocus
            className="flex-1 rounded-xl border border-line bg-white px-4 py-3 font-mono text-sm outline-none ring-accent/20 transition placeholder:text-muted/40 focus:ring-2"
          />
          <button
            type="submit"
            disabled={!parcelNumber.trim()}
            className="rounded-xl bg-accent px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:brightness-110 active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-50"
          >
            Track Parcel
          </button>
        </form>

        <div className="mt-6 flex flex-wrap items-center gap-6 text-xs text-muted">
          <span className="flex items-center gap-1.5">
            <span className="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse-dot" />
            Live status updates
          </span>
          <span className="flex items-center gap-1.5">
            <span className="h-1.5 w-1.5 rounded-full bg-accent-2" />
            Hub-to-hub tracking
          </span>
          <span className="flex items-center gap-1.5">
            <span className="h-1.5 w-1.5 rounded-full bg-accent" />
            Delivery confirmation
          </span>
        </div>
      </section>

      {/* Route network hint */}
      <div className="flex flex-wrap justify-center gap-3 text-xs">
        {["CMB", "KDY", "GAL", "MTR", "JFN", "KLN"].map((hub) => (
          <span
            key={hub}
            className="rounded-xl border border-line bg-surface px-3 py-1.5 font-mono font-semibold text-muted"
          >
            {hub}
          </span>
        ))}
        <span className="rounded-xl border border-dashed border-line bg-transparent px-3 py-1.5 text-muted/50">
          + more hubs
        </span>
      </div>
    </main>
  );
}
