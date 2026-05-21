// lib/utils.ts — shared utility functions

import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { format, parseISO } from 'date-fns';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatDate(iso?: string | null, fmt = 'dd MMM yyyy, HH:mm'): string {
  if (!iso) return '—';
  try {
    return format(parseISO(iso), fmt);
  } catch {
    return iso;
  }
}

export function formatDateShort(iso?: string | null): string {
  return formatDate(iso, 'dd MMM yyyy');
}

export function formatCurrency(amount?: number | string | null): string {
  const num = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
  return `LKR ${num.toLocaleString('en-LK', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
}

export function formatNumber(n?: number | null): string {
  if (n == null) return '0';
  return n.toLocaleString();
}

// ── Status config ─────────────────────────────────────────────────────────────

export const PARCEL_STATUS_CONFIG: Record<string, { label: string; color: string; bg: string }> = {
  BOOKED:                      { label: 'Booked',             color: '#94a3b8', bg: 'rgba(148,163,184,0.15)' },
  LABEL_PRINTED:               { label: 'Label Printed',      color: '#60a5fa', bg: 'rgba(96,165,250,0.15)' },
  PICKED_UP:                   { label: 'Picked Up',          color: '#a78bfa', bg: 'rgba(167,139,250,0.15)' },
  RECEIVED_AT_ORIGIN_HUB:      { label: 'At Origin Hub',      color: '#34d399', bg: 'rgba(52,211,153,0.15)' },
  LOADED_ON_LORRY:             { label: 'Loaded',             color: '#fbbf24', bg: 'rgba(251,191,36,0.15)' },
  IN_TRANSIT:                  { label: 'In Transit',         color: '#f59e0b', bg: 'rgba(245,158,11,0.15)' },
  ARRIVED_AT_DESTINATION_HUB:  { label: 'At Dest. Hub',       color: '#6ee7b7', bg: 'rgba(110,231,183,0.15)' },
  OUT_FOR_DELIVERY:            { label: 'Out for Delivery',   color: '#818cf8', bg: 'rgba(129,140,248,0.15)' },
  DELIVERED:                   { label: 'Delivered',          color: '#10b981', bg: 'rgba(16,185,129,0.15)' },
  DELIVERY_FAILED:             { label: 'Failed',             color: '#ef4444', bg: 'rgba(239,68,68,0.15)' },
  CANCELLED:                   { label: 'Cancelled',          color: '#6b7280', bg: 'rgba(107,114,128,0.15)' },
  RETURNED_TO_ORIGIN:          { label: 'Returned',           color: '#f97316', bg: 'rgba(249,115,22,0.15)' },
};

export const TRIP_STATUS_CONFIG: Record<string, { label: string; color: string; bg: string }> = {
  SCHEDULED:  { label: 'Scheduled',  color: '#60a5fa', bg: 'rgba(96,165,250,0.15)' },
  LOADING:    { label: 'Loading',    color: '#fbbf24', bg: 'rgba(251,191,36,0.15)' },
  IN_TRANSIT: { label: 'In Transit', color: '#f59e0b', bg: 'rgba(245,158,11,0.15)' },
  ARRIVED:    { label: 'Arrived',    color: '#34d399', bg: 'rgba(52,211,153,0.15)' },
  UNLOADING:  { label: 'Unloading',  color: '#a78bfa', bg: 'rgba(167,139,250,0.15)' },
  COMPLETED:  { label: 'Completed',  color: '#10b981', bg: 'rgba(16,185,129,0.15)' },
  CANCELLED:  { label: 'Cancelled',  color: '#ef4444', bg: 'rgba(239,68,68,0.15)' },
};

export const USER_ROLES = [
  { value: 'customer',       label: 'Customer' },
  { value: 'driver',         label: 'Driver' },
  { value: 'hub_staff',      label: 'Hub Staff' },
  { value: 'hub_manager',    label: 'Hub Manager' },
  { value: 'ops_admin',      label: 'Ops Admin' },
  { value: 'finance_admin',  label: 'Finance Admin' },
  { value: 'support_admin',  label: 'Support Admin' },
  { value: 'admin_super',    label: 'Super Admin' },
];

export const PARCEL_STATUSES = Object.keys(PARCEL_STATUS_CONFIG);
export const TRIP_STATUSES   = Object.keys(TRIP_STATUS_CONFIG);
