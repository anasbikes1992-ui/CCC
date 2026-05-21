'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  LayoutDashboard, Package, Truck, Users, UserCog,
  MapPin, Route, Car, DollarSign, Bell, LogOut,
  ChevronRight, Boxes, AlertTriangle, MessageSquare,
} from 'lucide-react';
import { useAuth } from './AuthProvider';
import { logout } from '@/lib/api';
import { useRouter } from 'next/navigation';

const NAV_SECTIONS = [
  {
    label: 'Overview',
    items: [
      { href: '/',              label: 'Dashboard',     icon: LayoutDashboard },
    ],
  },
  {
    label: 'Operations',
    items: [
      { href: '/parcels',       label: 'Parcels',       icon: Package },
      { href: '/trips',         label: 'Trips',         icon: Truck },
    ],
  },
  {
    label: 'People',
    items: [
      { href: '/users',         label: 'Users',         icon: Users },
      { href: '/drivers',       label: 'Drivers',       icon: UserCog },
    ],
  },
  {
    label: 'Fleet & Network',
    items: [
      { href: '/hubs',          label: 'Hubs',          icon: MapPin },
      { href: '/routes',        label: 'Routes',        icon: Route },
      { href: '/lorries',       label: 'Lorries',       icon: Car },
    ],
  },
  {
    label: 'Finance',
    items: [
      { href: '/pricing',       label: 'Pricing Matrix', icon: DollarSign },
    ],
  },
  {
    label: 'Support',
    items: [
      { href: '/disputes',      label: 'Disputes',      icon: AlertTriangle },
      { href: '/tickets',       label: 'Tickets',       icon: MessageSquare },
    ],
  },
  {
    label: 'Comms',
    items: [
      { href: '/notifications', label: 'Notifications', icon: Bell },
    ],
  },
];

export default function Sidebar() {
  const pathname  = usePathname();
  const { user, clearAuth } = useAuth();
  const router    = useRouter();

  const isActive = (href: string) =>
    href === '/' ? pathname === '/' : pathname.startsWith(href);

  const handleLogout = async () => {
    await logout();
    clearAuth();
    router.push('/login');
  };

  return (
    <aside
      className="sidebar"
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        width: 240,
        height: '100vh',
        background: 'var(--bg-secondary)',
        borderRight: '1px solid var(--border)',
        display: 'flex',
        flexDirection: 'column',
        zIndex: 50,
        overflowY: 'auto',
      }}
    >
      {/* Logo */}
      <div style={{ padding: '20px 16px 14px', borderBottom: '1px solid var(--border)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          <div style={{
            width: 34, height: 34, borderRadius: 8,
            background: 'linear-gradient(135deg, #6366f1, #4f46e5)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
          }}>
            <Boxes size={18} color="white" />
          </div>
          <div>
            <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--text-primary)', lineHeight: 1.2 }}>
              CCC Admin
            </div>
            <div style={{ fontSize: 10, color: 'var(--text-muted)', lineHeight: 1.2 }}>
              Operations Portal
            </div>
          </div>
        </div>
      </div>

      {/* Nav */}
      <nav style={{ flex: 1, padding: '12px 10px', overflowY: 'auto' }}>
        {NAV_SECTIONS.map((section) => (
          <div key={section.label} style={{ marginBottom: 20 }}>
            <div style={{
              fontSize: 10, fontWeight: 600, color: 'var(--text-muted)',
              letterSpacing: '0.08em', textTransform: 'uppercase',
              padding: '0 6px', marginBottom: 4,
            }}>
              {section.label}
            </div>
            {section.items.map((item) => {
              const Icon = item.icon;
              const active = isActive(item.href);
              return (
                <Link key={item.href} href={item.href} className={`sidebar-link ${active ? 'active' : ''}`}>
                  <Icon size={16} />
                  <span style={{ flex: 1 }}>{item.label}</span>
                  {active && <ChevronRight size={12} style={{ opacity: 0.5 }} />}
                </Link>
              );
            })}
          </div>
        ))}
      </nav>

      {/* User footer */}
      <div style={{ padding: '12px 10px', borderTop: '1px solid var(--border)' }}>
        {user && (
          <div style={{
            padding: '10px 12px', borderRadius: 8,
            background: 'rgba(255,255,255,0.03)', marginBottom: 6,
          }}>
            <div style={{ fontSize: 12, fontWeight: 600, color: 'var(--text-primary)' }}>
              {user.full_name}
            </div>
            <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 1 }}>
              {user.email ?? user.phone}
            </div>
            <div style={{
              display: 'inline-block', marginTop: 4, fontSize: 10, fontWeight: 600,
              padding: '2px 7px', borderRadius: 10,
              background: 'var(--accent-dim)', color: 'var(--accent-light)',
            }}>
              {user.role}
            </div>
          </div>
        )}
        <button onClick={handleLogout} className="sidebar-link" style={{ width: '100%', background: 'none', border: 'none' }}>
          <LogOut size={15} />
          Sign out
        </button>
      </div>
    </aside>
  );
}
