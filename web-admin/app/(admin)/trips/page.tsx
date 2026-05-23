'use client';

import { useEffect, useState, useCallback } from 'react';
import { getTrips, createTrip, updateTrip, deleteTrip, getRoutes, getLorries, getDrivers, Trip, Route, Lorry, Driver } from '@/lib/api';
import { formatDate, TRIP_STATUSES } from '@/lib/utils';
import { TripStatusBadge } from '@/components/StatusBadge';
import { ConfirmDialog, Drawer, FormField, EmptyState, SearchInput } from '@/components/ui';
import { Truck, Plus, Trash2, Edit2, Filter, ChevronLeft, ChevronRight } from 'lucide-react';
import { useRouter } from 'next/navigation';

interface TripForm { route_id: string; lorry_id: string; driver_id: string; scheduled_departure: string; scheduled_arrival: string; capacity_units_max: number; }

const DEFAULT_FORM: TripForm = { route_id: '', lorry_id: '', driver_id: '', scheduled_departure: '', scheduled_arrival: '', capacity_units_max: 300 };

export default function TripsPage() {
  const router = useRouter();
  const [trips, setTrips]       = useState<Trip[]>([]);
  const [meta, setMeta]         = useState({ total: 0, page: 1, last_page: 1 });
  const [loading, setLoading]   = useState(true);
  const [status, setStatus]     = useState('');
  const [page, setPage]         = useState(1);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [editTrip, setEditTrip] = useState<Trip | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm]         = useState<TripForm>(DEFAULT_FORM);
  const [saving, setSaving]     = useState(false);
  const [routes, setRoutes]     = useState<Route[]>([]);
  const [lorries, setLorries]   = useState<Lorry[]>([]);
  const [drivers, setDrivers]   = useState<Driver[]>([]);

  useEffect(() => {
    Promise.all([getRoutes(), getLorries(), getDrivers()])
      .then(([r, l, d]) => {
        setRoutes(r);
        setLorries(l);
        setDrivers(d.drivers);
      })
      .catch((error) => {
        console.error('Failed to load trip dependencies', error);
      });
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await getTrips({ status: status || undefined, page, limit: 25 } as Record<string, string | number | undefined>);
      setTrips(data.trips);
      setMeta(data.meta as { total: number; page: number; last_page: number });
    } catch (e) { console.error(e); }
    setLoading(false);
  }, [status, page]);

  useEffect(() => { load(); }, [load]);

  const openCreate = () => { setForm(DEFAULT_FORM); setEditTrip(null); setShowForm(true); };
  const openEdit   = (t: Trip) => {
    setEditTrip(t);
    setForm({
      route_id: t.route_id, lorry_id: t.lorry_id ?? '', driver_id: t.driver_id ?? '',
      scheduled_departure: t.scheduled_departure?.slice(0, 16) ?? '',
      scheduled_arrival: t.scheduled_arrival?.slice(0, 16) ?? '',
      capacity_units_max: t.capacity_units_max,
    });
    setShowForm(true);
  };

  const handleSave = async () => {
    if (!form.route_id) {
      alert('Route is required.');
      return;
    }

    if (!form.scheduled_departure) {
      alert('Scheduled departure is required.');
      return;
    }

    if (!Number.isFinite(form.capacity_units_max) || form.capacity_units_max < 1) {
      alert('Capacity must be at least 1.');
      return;
    }

    setSaving(true);
    try {
      const payload: Record<string, unknown> = {
        route_id: form.route_id,
        lorry_id: form.lorry_id || null,
        driver_id: form.driver_id || null,
        scheduled_departure: form.scheduled_departure,
        scheduled_arrival: form.scheduled_arrival || null,
        capacity_units_max: form.capacity_units_max,
      };

      if (editTrip) {
        await updateTrip(editTrip.id, payload);
      } else {
        await createTrip(payload);
      }
      setShowForm(false);
      load();
    } catch (e) { alert((e as Error).message); }
    setSaving(false);
  };

  const handleDelete = async () => {
    if (!deleteId) return;
    await deleteTrip(deleteId);
    setDeleteId(null);
    load();
  };

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div>
          <h1 className="page-title">Trips</h1>
          <p className="page-subtitle">{meta.total} scheduled trips</p>
        </div>
        <button className="btn btn-primary" onClick={openCreate}><Plus size={14} /> New Trip</button>
      </div>

      <div className="glass" style={{ padding: '14px 18px', marginBottom: 18, display: 'flex', gap: 12, alignItems: 'center' }}>
        <Filter size={14} color="var(--text-muted)" />
        <select className="form-input" value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} style={{ maxWidth: 200 }}>
          <option value="">All Statuses</option>
          {TRIP_STATUSES.map((s) => <option key={s} value={s}>{s.replace(/_/g, ' ')}</option>)}
        </select>
      </div>

      <div className="glass">
        <div style={{ overflowX: 'auto' }}>
          <table className="data-table">
            <thead>
              <tr>
                <th scope="col">Trip Code</th>
                <th scope="col">Route</th>
                <th scope="col">Departure</th>
                <th scope="col">Driver</th>
                <th scope="col">Lorry</th>
                <th scope="col">Capacity</th>
                <th scope="col">Status</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr><td colSpan={8} style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>Loading...</td></tr>
              ) : trips.length === 0 ? (
                <tr><td colSpan={8}><EmptyState icon={<Truck />} title="No trips" /></td></tr>
              ) : trips.map((t) => (
                <tr key={t.id}>
                  <td><span style={{ fontFamily: 'monospace', fontSize: 12, color: 'var(--accent-light)', cursor: 'pointer' }}
                    onClick={() => router.push(`/trips/${t.id}`)}>{t.trip_code}</span></td>
                  <td style={{ fontSize: 12 }}>{t.route?.display_name ?? '—'}</td>
                  <td style={{ fontSize: 12, color: 'var(--text-secondary)' }}>{formatDate(t.scheduled_departure, 'dd MMM HH:mm')}</td>
                  <td style={{ fontSize: 12 }}>{t.driver?.user?.full_name ?? '—'}</td>
                  <td style={{ fontSize: 12 }}>{t.lorry?.registration_number ?? '—'}</td>
                  <td>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                      <div style={{ flex: 1, height: 4, background: 'var(--border)', borderRadius: 2, maxWidth: 60 }}>
                        <div style={{
                          height: '100%', borderRadius: 2,
                          width: `${Math.min(100, (t.capacity_units_used / t.capacity_units_max) * 100)}%`,
                          background: t.capacity_units_used >= t.capacity_units_max ? 'var(--danger)' : 'var(--accent)',
                        }} />
                      </div>
                      <span style={{ fontSize: 11, color: 'var(--text-muted)' }}>{t.capacity_units_used}/{t.capacity_units_max}</span>
                    </div>
                  </td>
                  <td><TripStatusBadge status={t.status} /></td>
                  <td>
                    <div style={{ display: 'flex', gap: 6 }}>
                      <button className="btn btn-secondary btn-sm" onClick={() => openEdit(t)}><Edit2 size={13} /></button>
                      <button className="btn btn-danger btn-sm" onClick={() => setDeleteId(t.id)}><Trash2 size={13} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {meta.last_page > 1 && (
          <div style={{ padding: '12px 16px', borderTop: '1px solid var(--border)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: 12, color: 'var(--text-muted)' }}>Page {meta.page} of {meta.last_page}</span>
            <div style={{ display: 'flex', gap: 8 }}>
              <button className="btn btn-secondary btn-sm" onClick={() => setPage(p => p - 1)} disabled={page <= 1}><ChevronLeft size={13} /></button>
              <button className="btn btn-secondary btn-sm" onClick={() => setPage(p => p + 1)} disabled={page >= meta.last_page}><ChevronRight size={13} /></button>
            </div>
          </div>
        )}
      </div>

      {showForm && (
        <Drawer title={editTrip ? 'Edit Trip' : 'New Trip'} onClose={() => setShowForm(false)}>
          <FormField label="Route" required>
            <select className="form-input" value={form.route_id} onChange={(e) => setForm(f => ({ ...f, route_id: e.target.value }))}>
              <option value="">Select route...</option>
              {routes.map(r => <option key={r.id} value={r.id}>{r.display_name}</option>)}
            </select>
          </FormField>
          <FormField label="Lorry">
            <select className="form-input" value={form.lorry_id} onChange={(e) => setForm(f => ({ ...f, lorry_id: e.target.value }))}>
              <option value="">Select lorry...</option>
              {lorries.map(l => <option key={l.id} value={l.id}>{l.registration_number} ({l.type})</option>)}
            </select>
          </FormField>
          <FormField label="Driver">
            <select className="form-input" value={form.driver_id} onChange={(e) => setForm(f => ({ ...f, driver_id: e.target.value }))}>
              <option value="">Select driver...</option>
              {drivers.map(d => <option key={d.id} value={d.id}>{d.user?.full_name ?? d.id}</option>)}
            </select>
          </FormField>
          <FormField label="Scheduled Departure" required>
            <input type="datetime-local" className="form-input" value={form.scheduled_departure}
              onChange={(e) => setForm(f => ({ ...f, scheduled_departure: e.target.value }))} />
          </FormField>
          <FormField label="Scheduled Arrival">
            <input type="datetime-local" className="form-input" value={form.scheduled_arrival}
              onChange={(e) => setForm(f => ({ ...f, scheduled_arrival: e.target.value }))} />
          </FormField>
          <FormField label="Max Capacity Units">
            <input type="number" className="form-input" value={form.capacity_units_max} min={1}
              onChange={(e) => {
                const value = parseInt(e.target.value, 10);
                setForm(f => ({ ...f, capacity_units_max: Number.isNaN(value) ? 1 : value }));
              }} />
          </FormField>
          <div style={{ display: 'flex', gap: 10, marginTop: 24 }}>
            <button className="btn btn-secondary" onClick={() => setShowForm(false)} style={{ flex: 1 }}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSave} disabled={saving} style={{ flex: 1 }}>
              {saving ? 'Saving...' : editTrip ? 'Update Trip' : 'Create Trip'}
            </button>
          </div>
        </Drawer>
      )}

      {deleteId && (
        <ConfirmDialog title="Delete Trip" message="Delete this trip? This can't be undone if it has parcels." onConfirm={handleDelete} onCancel={() => setDeleteId(null)} />
      )}
    </div>
  );
}
