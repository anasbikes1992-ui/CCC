'use client';

import { useEffect, useState } from 'react';
import { getLorries, createLorry, updateLorry, deleteLorry, Lorry } from '@/lib/api';
import { ActiveBadge } from '@/components/StatusBadge';
import { ConfirmDialog, Drawer, FormField, EmptyState } from '@/components/ui';
import { Car, Plus, Trash2, Edit2 } from 'lucide-react';

export default function LorriesPage() {
  const [lorries, setLorries]   = useState<Lorry[]>([]);
  const [loading, setLoading]   = useState(true);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [editLorry, setEditLorry] = useState<Lorry | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving]     = useState(false);
  const [form, setForm]         = useState({ registration_number: '', type: 'medium', max_weight_kg: 2000, max_capacity_units: 300, is_active: true });

  const load = async () => { setLoading(true); try { setLorries(await getLorries()); } catch (e) { console.error(e); } setLoading(false); };
  useEffect(() => { load(); }, []);

  const openCreate = () => { setForm({ registration_number: '', type: 'medium', max_weight_kg: 2000, max_capacity_units: 300, is_active: true }); setEditLorry(null); setShowForm(true); };
  const openEdit   = (l: Lorry) => { setEditLorry(l); setForm({ registration_number: l.registration_number, type: l.type, max_weight_kg: Number(l.max_weight_kg), max_capacity_units: l.max_capacity_units, is_active: l.is_active }); setShowForm(true); };

  const handleSave = async () => {
    setSaving(true);
    try {
      if (editLorry) await updateLorry(editLorry.id, form as Record<string, unknown>);
      else           await createLorry(form as Record<string, unknown>);
      setShowForm(false); load();
    } catch (e) { alert((e as Error).message); }
    setSaving(false);
  };
  const handleDelete = async () => { if (!deleteId) return; await deleteLorry(deleteId); setDeleteId(null); load(); };

  const TYPE_COLORS: Record<string, string> = { small: '#10b981', medium: '#6366f1', large: '#f59e0b' };

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div><h1 className="page-title">Lorries</h1><p className="page-subtitle">{lorries.length} vehicles in fleet</p></div>
        <button className="btn btn-primary" onClick={openCreate}><Plus size={14} /> New Lorry</button>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: 16 }}>
        {loading ? <div style={{ gridColumn: '1/-1', textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>Loading...</div>
        : lorries.length === 0 ? <div style={{ gridColumn: '1/-1' }}><EmptyState icon={<Car />} title="No lorries" /></div>
        : lorries.map((l) => (
          <div key={l.id} className="kpi-card" style={{ padding: '18px 20px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 }}>
              <div>
                <div style={{ fontSize: 18, fontWeight: 800, fontFamily: 'monospace', color: 'var(--text-primary)', marginBottom: 4 }}>
                  {l.registration_number}
                </div>
                <span style={{
                  fontSize: 11, fontWeight: 700, padding: '2px 8px', borderRadius: 5, textTransform: 'uppercase',
                  color: TYPE_COLORS[l.type] ?? '#94a3b8',
                  background: `${TYPE_COLORS[l.type] ?? '#94a3b8'}22`,
                }}>
                  {l.type}
                </span>
              </div>
              <ActiveBadge active={l.is_active} />
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 8, marginBottom: 12 }}>
              <div>
                <div style={{ fontSize: 11, color: 'var(--text-muted)' }}>Max Weight</div>
                <div style={{ fontSize: 14, fontWeight: 600 }}>{l.max_weight_kg} kg</div>
              </div>
              <div>
                <div style={{ fontSize: 11, color: 'var(--text-muted)' }}>Capacity Units</div>
                <div style={{ fontSize: 14, fontWeight: 600 }}>{l.max_capacity_units}</div>
              </div>
            </div>
            <div style={{ display: 'flex', gap: 8 }}>
              <button className="btn btn-secondary btn-sm" onClick={() => openEdit(l)} style={{ flex: 1 }}><Edit2 size={12} /> Edit</button>
              <button className="btn btn-danger btn-sm" onClick={() => setDeleteId(l.id)}><Trash2 size={12} /></button>
            </div>
          </div>
        ))}
      </div>

      {showForm && (
        <Drawer title={editLorry ? 'Edit Lorry' : 'New Lorry'} onClose={() => setShowForm(false)}>
          <FormField label="Registration Number" required><input className="form-input" value={form.registration_number} onChange={e => setForm(f => ({ ...f, registration_number: e.target.value.toUpperCase() }))} placeholder="e.g. LX-1234" /></FormField>
          <FormField label="Type" required>
            <select className="form-input" value={form.type} onChange={e => setForm(f => ({ ...f, type: e.target.value }))}>
              <option value="small">Small</option><option value="medium">Medium</option><option value="large">Large</option>
            </select>
          </FormField>
          <FormField label="Max Weight (kg)"><input type="number" className="form-input" value={form.max_weight_kg} onChange={e => setForm(f => ({ ...f, max_weight_kg: Number(e.target.value) }))} /></FormField>
          <FormField label="Max Capacity Units"><input type="number" className="form-input" value={form.max_capacity_units} onChange={e => setForm(f => ({ ...f, max_capacity_units: Number(e.target.value) }))} /></FormField>
          <FormField label="Active">
            <select className="form-input" value={String(form.is_active)} onChange={e => setForm(f => ({ ...f, is_active: e.target.value === 'true' }))}>
              <option value="true">Active</option><option value="false">Inactive</option>
            </select>
          </FormField>
          <div style={{ display: 'flex', gap: 10, marginTop: 24 }}>
            <button className="btn btn-secondary" onClick={() => setShowForm(false)} style={{ flex: 1 }}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSave} disabled={saving} style={{ flex: 1 }}>{saving ? 'Saving...' : editLorry ? 'Update' : 'Create'}</button>
          </div>
        </Drawer>
      )}
      {deleteId && <ConfirmDialog title="Delete Lorry" message="Remove this vehicle from fleet?" onConfirm={handleDelete} onCancel={() => setDeleteId(null)} />}
    </div>
  );
}
