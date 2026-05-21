'use client';

import { useEffect, useState } from 'react';
import { getRoutes, createRoute, updateRoute, deleteRoute, getHubs, Route, Hub } from '@/lib/api';
import { ActiveBadge } from '@/components/StatusBadge';
import { ConfirmDialog, Drawer, FormField, EmptyState } from '@/components/ui';
import { Route as RouteIcon, Plus, Trash2, Edit2, ArrowRight, Clock } from 'lucide-react';

export default function RoutesPage() {
  const [routes, setRoutes]     = useState<Route[]>([]);
  const [hubs, setHubs]         = useState<Hub[]>([]);
  const [loading, setLoading]   = useState(true);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [editRoute, setEditRoute] = useState<Route | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving]     = useState(false);
  const [form, setForm]         = useState({ code: '', origin_hub_id: '', destination_hub_id: '', display_name: '', estimated_duration_minutes: 180, is_active: true });

  const load = async () => {
    setLoading(true);
    try {
      const [r, h] = await Promise.all([getRoutes(), getHubs()]);
      setRoutes(r); setHubs(h);
    } catch (e) { console.error(e); }
    setLoading(false);
  };
  useEffect(() => { load(); }, []);

  const openCreate = () => { setForm({ code: '', origin_hub_id: '', destination_hub_id: '', display_name: '', estimated_duration_minutes: 180, is_active: true }); setEditRoute(null); setShowForm(true); };
  const openEdit   = (r: Route) => { setEditRoute(r); setForm({ code: r.code, origin_hub_id: r.origin_hub_id, destination_hub_id: r.destination_hub_id, display_name: r.display_name, estimated_duration_minutes: r.estimated_duration_minutes, is_active: r.is_active }); setShowForm(true); };

  const handleSave = async () => {
    setSaving(true);
    try {
      if (editRoute) await updateRoute(editRoute.id, form as Record<string, unknown>);
      else           await createRoute(form as Record<string, unknown>);
      setShowForm(false); load();
    } catch (e) { alert((e as Error).message); }
    setSaving(false);
  };
  const handleDelete = async () => { if (!deleteId) return; await deleteRoute(deleteId); setDeleteId(null); load(); };

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div><h1 className="page-title">Routes</h1><p className="page-subtitle">{routes.length} routes configured</p></div>
        <button className="btn btn-primary" onClick={openCreate}><Plus size={14} /> New Route</button>
      </div>

      <div className="glass">
        <table className="data-table">
          <thead><tr><th>Code</th><th>Route</th><th>Duration</th><th>Trips</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            {loading ? <tr><td colSpan={6} style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>Loading...</td></tr>
            : routes.length === 0 ? <tr><td colSpan={6}><EmptyState icon={<RouteIcon />} title="No routes" /></td></tr>
            : routes.map((r) => (
              <tr key={r.id}>
                <td><span style={{ fontFamily: 'monospace', fontSize: 12, padding: '2px 8px', borderRadius: 5, background: 'var(--accent-dim)', color: 'var(--accent-light)' }}>{r.code}</span></td>
                <td>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: 13 }}>
                    <span style={{ fontWeight: 500 }}>{r.origin_hub?.name ?? '—'}</span>
                    <ArrowRight size={12} color="var(--text-muted)" />
                    <span style={{ fontWeight: 500 }}>{r.destination_hub?.name ?? '—'}</span>
                  </div>
                  <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 2 }}>{r.display_name}</div>
                </td>
                <td>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 12, color: 'var(--text-secondary)' }}>
                    <Clock size={12} /> {Math.floor(r.estimated_duration_minutes / 60)}h {r.estimated_duration_minutes % 60}m
                  </div>
                </td>
                <td style={{ fontSize: 12 }}>{r.trips_count ?? 0}</td>
                <td><ActiveBadge active={r.is_active} /></td>
                <td>
                  <div style={{ display: 'flex', gap: 6 }}>
                    <button className="btn btn-secondary btn-sm" onClick={() => openEdit(r)}><Edit2 size={13} /></button>
                    <button className="btn btn-danger btn-sm" onClick={() => setDeleteId(r.id)}><Trash2 size={13} /></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {showForm && (
        <Drawer title={editRoute ? 'Edit Route' : 'New Route'} onClose={() => setShowForm(false)}>
          <FormField label="Route Code" required hint="e.g. CMB-KDY"><input className="form-input" value={form.code} onChange={e => setForm(f => ({ ...f, code: e.target.value.toUpperCase() }))} /></FormField>
          <FormField label="Display Name" required><input className="form-input" value={form.display_name} onChange={e => setForm(f => ({ ...f, display_name: e.target.value }))} /></FormField>
          <FormField label="Origin Hub" required>
            <select className="form-input" value={form.origin_hub_id} onChange={e => setForm(f => ({ ...f, origin_hub_id: e.target.value }))}>
              <option value="">Select hub...</option>
              {hubs.map(h => <option key={h.id} value={h.id}>{h.name}</option>)}
            </select>
          </FormField>
          <FormField label="Destination Hub" required>
            <select className="form-input" value={form.destination_hub_id} onChange={e => setForm(f => ({ ...f, destination_hub_id: e.target.value }))}>
              <option value="">Select hub...</option>
              {hubs.filter(h => h.id !== form.origin_hub_id).map(h => <option key={h.id} value={h.id}>{h.name}</option>)}
            </select>
          </FormField>
          <FormField label="Estimated Duration (minutes)">
            <input type="number" className="form-input" value={form.estimated_duration_minutes} onChange={e => setForm(f => ({ ...f, estimated_duration_minutes: parseInt(e.target.value) }))} />
          </FormField>
          <FormField label="Active">
            <select className="form-input" value={String(form.is_active)} onChange={e => setForm(f => ({ ...f, is_active: e.target.value === 'true' }))}>
              <option value="true">Active</option><option value="false">Inactive</option>
            </select>
          </FormField>
          <div style={{ display: 'flex', gap: 10, marginTop: 24 }}>
            <button className="btn btn-secondary" onClick={() => setShowForm(false)} style={{ flex: 1 }}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSave} disabled={saving} style={{ flex: 1 }}>{saving ? 'Saving...' : editRoute ? 'Update' : 'Create'}</button>
          </div>
        </Drawer>
      )}
      {deleteId && <ConfirmDialog title="Delete Route" message="Delete this route? This will fail if trips exist." onConfirm={handleDelete} onCancel={() => setDeleteId(null)} />}
    </div>
  );
}
