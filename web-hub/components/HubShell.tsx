'use client';

import { useEffect, useRef } from 'react';
import { usePathname, useRouter } from 'next/navigation';
import Link from 'next/link';
import { useAuth } from './AuthProvider';
import {
  ScanLine, ArrowDownToLine, ArrowUpFromLine, Package, FileText,
  LogOut, Menu, X, Warehouse,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useState } from 'react';

const NAV = [
  { href: '/scan',      label: 'Scan',      icon: ScanLine },
  { href: '/inbound',   label: 'Inbound',   icon: ArrowDownToLine },
  { href: '/outbound',  label: 'Outbound',  icon: ArrowUpFromLine },
  { href: '/inventory', label: 'Inventory', icon: Package },
  { href: '/manifest',  label: 'Manifest',  icon: FileText },
];

// Session timeout: 30 min of inactivity → redirect to login
const IDLE_MS = 30 * 60 * 1000;

export default function HubShell({ children }: { children: React.ReactNode }) {
  const { user, token, isLoading, signOut } = useAuth();
  const router   = useRouter();
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const idleTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Auth guard
  useEffect(() => {
    if (!isLoading && !token) router.replace('/login');
  }, [isLoading, token, router]);

  // Idle session timeout
  useEffect(() => {
    const reset = () => {
      if (idleTimer.current) clearTimeout(idleTimer.current);
      idleTimer.current = setTimeout(() => {
        signOut();
      }, IDLE_MS);
    };
    reset();
    window.addEventListener('mousemove', reset);
    window.addEventListener('keydown', reset);
    window.addEventListener('touchstart', reset);
    return () => {
      if (idleTimer.current) clearTimeout(idleTimer.current);
      window.removeEventListener('mousemove', reset);
      window.removeEventListener('keydown', reset);
      window.removeEventListener('touchstart', reset);
    };
  }, [signOut]);

  if (isLoading || !token) return null;

  const hubName = user?.hub_staff?.hub?.name ?? 'Hub Console';

  return (
    <div className="min-h-screen flex bg-slate-50">
      {/* Sidebar — desktop */}
      <aside className="hidden md:flex flex-col w-60 bg-slate-900 text-white shrink-0">
        <div className="flex items-center gap-2 px-5 py-4 border-b border-slate-800">
          <Warehouse size={20} className="text-indigo-400" />
          <div>
            <p className="text-xs text-slate-400 font-medium uppercase tracking-wider">CCC Hub</p>
            <p className="text-sm font-semibold truncate">{hubName}</p>
          </div>
        </div>

        <nav className="flex-1 py-4 space-y-0.5 px-2">
          {NAV.map(({ href, label, icon: Icon }) => (
            <Link
              key={href}
              href={href}
              className={cn(
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                pathname.startsWith(href)
                  ? 'bg-indigo-600 text-white'
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              )}
            >
              <Icon size={18} />
              {label}
            </Link>
          ))}
        </nav>

        <div className="px-2 pb-4 border-t border-slate-800 pt-3">
          <div className="px-3 py-2 text-xs text-slate-400 truncate">{user?.name}</div>
          <button
            onClick={signOut}
            className="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm text-slate-300 hover:bg-slate-800 hover:text-white transition-colors"
          >
            <LogOut size={18} />
            Sign out
          </button>
        </div>
      </aside>

      {/* Mobile top bar */}
      <div className="md:hidden fixed top-0 inset-x-0 z-30 bg-slate-900 text-white flex items-center justify-between px-4 py-3">
        <div className="flex items-center gap-2">
          <Warehouse size={18} className="text-indigo-400" />
          <span className="font-semibold text-sm">{hubName}</span>
        </div>
        <button onClick={() => setOpen(o => !o)} className="p-1">
          {open ? <X size={20} /> : <Menu size={20} />}
        </button>
      </div>

      {/* Mobile drawer */}
      {open && (
        <div className="md:hidden fixed inset-0 z-20 bg-slate-900 text-white pt-14 px-4 flex flex-col">
          <nav className="flex-1 space-y-1 py-4">
            {NAV.map(({ href, label, icon: Icon }) => (
              <Link
                key={href}
                href={href}
                onClick={() => setOpen(false)}
                className={cn(
                  'flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium',
                  pathname.startsWith(href)
                    ? 'bg-indigo-600 text-white'
                    : 'text-slate-300 hover:bg-slate-800'
                )}
              >
                <Icon size={20} />
                {label}
              </Link>
            ))}
          </nav>
          <button
            onClick={signOut}
            className="flex items-center gap-3 px-3 py-3 text-slate-300 hover:text-white mb-8"
          >
            <LogOut size={18} /> Sign out
          </button>
        </div>
      )}

      {/* Main content */}
      <main className="flex-1 overflow-y-auto md:h-screen pt-14 md:pt-0 p-4 md:p-6">
        {children}
      </main>
    </div>
  );
}
