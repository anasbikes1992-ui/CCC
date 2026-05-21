'use client';

import { useEffect, useState, useCallback } from 'react';
import { getParcels, deleteParcel, Parcel } from '@/lib/api';
import { formatDate, formatCurrency, PARCEL_STATUSES } from '@/lib/utils';
import { ParcelStatusBadge } from '@/components/StatusBadge';
import { ConfirmDialog, SearchInput, EmptyState } from '@/components/ui';
import { Package, Trash2, Eye, Filter, ChevronLeft, ChevronRight } from 'lucide-react';
import { useRouter } from 'next/navigation';

export default function ParcelsPage() {
  const router = useRouter();
  const [parcels, setParcels]     = useState<Parcel[]>([]);
  const [meta, setMeta]           = useState({ total: 0, page: 1, last_page: 1 });
  const [loading, setLoading]     = useState(true);
  const [search, setSearch]       = useState('');
  const [statusFilter, setStatus] = useState('');
  const [page, setPage]           = useState(1);
  const [deleteId, setDeleteId]   = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await getParcels({
        search: search || undefined,
        status: statusFilter || undefined,
        page,
        limit: 25,
      } as Record<string, string | number | undefined>);
      setParcels(data.parcels);
      setMeta(data.meta as { total: number; page: number; last_page: number });
    } catch (e) { console.error(e); }
    setLoading(false);
  }, [search, statusFilter, page]);

  useEffect(() => { load(); }, [load]);

  const handleDelete = async () => {
    if (!deleteId) return;
    await deleteParcel(deleteId);
    setDeleteId(null);
    load();
  };

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div>
          <h1 className="page-title">Parcels</h1>
          <p className="page-subtitle">{meta.total.toLocaleString()} total parcels</p>
        </div>
      </div>

      {/* Filters */}
      <div className="glass" style={{ padding: '14px 18px', marginBottom: 18, display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
        <Filter size={14} color="var(--text-muted)" />
        <SearchInput value={search} onChange={(v) => { setSearch(v); setPage(1); }} placeholder="Search by parcel # or customer..." />
        <select
          className="form-input"
          value={statusFilter}
          onChange={(e) => { setStatus(e.target.value); setPage(1); }}
          style={{ maxWidth: 200 }}
        >
          <option value="">All Statuses</option>
          {PARCEL_STATUSES.map((s) => <option key={s} value={s}>{s.replace(/_/g, ' ')}</option>)}
        </select>
        <span style={{ fontSize: 12, color: 'var(--text-muted)', marginLeft: 'auto' }}>
          {meta.total} results
        </span>
      </div>

      {/* Table */}
      <div className="glass">
        <div style={{ overflowX: 'auto' }}>
          <table className="data-table">
            <thead>
              <tr>
                <th>Parcel #</th>
                <th>Customer</th>
                <th>Route</th>
                <th>Size</th>
                <th>Status</th>
                <th>Total</th>
                <th>Created</th>
                <th style={{ width: 80 }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr><td colSpan={8} style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>Loading...</td></tr>
              ) : parcels.length === 0 ? (
                <tr><td colSpan={8}><EmptyState icon={<Package />} title="No parcels found" subtitle="Try adjusting your filters" /></td></tr>
              ) : parcels.map((p) => (
                <tr key={p.id}>
                  <td>
                    <span style={{ fontFamily: 'monospace', fontSize: 12, color: 'var(--accent-light)', cursor: 'pointer' }}
                      onClick={() => router.push(`/parcels/${p.id}`)}>
                      {p.parcel_number}
                    </span>
                  </td>
                  <td>
                    <div style={{ fontSize: 13, fontWeight: 500 }}>{p.customer?.full_name ?? '—'}</div>
                    <div style={{ fontSize: 11, color: 'var(--text-muted)' }}>{p.customer?.phone}</div>
                  </td>
                  <td style={{ fontSize: 12, color: 'var(--text-secondary)' }}>{p.route?.display_name ?? '—'}</td>
                  <td>
                    <span className="badge" style={{ color: '#a78bfa', background: 'rgba(167,139,250,0.15)' }}>
                      {p.package_size?.code ?? '—'}
                    </span>
                  </td>
                  <td><ParcelStatusBadge status={p.status} /></td>
                  <td style={{ fontWeight: 600 }}>{formatCurrency(p.total_price_lkr)}</td>
                  <td style={{ fontSize: 12, color: 'var(--text-muted)' }}>{formatDate(p.created_at, 'dd MMM HH:mm')}</td>
                  <td>
                    <div style={{ display: 'flex', gap: 6 }}>
                      <button className="btn btn-secondary btn-sm" onClick={() => router.push(`/parcels/${p.id}`)}>
                        <Eye size={13} />
                      </button>
                      <button className="btn btn-danger btn-sm" onClick={() => setDeleteId(p.id)}>
                        <Trash2 size={13} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {meta.last_page > 1 && (
          <div style={{ padding: '12px 16px', borderTop: '1px solid var(--border)', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: 12, color: 'var(--text-muted)' }}>
              Page {meta.page} of {meta.last_page}
            </span>
            <div style={{ display: 'flex', gap: 8 }}>
              <button className="btn btn-secondary btn-sm" onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page <= 1}>
                <ChevronLeft size={13} />
              </button>
              <button className="btn btn-secondary btn-sm" onClick={() => setPage(p => Math.min(meta.last_page, p + 1))} disabled={page >= meta.last_page}>
                <ChevronRight size={13} />
              </button>
            </div>
          </div>
        )}
      </div>

      {deleteId && (
        <ConfirmDialog
          title="Delete Parcel"
          message="This will soft-delete the parcel. The parcel data is retained but hidden from normal views."
          onConfirm={handleDelete}
          onCancel={() => setDeleteId(null)}
        />
      )}
    </div>
  );
}
