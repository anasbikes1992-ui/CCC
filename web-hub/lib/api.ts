// lib/api.ts — Hub console API client

const BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL ?? 'http://localhost:8000/api/v1';

const TOKEN_KEY = 'ccc_hub_token';
const USER_KEY  = 'ccc_hub_user';

export function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function getUser(): HubUser | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = localStorage.getItem(USER_KEY);
    return raw ? (JSON.parse(raw) as HubUser) : null;
  } catch { return null; }
}

export function saveAuth(token: string, user: HubUser): void {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function clearAuth(): void {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
}

interface ApiOptions extends RequestInit {
  params?: Record<string, string | number | boolean | undefined>;
}

async function request<T>(path: string, options: ApiOptions = {}): Promise<T> {
  const { params, ...fetchOptions } = options;

  let url = `${BASE_URL}${path}`;
  if (params) {
    const qs = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== '' && v !== null) qs.set(k, String(v));
    });
    const s = qs.toString();
    if (s) url += `?${s}`;
  }

  const token = getToken();
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(fetchOptions.headers as Record<string, string>),
  };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  const res = await fetch(url, { ...fetchOptions, headers });

  if (res.status === 401) {
    if (typeof window !== 'undefined') {
      clearAuth();
      window.location.href = '/login';
    }
    throw new Error('Unauthorized');
  }

  const json = await res.json();
  if (!res.ok) throw new Error(json?.error?.message ?? `HTTP ${res.status}`);
  return json as T;
}

// ── Types ─────────────────────────────────────────────────────────────────────

export interface HubUser {
  id: string;
  name: string;
  email: string;
  role: 'hub_staff' | 'hub_manager' | 'super_admin';
  hub_staff?: { hub_id: string; hub?: { id: string; name: string; city: string } };
}

export interface ParcelSummary {
  id: string;
  parcel_number: string;
  status: string;
  size?: string;
  sender_name: string;
  receiver_name: string;
  pickup_point: string;
  drop_point: string;
  trip?: {
    id: string;
    route: string;
    scheduled_departure: string;
    status: string;
  } | null;
}

export interface TripManifest {
  id: string;
  route: { id: string; code: string };
  lorry: { id: string; plate_number: string; type: string };
  driver: { id: string; name: string; phone: string };
  scheduled_departure_at: string;
  scheduled_arrival_at: string;
  status: string;
  parcels: Array<{
    id: string;
    parcel_number: string;
    size_code: string;
    status: string;
    sender_name: string;
    receiver_name: string;
    receiver_phone: string;
    pickup_point: string;
    drop_point: string;
    declared_value: number;
  }>;
}

export interface HubDashboardStats {
  atHub: number;
  inboundToday: number;
  outboundToday: number;
}

// ── Auth ──────────────────────────────────────────────────────────────────────

export async function login(email: string, password: string) {
  const data = await request<{ success: boolean; data: { token: string; user: HubUser } }>(
    '/auth/login',
    { method: 'POST', body: JSON.stringify({ email, password }) }
  );
  return data.data;
}

export async function logout() {
  await request('/auth/logout', { method: 'POST' }).catch(() => {});
  clearAuth();
}

// ── Hub ───────────────────────────────────────────────────────────────────────

export async function getDashboardStats() {
  const data = await request<{ success: boolean; data: HubDashboardStats }>('/hub/dashboard');
  return data.data;
}

export async function getInventory(params?: { limit?: number; page?: number }) {
  const data = await request<{ success: boolean; data: ParcelSummary[]; meta: Record<string, number> }>(
    '/hub/inventory', { params }
  );
  return data;
}

export async function getInbound(date?: string) {
  const data = await request<{ success: boolean; data: TripManifest[] }>(
    '/hub/inbound', { params: date ? { date } : undefined }
  );
  return data.data;
}

export async function getOutbound(date?: string) {
  const data = await request<{ success: boolean; data: TripManifest[] }>(
    '/hub/outbound', { params: date ? { date } : undefined }
  );
  return data.data;
}

export async function getManifest(tripId: string) {
  const data = await request<{ success: boolean; data: TripManifest }>(`/hub/trips/${tripId}/manifest`);
  return data.data;
}

export async function lookupParcel(idOrNumber: string) {
  const data = await request<{ success: boolean; data: ParcelSummary }>(`/hub/parcels/${idOrNumber}`);
  return data.data;
}

export type ScanEvent =
  | 'RECEIVED_AT_ORIGIN_HUB'
  | 'ARRIVED_AT_DESTINATION_HUB'
  | 'LOADED_ON_LORRY'
  | 'OUT_FOR_DELIVERY';

export async function scanParcel(
  idOrNumber: string,
  event: ScanEvent,
  tripId?: string,
  notes?: string
) {
  const data = await request<{ success: boolean; data: { parcel_number: string; new_status: string; event_id: string } }>(
    `/hub/parcels/${idOrNumber}/scan`,
    {
      method: 'POST',
      body: JSON.stringify({ event, trip_id: tripId ?? null, notes: notes ?? null }),
    }
  );
  return data.data;
}
