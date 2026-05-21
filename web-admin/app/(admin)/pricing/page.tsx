'use client';

import { useEffect, useState } from 'react';
import { getPricing, createPricing, updatePricing, deletePricing, getRoutes, PricingEntry, Route } from '@/lib/api';
import { formatCurrency } from '@/lib/utils';
import { ConfirmDialog, Drawer, FormField, EmptyState } from '@/components/ui';
import { DollarSign, Plus, Trash2, Edit2 } from 'lucide-react';

const SIZE_CODES = ['S', 'M', 'L', 'XL', 'Bale'];

export default function PricingPage() {
  const [pricing, setPricing]   = useState<PricingEntry[]>([]);
  const [routes, setRoutes]     = useState<Route[]>([]);
  const [loading, setLoading]   = useState(true);
  const [routeFilter, setRoute] = useState('');
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [editEntry, setEditEntry] = useState<PricingEntry | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving]     = useState(false);
  const [form, setForm]         = useState({
    route_id: '', package_size_id: '', base_price_lkr: 0,
    surcharges: { doorstep_pickup: 0, doorstep_drop: 0, express: 0, insurance_pct: 1.5, cod_min: 100, cod_pct: 3 },
  });
  const [packageSizes, setPackageSizes] = useState<{ id: string; code: string; name: string }[]>([]);

  const load = async () => {
    setLoading(true);
    try {
      const [p, r] = await Promise.all([
        getPricing(routeFilter ? { route_id: routeFilter } : undefined),
        getRoutes(),
      ]);
      setPricing(p); setRoutes(r);
      // Extract unique package sizes from pricing data
      const sizes = Array.from(new Map(p.filter(e => e.package_size).map(e => [e.package_size!.id, e.package_size!])).values());
      if (sizes.length > 0) setPackageSizes(sizes as { id: string; code: string; name: string }[]);
    } catch (e) { console.error(e); }
    setLoading(false);
  };
  useEffect(() => { load(); }, [routeFilter]);

  const openCreate = () => {
    setForm({ route_id: '', package_size_id: '', base_price_lkr: 0, surcharges: { doorstep_pickup: 0, doorstep_drop: 0, express: 0, insurance_pct: 1.5, cod_min: 100, cod_pct: 3 } });
    setEditEntry(null); setShowForm(true);
  };

  const openEdit = (e: PricingEntry) => {
    setEditEntry(e);
    setForm({
      route_id: e.route_id, package_size_id: e.package_size_id, base_price_lkr: Number(e.base_price_lkr),
      surcharges: { doorstep_pickup: 0, doorstep_drop: 0, express: 0, insurance_pct: 1.5, cod_min: 100, cod_pct: 3, ...e.surcharges },
    });
    setShowForm(true);
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      if (editEntry) await updatePricing(editEntry.id, { base_price_lkr: form.base_price_lkr, surcharges: form.surcharges });
      else           await createPricing(form as Record<string, unknown>);
      setShowForm(false); load();
    } catch (e) { alert((e as Error).message); }
    setSaving(false);
  };
  const handleDelete = async () => { if (!deleteId) return; await deletePricing(deleteId); setDeleteId(null); load(); };

  // Group pricing by route for matrix view
  const byRoute = routes.map(route => ({
    route,
    entries: pricing.filter(p => p.route_id === route.id),
  })).filter(g => g.entries.length > 0 || routeFilter === g.route.id);

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div><h1 className="page-title">Pricing Matrix</h1><p className="page-subtitle">Base prices per route × package size</p></div>
        <button className="btn btn-primary" onClick={openCreate}><Plus size={14} /> New Entry</button>
      </div>

      <div className="glass" style={{ padding: '14px 18px', marginBottom: 18, display: 'flex', gap: 12, alignItems: 'center' }}>
        <select className="form-input" value={routeFilter} onChange={e => setRoute(e.target.value)} style={{ maxWidth: 250 }}>
          <option value="">All Routes</option>
          {routes.map(r => <option key={r.id} value={r.id}>{r.display_name}</option>)}
        </select>
      </div>

      {loading ? <div className="glass" style={{ padding: 40, textAlign: 'center', color: 'var(--text-muted)' }}>Loading...</div>
      : pricing.length === 0 ? <div className="glass"><EmptyState icon={<DollarSign />} title="No pricing entries" subtitle="Click + New Entry to add pricing" /></div>
      : byRoute.map(({ route, entries }) => (
        <div key={route.id} className="glass" style={{ marginBottom: 18, overflow: 'hidden' }}>
          <div style={{ padding: '14px 18px', borderBottom: '1px solid var(--border)', background: 'rgba(255,255,255,0.02)' }}>
            <div style={{ fontSize: 14, fontWeight: 700 }}>{route.display_name}</div>
            <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 2 }}>{route.code}</div>
          </div>
          <table className="data-table">
            <thead>
              <tr>
                <th>Size</th>
                <th>Base Price</th>
                <th>Doorstep Pickup</th>
                <th>Doorstep Drop</th>
                <th>Express</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {entries.sort((a, b) => SIZE_CODES.indexOf(a.package_size?.code ?? '') - SIZE_CODES.indexOf(b.package_size?.code ?? '')).map(e => (
                <tr key={e.id}>
                  <td>
                    <span className="badge" style={{ color: '#a78bfa', background: 'rgba(167,139,250,0.15)', fontSize: 13, fontWeight: 700 }}>
                      {e.package_size?.code ?? '—'}
                    </span>
                  </td>
                  <td style={{ fontWeight: 700, color: 'var(--accent-light)' }}>{formatCurrency(e.base_price_lkr)}</td>
                  <td style={{ color: 'var(--text-secondary)' }}>{formatCurrency((e.surcharges as Record<string, number>)?.doorstep_pickup ?? 0)}</td>
                  <td style={{ color: 'var(--text-secondary)' }}>{formatCurrency((e.surcharges as Record<string, number>)?.doorstep_drop ?? 0)}</td>
                  <td style={{ color: 'var(--text-secondary)' }}>{formatCurrency((e.surcharges as Record<string, number>)?.express ?? 0)}</td>
                  <td>
                    <div style={{ display: 'flex', gap: 6 }}>
                      <button className="btn btn-secondary btn-sm" onClick={() => openEdit(e)}><Edit2 size={13} /></button>
                      <button className="btn btn-danger btn-sm" onClick={() => setDeleteId(e.id)}><Trash2 size={13} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ))}

      {showForm && (
        <Drawer title={editEntry ? 'Edit Pricing' : 'New Pricing Entry'} onClose={() => setShowForm(false)} width={520}>
          {!editEntry && <>
            <FormField label="Route" required>
              <select className="form-input" value={form.route_id} onChange={e => setForm(f => ({ ...f, route_id: e.target.value }))}>
                <option value="">Select route...</option>
                {routes.map(r => <option key={r.id} value={r.id}>{r.display_name}</option>)}
              </select>
            </FormField>
            <FormField label="Package Size" required>
              <select className="form-input" value={form.package_size_id} onChange={e => setForm(f => ({ ...f, package_size_id: e.target.value }))}>
                <option value="">Select size...</option>
                {packageSizes.map(s => <option key={s.id} value={s.id}>{s.code} — {s.name}</option>)}
              </select>
            </FormField>
          </>}
          <FormField label="Base Price (LKR)" required>
            <input type="number" className="form-input" value={form.base_price_lkr} onChange={e => setForm(f => ({ ...f, base_price_lkr: Number(e.target.value) }))} />
          </FormField>
          <div style={{ fontSize: 12, fontWeight: 600, color: 'var(--text-muted)', marginBottom: 10, marginTop: 4, textTransform: 'uppercase', letterSpacing: '0.05em' }}>Surcharges (LKR)</div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
            {[['doorstep_pickup', 'Doorstep Pickup'], ['doorstep_drop', 'Doorstep Drop'], ['express', 'Express']].map(([k, l]) => (
              <FormField key={k} label={l}>
                <input type="number" className="form-input" value={(form.surcharges as Record<string, number>)[k] ?? 0}
                  onChange={e => setForm(f => ({ ...f, surcharges: { ...f.surcharges, [k]: Number(e.target.value) } }))} />
              </FormField>
            ))}
            <FormField label="Insurance %">
              <input type="number" step="0.1" className="form-input" value={form.surcharges.insurance_pct}
                onChange={e => setForm(f => ({ ...f, surcharges: { ...f.surcharges, insurance_pct: Number(e.target.value) } }))} />
            </FormField>
          </div>
          <div style={{ display: 'flex', gap: 10, marginTop: 24 }}>
            <button className="btn btn-secondary" onClick={() => setShowForm(false)} style={{ flex: 1 }}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSave} disabled={saving} style={{ flex: 1 }}>{saving ? 'Saving...' : editEntry ? 'Update' : 'Create'}</button>
          </div>
        </Drawer>
      )}
      {deleteId && <ConfirmDialog title="Delete Pricing" message="Remove this pricing entry?" onConfirm={handleDelete} onCancel={() => setDeleteId(null)} />}
    </div>
  );
}
