'use client';

import { useEffect, useState, useCallback } from 'react';
import { getUsers, createUser, updateUser, deleteUser, User } from '@/lib/api';
import { formatDate, USER_ROLES } from '@/lib/utils';
import { RoleBadge } from '@/components/StatusBadge';
import { ConfirmDialog, Drawer, FormField, EmptyState, SearchInput } from '@/components/ui';
import { Users, Plus, Trash2, Edit2, Filter, ChevronLeft, ChevronRight } from 'lucide-react';

interface UserForm { full_name: string; phone: string; email: string; password: string; role: string; }
const DEFAULT_FORM: UserForm = { full_name: '', phone: '', email: '', password: '', role: 'customer' };

export default function UsersPage() {
  const [users, setUsers]       = useState<User[]>([]);
  const [meta, setMeta]         = useState({ total: 0, page: 1, last_page: 1 });
  const [loading, setLoading]   = useState(true);
  const [search, setSearch]     = useState('');
  const [roleFilter, setRole]   = useState('');
  const [page, setPage]         = useState(1);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [editUser, setEditUser] = useState<User | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm]         = useState<UserForm>(DEFAULT_FORM);
  const [saving, setSaving]     = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await getUsers({ search: search || undefined, role: roleFilter || undefined, page, limit: 25 } as Record<string, string | number | undefined>);
      setUsers(data.users);
      setMeta(data.meta as { total: number; page: number; last_page: number });
    } catch (e) { console.error(e); }
    setLoading(false);
  }, [search, roleFilter, page]);

  useEffect(() => { load(); }, [load]);

  const openCreate = () => { setForm(DEFAULT_FORM); setEditUser(null); setShowForm(true); };
  const openEdit   = (u: User) => {
    setEditUser(u);
    setForm({ full_name: u.full_name, phone: u.phone, email: u.email ?? '', password: '', role: u.role });
    setShowForm(true);
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      const payload = { ...form, email: form.email || undefined, password: form.password || undefined };
      if (editUser) await updateUser(editUser.id, payload as Record<string, unknown>);
      else          await createUser(payload as Record<string, unknown>);
      setShowForm(false);
      load();
    } catch (e) { alert((e as Error).message); }
    setSaving(false);
  };

  const handleDelete = async () => {
    if (!deleteId) return;
    await deleteUser(deleteId);
    setDeleteId(null);
    load();
  };

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div>
          <h1 className="page-title">Users</h1>
          <p className="page-subtitle">{meta.total} registered accounts</p>
        </div>
        <button className="btn btn-primary" onClick={openCreate}><Plus size={14} /> New User</button>
      </div>

      <div className="glass" style={{ padding: '14px 18px', marginBottom: 18, display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
        <Filter size={14} color="var(--text-muted)" />
        <SearchInput value={search} onChange={(v) => { setSearch(v); setPage(1); }} placeholder="Search name, phone, email..." />
        <select className="form-input" value={roleFilter} onChange={(e) => { setRole(e.target.value); setPage(1); }} style={{ maxWidth: 180 }}>
          <option value="">All Roles</option>
          {USER_ROLES.map(r => <option key={r.value} value={r.value}>{r.label}</option>)}
        </select>
      </div>

      <div className="glass">
        <table className="data-table">
          <thead>
            <tr><th>Name</th><th>Phone</th><th>Email</th><th>Role</th><th>Parcels</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            {loading ? (
              <tr><td colSpan={7} style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>Loading...</td></tr>
            ) : users.length === 0 ? (
              <tr><td colSpan={7}><EmptyState icon={<Users />} title="No users found" /></td></tr>
            ) : users.map((u) => (
              <tr key={u.id}>
                <td style={{ fontWeight: 500 }}>{u.full_name}</td>
                <td style={{ fontFamily: 'monospace', fontSize: 12 }}>{u.phone}</td>
                <td style={{ fontSize: 12, color: 'var(--text-secondary)' }}>{u.email ?? '—'}</td>
                <td><RoleBadge role={u.role} /></td>
                <td style={{ fontSize: 13 }}>{u.parcels_count ?? 0}</td>
                <td style={{ fontSize: 12, color: 'var(--text-muted)' }}>{formatDate(u.created_at, 'dd MMM yyyy')}</td>
                <td>
                  <div style={{ display: 'flex', gap: 6 }}>
                    <button className="btn btn-secondary btn-sm" onClick={() => openEdit(u)}><Edit2 size={13} /></button>
                    <button className="btn btn-danger btn-sm" onClick={() => setDeleteId(u.id)}><Trash2 size={13} /></button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
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
        <Drawer title={editUser ? 'Edit User' : 'New User'} onClose={() => setShowForm(false)}>
          {(['full_name', 'phone'] as const).map(k => (
            <FormField key={k} label={k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())} required>
              <input className="form-input" value={form[k]} onChange={(e) => setForm(f => ({ ...f, [k]: e.target.value }))} />
            </FormField>
          ))}
          <FormField label="Email">
            <input type="email" className="form-input" value={form.email} onChange={(e) => setForm(f => ({ ...f, email: e.target.value }))} />
          </FormField>
          <FormField label={editUser ? 'New Password (leave blank to keep)' : 'Password'} required={!editUser}>
            <input type="password" className="form-input" value={form.password} onChange={(e) => setForm(f => ({ ...f, password: e.target.value }))} />
          </FormField>
          <FormField label="Role" required>
            <select className="form-input" value={form.role} onChange={(e) => setForm(f => ({ ...f, role: e.target.value }))}>
              {USER_ROLES.map(r => <option key={r.value} value={r.value}>{r.label}</option>)}
            </select>
          </FormField>
          <div style={{ display: 'flex', gap: 10, marginTop: 24 }}>
            <button className="btn btn-secondary" onClick={() => setShowForm(false)} style={{ flex: 1 }}>Cancel</button>
            <button className="btn btn-primary" onClick={handleSave} disabled={saving} style={{ flex: 1 }}>
              {saving ? 'Saving...' : editUser ? 'Update User' : 'Create User'}
            </button>
          </div>
        </Drawer>
      )}

      {deleteId && (
        <ConfirmDialog title="Delete User" message="Soft-delete this user account?" onConfirm={handleDelete} onCancel={() => setDeleteId(null)} />
      )}
    </div>
  );
}
