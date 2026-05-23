'use client';

import { useEffect, useState, useCallback } from 'react';
import { getDrivers, createDriver, updateDriver, deleteDriver, Driver } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import { ActiveBadge } from '@/components/StatusBadge';
import { ConfirmDialog, Drawer, FormField, EmptyState, SearchInput } from '@/components/ui';
import { UserCog, Plus, Trash2, Edit2, AlertTriangle } from 'lucide-react';
import { isAfter, parseISO, addDays } from 'date-fns';

export default function DriversPage() {
  const [drivers, setDrivers]   = useState<Driver[]>([]);
  const [loading, setLoading]   = useState(true);
  const [search, setSearch]     = useState('');
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [editDrv, setEditDrv]   = useState<Driver | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving]     = useState(false);
  const [form, setForm]         = useState({ full_name: '', phone: '', password: '', license_number: '', license_expires_at: '', is_active: true });

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await getDrivers({ search: search || undefined } as Record<string, string | number | undefined>);
      setDrivers(data.drivers);
    } catch (e) { console.error(e); }
    setLoading(false);
  }, [search]);

  useEffect(() => { load(); }, [load]);

  const openCreate = () => {
    setForm({ full_name: '', phone: '', password: '', license_number: '', license_expires_at: '', is_active: true });
    setEditDrv(null); setShowForm(true);
  };

  const openEdit = (d: Driver) => {
    setEditDrv(d);
    setForm({ full_name: d.user?.full_name ?? '', phone: d.user?.phone ?? '', password: '', license_number: d.license_number, license_expires_at: d.license_expires_at?.slice(0, 10) ?? '', is_active: d.is_active });
    setShowForm(true);
  };

  const handleSave = async () => {
    if (!form.license_number.trim()) {
      alert('License number is required.');
      return;
    }

    if (!form.license_expires_at) {
      alert('License expiry date is required.');
      return;
    }

    if (!editDrv) {
      if (!form.full_name.trim()) {
        alert('Full name is required.');
        return;
      }

      if (!/^\+\d{10,15}$/.test(form.phone)) {
        alert('Phone must be in E.164 format (example: +94771234567).');
        return;
      }

      if (form.password.length < 8) {
        alert('Password must be at least 8 characters.');
        return;
      }
    }

    setSaving(true);
    try {
      if (editDrv) {
        await updateDriver(editDrv.id, { license_number: form.license_number, license_expires_at: form.license_expires_at, is_active: form.is_active });
      } else {
        await createDriver({ user: { full_name: form.full_name, phone: form.phone, password: form.password }, license_number: form.license_number, license_expires_at: form.license_expires_at, is_active: form.is_active });
      }
      setShowForm(false); load();
    } catch (e) { alert((e as Error).message); }
    setSaving(false);
  };

  const handleDelete = async () => {
    if (!deleteId) return;
    await deleteDriver(deleteId); setDeleteId(null); load();
  };

  const isExpiringSoon = (dateStr: string) => {
    try { return !isAfter(parseISO(dateStr), addDays(new Date(), 30)); } catch { return false; }
  };

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div><h1 className="page-title">Drivers</h1><p className="page-subtitle">{drivers.length} drivers registered</p></div>
        <button className="btn btn-primary" onClick={openCreate}><Plus size={14} /> New Driver</button>
      </div>

      <div className="glass" style={{ padding: '14px 18px', marginBottom: 18, display: 'flex', gap: 12 }}>
        <SearchInput value={search} onChange={(v) => setSearch(v)} placeholder="Search by name or phone..." />
      </div>

      <div className="glass">
        <table className="data-table">
          <thead><tr><th scope="col">Name</th><th scope="col">Phone</th><th scope="col">License #</th><th scope="col">License Expires</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
          <tbody>
            {loading ? <tr><td colSpan={6} style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>Loading...</td></tr>
            : drivers.length === 0 ? <tr><td colSpan={6}><EmptyState icon={<UserCog />} title="No drivers" /></td></tr>
            : drivers.map((d) => (
              <tr key={d.id}>
                <td style={{ fontWeight: 500 }}>{d.user?.full_name ?? '—'}</td>
                <td style={{ fontFamily: 'monospace', fontSize: 12 }}>{d.user?.phone ?? '—'}</td>
                <td style={{ fontFamily: 'monospace', fontSize: 12 }}>{d.license_number}</td>
                <td>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                    <span style={{ fontSize: 12 }}>{formatDate(d.license_expires_at, 'dd MMM yyyy')}</span>
                    {isExpiringSoon(d.license_expires_at) && (
                      <span title="Expiring soon!" aria-label="Expiring soon">
                        <AlertTriangle size={13} color="var(--warning)" />
                      </span>
                    )}
                  </div>
                </td>
                <td><ActiveBadge active={d.is_active} /></td>
                <td>
                  <div style={{ display: 'flex', gap: 6 }}>
                    <button className="btn btn-secondary btn-sm" onClick={() => openEdit(d)}><Edit2 size={13} /></button>
                    <button className="btn btn-danger btn-sm" onClick={() => setDeleteId(d.id)}><Trash2 size={13} /></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {showForm && (
        <Drawer title={editDrv ? 'Edit Driver' : 'New Driver'} onClose={() => setShowForm(false)}>
          {!editDrv && <>
            <FormField label="Full Name" required><input className="form-input" value={form.full_name} onChange={e => setForm(f => ({ ...f, full_name: e.target.value }))} /></FormField>
            <FormField label="Phone" required><input className="form-input" value={form.phone} onChange={e => setForm(f => ({ ...f, phone: e.target.value }))} /></FormField>
            <FormField label="Password" required><input type="password" className="form-input" value={form.password} onChange={e => setForm(f => ({ ...f, password: e.target.value }))} /></FormField>
          </>}
          <FormField label="License Number" required><input className="form-input" value={form.license_number} onChange={e => setForm(f => ({ ...f, license_number: e.target.value }))} /></FormField>
          <FormField label="License Expiry" required><input type="date" className="form-input" value={form.license_expires_at} onChange={e => setForm(f => ({ ...f, license_expires_at: e.target.value }))} /></FormField>
          <FormField label="Active Status">
            <select className="form-input" value={String(form.is_active)} onChange={e => setForm(f => ({ ...f, is_active: e.target.value === 'true' }))}>
              <option value="true">Active</option>
              <option value="false">Inactive</option>
            </select>
          </FormField>
          <div style={{ display: 'flex', gap: 10, marginTop: 24 }}>
            <button className="btn btn-secondary" onClick={() => setShowForm(false)} style={{ flex: 1 }}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSave} disabled={saving} style={{ flex: 1 }}>{saving ? 'Saving...' : editDrv ? 'Update' : 'Create'}</button>
          </div>
        </Drawer>
      )}
      {deleteId && <ConfirmDialog title="Delete Driver" message="Remove this driver?" onConfirm={handleDelete} onCancel={() => setDeleteId(null)} />}
    </div>
  );
}
