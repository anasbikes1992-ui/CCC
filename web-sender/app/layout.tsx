import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import Link from "next/link";
import "./globals.css";

const geistSans = Geist({ variable: "--font-geist-sans", subsets: ["latin"] });
const geistMono = Geist_Mono({ variable: "--font-geist-mono", subsets: ["latin"] });

export const metadata: Metadata = {
  title: "CCC Sender Portal",
  description: "Book parcels, get labels, and share tracking links.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}>
      <body className="flex min-h-full flex-col">
        <header className="sticky top-0 z-50 border-b border-line bg-surface/90 backdrop-blur-sm">
          <div className="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 md:px-8">
            <Link href="/" className="flex items-center gap-3 group">
              <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-accent text-[9px] font-black tracking-tighter text-white shadow-sm">
                CCC
              </span>
              <div className="leading-none">
                <span className="block text-sm font-semibold transition-colors group-hover:text-accent">
                  Colombo Cargo Connect
                </span>
                <span className="block text-[10px] uppercase tracking-widest text-muted">
                  Sender Portal
                </span>
              </div>
            </Link>
            <nav className="flex items-center gap-2 text-sm text-muted">
              <Link href="/" className="rounded-full px-3 py-1.5 transition-colors hover:bg-accent/10 hover:text-foreground">Home</Link>
              <Link href="/login" className="rounded-full px-3 py-1.5 transition-colors hover:bg-accent/10 hover:text-foreground">Log in</Link>
              <Link href="/register" className="rounded-full px-3 py-1.5 transition-colors hover:bg-accent/10 hover:text-foreground">Register</Link>
              <Link href="/dashboard" className="rounded-full px-3 py-1.5 transition-colors hover:bg-accent/10 hover:text-foreground">Dashboard</Link>
              <Link href="/book" className="rounded-full px-3 py-1.5 transition-colors hover:bg-accent/10 hover:text-foreground">Book</Link>
              <a
                href={process.env.NEXT_PUBLIC_TRACKING_URL ?? "http://localhost:3001"}
                target="_blank"
                rel="noreferrer"
                className="flex items-center gap-1.5 rounded-full px-3 py-1.5 transition-colors hover:bg-accent/10 hover:text-foreground"
              >
                Track Parcel
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden>
                  <path d="M2.5 9.5L9.5 2.5M9.5 2.5H4.5M9.5 2.5V7.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </a>
            </nav>
          </div>
          <div className="h-px bg-gradient-to-r from-transparent via-accent/40 to-transparent" />
        </header>

        {children}

        <footer className="mt-auto border-t border-line">
          <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 md:px-8">
            <span className="text-xs text-muted">Colombo Cargo Connect — Hub-to-Hub Logistics</span>
            <span className="text-xs text-muted">Sri Lanka 🇱🇰</span>
          </div>
        </footer>
      </body>
    </html>
  );
}
