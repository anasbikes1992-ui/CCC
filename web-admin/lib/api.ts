// lib/api.ts — Typed fetch wrapper for the CCC Laravel API

const BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL ?? 'http://localhost:8000/api/v1';

function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem('ccc_admin_token');
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
    const qstring = qs.toString();
    if (qstring) url += `?${qstring}`;
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
      localStorage.removeItem('ccc_admin_token');
      localStorage.removeItem('ccc_admin_user');
      window.location.href = '/login';
    }
    throw new Error('Unauthorized');
  }

  const json = await res.json();

  if (!res.ok) {
    const msg =
      json?.error?.message ?? json?.message ?? `HTTP ${res.status}`;
    throw new Error(msg);
  }

  return json as T;
}

// ── Auth ──────────────────────────────────────────────────────────────────────

export interface AuthUser {
  id: string;
  full_name: string;
  phone: string;
  email?: string;
  role: string;
}

export async function login(phone: string, password: string): Promise<{ user: AuthUser; token: string }> {
  const res = await request<{ success: boolean; data: { user: AuthUser; token: string } }>('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ phone, password }),
  });
  return res.data;
}


export async function logout(): Promise<void> {
  await request('/auth/logout', { method: 'POST' }).catch(() => {});
  localStorage.removeItem('ccc_admin_token');
  localStorage.removeItem('ccc_admin_user');
}

// ── Dashboard ─────────────────────────────────────────────────────────────────

export async function getDashboardStats() {
  const res = await request<{ success: boolean; data: DashboardStats }>('/admin/dashboard/stats');
  return res.data;
}

export interface DashboardStats {
  kpis: {
    bookings_today: number;
    bookings_mtd: number;
    revenue_today: number;
    revenue_mtd: number;
    active_trips: number;
    total_customers: number;
  };
  parcels_by_status: Record<string, number>;
  last_7_days: Array<{ date: string; count: number; revenue: number }>;
  recent_parcels: Array<{
    id: string;
    parcel_number: string;
    status: string;
    customer_name: string;
    route: string;
    total_price: number;
    created_at: string;
  }>;
  notification_stats: Array<{ channel: string; status: string; count: number }>;
}

// ── Parcels ───────────────────────────────────────────────────────────────────

export interface Parcel {
  id: string;
  parcel_number: string;
  status: string;
  customer?: { id: string; full_name: string; phone: string };
  route?: { display_name: string; origin_hub?: { name: string }; destination_hub?: { name: string } };
  package_size?: { code: string; name: string };
  trip?: { id: string; trip_code: string; scheduled_departure: string };
  total_price_lkr: number;
  weight_kg: number;
  receiver_name: string;
  receiver_phone: string;
  pickup_type: string;
  drop_type: string;
  is_express: boolean;
  notes?: string;
  created_at: string;
  events?: ParcelEvent[];
  delivery_proof?: DeliveryProof;
  payments?: Payment[];
}

export interface ParcelEvent {
  id: string;
  event_type: string;
  occurred_at: string;
  scan_mode: string;
  metadata?: Record<string, unknown>;
}

export interface DeliveryProof {
  id: string;
  receiver_name_input: string;
  receiver_nic_last4: string;
  delivered_at: string;
  signature_url?: string;
  photo_url?: string;
}

export interface Payment {
  id: string;
  method: string;
  status: string;
  amount_lkr: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: { total: number; page: number; last_page: number };
}

export async function getParcels(params?: Record<string, string | number | undefined>) {
  const res = await request<{ success: boolean; data: { parcels: Parcel[]; meta: PaginatedResponse<Parcel>['meta'] } }>(
    '/admin/parcels', { params }
  );
  return res.data;
}

export async function getParcel(id: string) {
  const res = await request<{ success: boolean; data: { parcel: Parcel } }>(`/admin/parcels/${id}`);
  return res.data.parcel;
}

export async function updateParcel(id: string, data: Partial<{ status: string; notes: string; trip_id: string }>) {
  const res = await request<{ success: boolean; data: { parcel: Parcel } }>(`/admin/parcels/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data.parcel;
}

export async function deleteParcel(id: string) {
  await request(`/admin/parcels/${id}`, { method: 'DELETE' });
}

// ── Trips ─────────────────────────────────────────────────────────────────────

export interface Trip {
  id: string;
  trip_code: string;
  route_id: string;
  lorry_id?: string;
  driver_id?: string;
  scheduled_departure: string;
  scheduled_arrival?: string;
  actual_departure?: string;
  actual_arrival?: string;
  status: string;
  capacity_units_max: number;
  capacity_units_used: number;
  bookings_close_at?: string;
  route?: { display_name: string; origin_hub?: { name: string }; destination_hub?: { name: string } };
  lorry?: { registration_number: string; type: string };
  driver?: { user?: { full_name: string } };
  parcels?: Parcel[];
}

export async function getTrips(params?: Record<string, string | number | undefined>) {
  const res = await request<{ success: boolean; data: { trips: Trip[]; meta: Record<string, number> } }>('/admin/trips', { params });
  return res.data;
}

export async function getTrip(id: string) {
  const res = await request<{ success: boolean; data: { trip: Trip } }>(`/admin/trips/${id}`);
  return res.data.trip;
}

export async function createTrip(data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { trip: Trip } }>('/admin/trips', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data.trip;
}

export async function updateTrip(id: string, data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { trip: Trip } }>(`/admin/trips/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data.trip;
}

export async function deleteTrip(id: string) {
  await request(`/admin/trips/${id}`, { method: 'DELETE' });
}

// ── Users ─────────────────────────────────────────────────────────────────────

export interface User {
  id: string;
  full_name: string;
  phone: string;
  email?: string;
  role: string;
  preferred_lang: string;
  parcels_count?: number;
  created_at?: string;
  driver?: Driver;
}

export async function getUsers(params?: Record<string, string | number | undefined>) {
  const res = await request<{ success: boolean; data: { users: User[]; meta: Record<string, number> } }>('/admin/users', { params });
  return res.data;
}

export async function getUser(id: string) {
  const res = await request<{ success: boolean; data: { user: User } }>(`/admin/users/${id}`);
  return res.data.user;
}

export async function createUser(data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { user: User } }>('/admin/users', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data.user;
}

export async function updateUser(id: string, data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { user: User } }>(`/admin/users/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data.user;
}

export async function deleteUser(id: string) {
  await request(`/admin/users/${id}`, { method: 'DELETE' });
}

// ── Drivers ───────────────────────────────────────────────────────────────────

export interface Driver {
  id: string;
  user_id: string;
  license_number: string;
  license_expires_at: string;
  is_active: boolean;
  user?: User;
}

export async function getDrivers(params?: Record<string, string | number | undefined>) {
  const res = await request<{ success: boolean; data: { drivers: Driver[]; meta: Record<string, number> } }>('/admin/drivers', { params });
  return res.data;
}

export async function getDriver(id: string) {
  const res = await request<{ success: boolean; data: { driver: Driver } }>(`/admin/drivers/${id}`);
  return res.data.driver;
}

export async function createDriver(data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { driver: Driver } }>('/admin/drivers', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data.driver;
}

export async function updateDriver(id: string, data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { driver: Driver } }>(`/admin/drivers/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data.driver;
}

export async function deleteDriver(id: string) {
  await request(`/admin/drivers/${id}`, { method: 'DELETE' });
}

// ── Hubs ──────────────────────────────────────────────────────────────────────

export interface Hub {
  id: string;
  code: string;
  name: string;
  city: string;
  district?: string;
  address?: string;
  phone?: string;
  hub_lat?: number;
  hub_lng?: number;
  is_active: boolean;
}

export async function getHubs() {
  const res = await request<{ success: boolean; data: { hubs: Hub[] } }>('/admin/hubs');
  return res.data.hubs;
}

export async function createHub(data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { hub: Hub } }>('/admin/hubs', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data.hub;
}

export async function updateHub(id: string, data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { hub: Hub } }>(`/admin/hubs/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data.hub;
}

export async function deleteHub(id: string) {
  await request(`/admin/hubs/${id}`, { method: 'DELETE' });
}

// ── Routes ────────────────────────────────────────────────────────────────────

export interface Route {
  id: string;
  code: string;
  display_name: string;
  origin_hub_id: string;
  destination_hub_id: string;
  estimated_duration_minutes: number;
  is_active: boolean;
  trips_count?: number;
  origin_hub?: Hub;
  destination_hub?: Hub;
}

export async function getRoutes() {
  const res = await request<{ success: boolean; data: { routes: Route[] } }>('/admin/routes');
  return res.data.routes;
}

export async function createRoute(data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { route: Route } }>('/admin/routes', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data.route;
}

export async function updateRoute(id: string, data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { route: Route } }>(`/admin/routes/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data.route;
}

export async function deleteRoute(id: string) {
  await request(`/admin/routes/${id}`, { method: 'DELETE' });
}

// ── Lorries ───────────────────────────────────────────────────────────────────

export interface Lorry {
  id: string;
  registration_number: string;
  type: string;
  max_weight_kg: number;
  max_capacity_units: number;
  is_active: boolean;
}

export async function getLorries() {
  const res = await request<{ success: boolean; data: { lorries: Lorry[] } }>('/admin/lorries');
  return res.data.lorries;
}

export async function createLorry(data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { lorry: Lorry } }>('/admin/lorries', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data.lorry;
}

export async function updateLorry(id: string, data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { lorry: Lorry } }>(`/admin/lorries/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data.lorry;
}

export async function deleteLorry(id: string) {
  await request(`/admin/lorries/${id}`, { method: 'DELETE' });
}

// ── Pricing ───────────────────────────────────────────────────────────────────

export interface PackageSize {
  id: string;
  code: string;
  name: string;
  capacity_units: number;
}

export interface PricingEntry {
  id: string;
  route_id: string;
  package_size_id: string;
  base_price_lkr: number;
  surcharges?: Record<string, number>;
  effective_from?: string;
  effective_until?: string;
  route?: Route;
  package_size?: PackageSize;
}

export async function getPricing(params?: Record<string, string>) {
  const res = await request<{ success: boolean; data: { pricing: PricingEntry[] } }>('/admin/pricing', { params });
  return res.data.pricing;
}

export async function createPricing(data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { pricing: PricingEntry } }>('/admin/pricing', {
    method: 'POST',
    body: JSON.stringify(data),
  });
  return res.data.pricing;
}

export async function updatePricing(id: string, data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: { pricing: PricingEntry } }>(`/admin/pricing/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data.pricing;
}

export async function deletePricing(id: string) {
  await request(`/admin/pricing/${id}`, { method: 'DELETE' });
}

// ── Notifications ─────────────────────────────────────────────────────────────

export interface NotificationLog {
  id: string;
  parcel_id?: string;
  user_id?: string;
  channel: string;
  template?: string;
  recipient?: string;
  status: string;
  error_message?: string;
  sent_at?: string;
  created_at: string;
  parcel?: { parcel_number: string };
}

export async function getNotifications(params?: Record<string, string | number | undefined>) {
  const res = await request<{ success: boolean; data: { logs: NotificationLog[]; meta: Record<string, number> } }>(
    '/admin/notifications-log', { params }
  );
  return res.data;
}

// ── Disputes ──────────────────────────────────────────────────────────────────

export interface Dispute {
  id: string;
  parcel_id: string;
  type: string;
  description: string;
  status: 'open' | 'under_review' | 'resolved' | 'rejected' | 'closed';
  resolution?: string | null;
  resolved_at?: string | null;
  created_at: string;
  parcel?: { id: string; parcel_number: string; status: string };
  raised_by?: { id: string; name: string; phone: string };
  resolved_by_user?: { id: string; name: string };
}

export async function getDisputes(params?: Record<string, string | number | undefined>) {
  const res = await request<{ success: boolean; data: Dispute[]; meta: Record<string, number> }>(
    '/admin/disputes', { params }
  );
  return res;
}

export async function getDispute(id: string) {
  const res = await request<{ success: boolean; data: Dispute }>(`/admin/disputes/${id}`);
  return res.data;
}

export async function updateDispute(id: string, data: { status: string; resolution?: string }) {
  const res = await request<{ success: boolean; data: Dispute }>(`/admin/disputes/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data;
}

// ── Support Tickets ───────────────────────────────────────────────────────────

export interface TicketMessage {
  id: string;
  sender_id: string;
  body: string;
  attachments: unknown[];
  sent_at: string;
  sender?: { id: string; name: string };
}

export interface SupportTicket {
  id: string;
  subject: string;
  status: 'open' | 'pending' | 'in_progress' | 'resolved' | 'closed';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  created_at: string;
  resolved_at?: string | null;
  user?: { id: string; name: string; phone: string; email: string };
  parcel?: { id: string; parcel_number: string } | null;
  assigned_to_user?: { id: string; name: string } | null;
  messages?: TicketMessage[];
  latest_message?: TicketMessage[];
}

export async function getTickets(params?: Record<string, string | number | undefined>) {
  const res = await request<{ success: boolean; data: SupportTicket[]; meta: Record<string, number> }>(
    '/admin/tickets', { params }
  );
  return res;
}

export async function getTicket(id: string) {
  const res = await request<{ success: boolean; data: SupportTicket }>(`/admin/tickets/${id}`);
  return res.data;
}

export async function updateTicket(id: string, data: Record<string, unknown>) {
  const res = await request<{ success: boolean; data: SupportTicket }>(`/admin/tickets/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  });
  return res.data;
}

export async function replyToTicket(id: string, body: string) {
  const res = await request<{ success: boolean; data: TicketMessage }>(`/admin/tickets/${id}/reply`, {
    method: 'POST',
    body: JSON.stringify({ body }),
  });
  return res.data;
}
