'use client';

import { PARCEL_STATUS_CONFIG, TRIP_STATUS_CONFIG } from '@/lib/utils';

export function ParcelStatusBadge({ status }: { status: string }) {
  const cfg = PARCEL_STATUS_CONFIG[status] ?? { label: status, color: '#94a3b8', bg: 'rgba(148,163,184,0.15)' };
  return (
    <span className="badge" style={{ color: cfg.color, background: cfg.bg }}>
      {cfg.label}
    </span>
  );
}

export function TripStatusBadge({ status }: { status: string }) {
  const cfg = TRIP_STATUS_CONFIG[status] ?? { label: status, color: '#94a3b8', bg: 'rgba(148,163,184,0.15)' };
  return (
    <span className="badge" style={{ color: cfg.color, background: cfg.bg }}>
      {cfg.label}
    </span>
  );
}

export function RoleBadge({ role }: { role: string }) {
  const COLOR_MAP: Record<string, { color: string; bg: string }> = {
    admin_super:   { color: '#f59e0b', bg: 'rgba(245,158,11,0.15)' },
    ops_admin:     { color: '#818cf8', bg: 'rgba(129,140,248,0.15)' },
    finance_admin: { color: '#34d399', bg: 'rgba(52,211,153,0.15)' },
    support_admin: { color: '#60a5fa', bg: 'rgba(96,165,250,0.15)' },
    hub_manager:   { color: '#a78bfa', bg: 'rgba(167,139,250,0.15)' },
    hub_staff:     { color: '#6ee7b7', bg: 'rgba(110,231,183,0.15)' },
    driver:        { color: '#fbbf24', bg: 'rgba(251,191,36,0.15)' },
    customer:      { color: '#94a3b8', bg: 'rgba(148,163,184,0.15)' },
  };
  const cfg = COLOR_MAP[role] ?? { color: '#94a3b8', bg: 'rgba(148,163,184,0.15)' };
  return (
    <span className="badge" style={{ color: cfg.color, background: cfg.bg }}>
      {role.replace(/_/g, ' ')}
    </span>
  );
}

export function ActiveBadge({ active }: { active: boolean }) {
  return (
    <span className="badge" style={{
      color: active ? '#10b981' : '#ef4444',
      background: active ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)',
    }}>
      {active ? 'Active' : 'Inactive'}
    </span>
  );
}
