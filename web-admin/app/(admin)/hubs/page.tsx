'use client';

import { useEffect, useState } from 'react';
import { getHubs, createHub, updateHub, deleteHub, Hub } from '@/lib/api';
import { ActiveBadge } from '@/components/StatusBadge';
import { ConfirmDialog, Drawer, FormField, EmptyState } from '@/components/ui';
import { MapPin, Plus, Trash2, Edit2, Phone } from 'lucide-react';

export default function HubsPage() {
  const [hubs, setHubs]         = useState<Hub[]>([]);
  const [loading, setLoading]   = useState(true);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [editHub, setEditHub]   = useState<Hub | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving]     = useState(false);
  const [form, setForm]         = useState({ name: '', code: '', city: '', district: '', address: '', phone: '', hub_lat: '', hub_lng: '', is_active: true });

  const load = async () => { setLoading(true); try { setHubs(await getHubs()); } catch (e) { console.error(e); } setLoading(false); };
  useEffect(() => { load(); }, []);

  const openCreate = () => { setForm({ name: '', code: '', city: '', district: '', address: '', phone: '', hub_lat: '', hub_lng: '', is_active: true }); setEditHub(null); setShowForm(true); };
  const openEdit   = (h: Hub) => { setEditHub(h); setForm({ name: h.name, code: h.code, city: h.city, district: h.district ?? '', address: h.address ?? '', phone: h.phone ?? '', hub_lat: String(h.hub_lat ?? ''), hub_lng: String(h.hub_lng ?? ''), is_active: h.is_active }); setShowForm(true); };

  const handleSave = async () => {
    setSaving(true);
    try {
      const payload = { ...form, hub_lat: form.hub_lat ? Number(form.hub_lat) : undefined, hub_lng: form.hub_lng ? Number(form.hub_lng) : undefined };
      if (editHub) await updateHub(editHub.id, payload as Record<string, unknown>);
      else         await createHub(payload as Record<string, unknown>);
      setShowForm(false); load();
    } catch (e) { alert((e as Error).message); }
    setSaving(false);
  };

  const handleDelete = async () => { if (!deleteId) return; await deleteHub(deleteId); setDeleteId(null); load(); };

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div><h1 className="page-title">Hubs</h1><p className="page-subtitle">{hubs.length} hubs configured</p></div>
        <button className="btn btn-primary" onClick={openCreate}><Plus size={14} /> New Hub</button>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 16 }}>
        {loading ? <div style={{ gridColumn: '1/-1', textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>Loading...</div>
        : hubs.length === 0 ? <div style={{ gridColumn: '1/-1' }}><EmptyState icon={<MapPin />} title="No hubs" /></div>
        : hubs.map((h) => (
          <div key={h.id} className="glass glass-hover" style={{ padding: '18px 20px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 }}>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 4 }}>
                  <span style={{ fontFamily: 'monospace', fontSize: 12, padding: '2px 7px', borderRadius: 5, background: 'var(--accent-dim)', color: 'var(--accent-light)' }}>{h.code}</span>
                  <ActiveBadge active={h.is_active} />
                </div>
                <div style={{ fontSize: 15, fontWeight: 700 }}>{h.name}</div>
                <div style={{ fontSize: 12, color: 'var(--text-secondary)', marginTop: 2 }}>{h.city}{h.district ? `, ${h.district}` : ''}</div>
              </div>
              <div style={{ display: 'flex', gap: 6 }}>
                <button className="btn btn-secondary btn-sm" onClick={() => openEdit(h)}><Edit2 size={13} /></button>
                <button className="btn btn-danger btn-sm" onClick={() => setDeleteId(h.id)}><Trash2 size={13} /></button>
              </div>
            </div>
            {h.address && <div style={{ fontSize: 12, color: 'var(--text-muted)', marginBottom: 4 }}>📍 {h.address}</div>}
            {h.phone && <div style={{ fontSize: 12, color: 'var(--text-muted)', display: 'flex', alignItems: 'center', gap: 4 }}><Phone size={10} /> {h.phone}</div>}
            {(h.hub_lat && h.hub_lng) && <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 4 }}>🌐 {h.hub_lat}, {h.hub_lng}</div>}
          </div>
        ))}
      </div>

      {showForm && (
        <Drawer title={editHub ? 'Edit Hub' : 'New Hub'} onClose={() => setShowForm(false)}>
          {(['name', 'code', 'city', 'district', 'address', 'phone'] as const).map((k) => (
            <FormField key={k} label={k.charAt(0).toUpperCase() + k.slice(1)} required={['name', 'code', 'city'].includes(k)}>
              <input className="form-input" value={form[k] as string} onChange={e => setForm(f => ({ ...f, [k]: e.target.value }))} />
            </FormField>
          ))}
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            <FormField label="Latitude"><input type="number" step="any" className="form-input" value={form.hub_lat} onChange={e => setForm(f => ({ ...f, hub_lat: e.target.value }))} /></FormField>
            <FormField label="Longitude"><input type="number" step="any" className="form-input" value={form.hub_lng} onChange={e => setForm(f => ({ ...f, hub_lng: e.target.value }))} /></FormField>
          </div>
          <FormField label="Active">
            <select className="form-input" value={String(form.is_active)} onChange={e => setForm(f => ({ ...f, is_active: e.target.value === 'true' }))}>
              <option value="true">Active</option><option value="false">Inactive</option>
            </select>
          </FormField>
          <div style={{ display: 'flex', gap: 10, marginTop: 24 }}>
            <button className="btn btn-secondary" onClick={() => setShowForm(false)} style={{ flex: 1 }}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSave} disabled={saving} style={{ flex: 1 }}>{saving ? 'Saving...' : editHub ? 'Update Hub' : 'Create Hub'}</button>
          </div>
        </Drawer>
      )}
      {deleteId && <ConfirmDialog title="Delete Hub" message="Delete this hub?" onConfirm={handleDelete} onCancel={() => setDeleteId(null)} />}
    </div>
  );
}
