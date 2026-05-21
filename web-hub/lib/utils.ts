import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { format, formatDistanceToNow } from 'date-fns';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatDate(d: string | null | undefined): string {
  if (!d) return '—';
  return format(new Date(d), 'dd MMM yyyy, HH:mm');
}

export function fromNow(d: string | null | undefined): string {
  if (!d) return '—';
  return formatDistanceToNow(new Date(d), { addSuffix: true });
}

export const PARCEL_STATUS_COLORS: Record<string, string> = {
  BOOKED:                        'bg-slate-100 text-slate-700',
  LABEL_PRINTED:                 'bg-slate-100 text-slate-700',
  PICKED_UP:                     'bg-blue-100 text-blue-700',
  RECEIVED_AT_ORIGIN_HUB:        'bg-indigo-100 text-indigo-700',
  LOADED_ON_LORRY:               'bg-violet-100 text-violet-700',
  IN_TRANSIT:                    'bg-amber-100 text-amber-700',
  ARRIVED_AT_DESTINATION_HUB:    'bg-teal-100 text-teal-700',
  OUT_FOR_DELIVERY:              'bg-orange-100 text-orange-700',
  DELIVERED:                     'bg-green-100 text-green-700',
  DELIVERY_FAILED:               'bg-red-100 text-red-700',
  CANCELLED:                     'bg-gray-100 text-gray-500',
};

export const SCAN_EVENT_LABELS: Record<string, string> = {
  RECEIVED_AT_ORIGIN_HUB:        'Receive at Origin Hub',
  RECEIVED_AT_DESTINATION_HUB:   'Receive at Destination Hub',
  LOADED_ON_LORRY:               'Load on Lorry',
  OUT_FOR_DELIVERY:              'Out for Delivery',
};

/** Play a short beep. tone = 'success' | 'error' */
export function beep(tone: 'success' | 'error' = 'success'): void {
  try {
    const ctx = new (window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.frequency.value = tone === 'success' ? 880 : 220;
    osc.type = 'sine';
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.3);
  } catch { /* audio not available */ }
}
