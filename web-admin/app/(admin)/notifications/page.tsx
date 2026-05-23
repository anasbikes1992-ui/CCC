'use client';

import { useEffect, useState, useCallback } from 'react';
import { getNotifications, NotificationLog } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import { EmptyState, SearchInput } from '@/components/ui';
import { Bell, Filter, ChevronLeft, ChevronRight } from 'lucide-react';

const CHANNEL_COLORS: Record<string, { color: string; bg: string }> = {
  whatsapp: { color: '#10b981', bg: 'rgba(16,185,129,0.15)' },
  sms:      { color: '#f59e0b', bg: 'rgba(245,158,11,0.15)' },
  email:    { color: '#6366f1', bg: 'rgba(99,102,241,0.15)' },
  push:     { color: '#a78bfa', bg: 'rgba(167,139,250,0.15)' },
};

const STATUS_COLORS: Record<string, { color: string; bg: string }> = {
  sent:    { color: '#10b981', bg: 'rgba(16,185,129,0.15)' },
  delivered: { color: '#6366f1', bg: 'rgba(99,102,241,0.15)' },
  failed:  { color: '#ef4444', bg: 'rgba(239,68,68,0.15)' },
  pending: { color: '#f59e0b', bg: 'rgba(245,158,11,0.15)' },
};

export default function NotificationsPage() {
  const [logs, setLogs]         = useState<NotificationLog[]>([]);
  const [meta, setMeta]         = useState({ total: 0, page: 1, last_page: 1 });
  const [loading, setLoading]   = useState(true);
  const [channel, setChannel]   = useState('');
  const [status, setStatus]     = useState('');
  const [page, setPage]         = useState(1);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await getNotifications({ channel: channel || undefined, status: status || undefined, page, limit: 30 } as Record<string, string | number | undefined>);
      setLogs(data.logs);
      setMeta(data.meta as { total: number; page: number; last_page: number });
    } catch (e) { console.error(e); }
    setLoading(false);
  }, [channel, status, page]);

  useEffect(() => { load(); }, [load]);

  // Summary counts
  const sent   = logs.filter(l => l.status === 'sent' || l.status === 'delivered').length;
  const failed = logs.filter(l => l.status === 'failed').length;

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div>
          <h1 className="page-title">Notification Log</h1>
          <p className="page-subtitle">{meta.total} total notifications · {sent} sent · {failed} failed (this page)</p>
        </div>
      </div>

      <div className="glass" style={{ padding: '14px 18px', marginBottom: 18, display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
        <Filter size={14} color="var(--text-muted)" />
        <select className="form-input" value={channel} onChange={e => { setChannel(e.target.value); setPage(1); }} style={{ maxWidth: 160 }}>
          <option value="">All Channels</option>
          <option value="whatsapp">WhatsApp</option>
          <option value="sms">SMS</option>
          <option value="email">Email</option>
          <option value="push">Push</option>
        </select>
        <select className="form-input" value={status} onChange={e => { setStatus(e.target.value); setPage(1); }} style={{ maxWidth: 160 }}>
          <option value="">All Statuses</option>
          <option value="sent">Sent</option>
          <option value="delivered">Delivered</option>
          <option value="failed">Failed</option>
          <option value="pending">Pending</option>
        </select>
      </div>

      <div className="glass">
        <table className="data-table">
          <thead>
            <tr>
              <th scope="col">Channel</th>
              <th scope="col">Template</th>
              <th scope="col">Recipient</th>
              <th scope="col">Parcel</th>
              <th scope="col">Status</th>
              <th scope="col">Sent At</th>
              <th scope="col">Error</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr><td colSpan={7} style={{ textAlign: 'center', padding: 40, color: 'var(--text-muted)' }}>Loading...</td></tr>
            ) : logs.length === 0 ? (
              <tr><td colSpan={7}><EmptyState icon={<Bell />} title="No notifications found" /></td></tr>
            ) : logs.map((l) => {
              const ch = CHANNEL_COLORS[l.channel] ?? { color: '#94a3b8', bg: 'rgba(148,163,184,0.15)' };
              const st = STATUS_COLORS[l.status] ?? { color: '#94a3b8', bg: 'rgba(148,163,184,0.15)' };
              return (
                <tr key={l.id}>
                  <td>
                    <span className="badge" style={{ color: ch.color, background: ch.bg, textTransform: 'uppercase', fontSize: 10 }}>
                      {l.channel}
                    </span>
                  </td>
                  <td style={{ fontSize: 12, fontFamily: 'monospace', color: 'var(--text-secondary)' }}>{l.template ?? '—'}</td>
                  <td style={{ fontSize: 12, fontFamily: 'monospace' }}>{l.recipient ?? '—'}</td>
                  <td>
                    {l.parcel ? (
                      <a href={`/parcels/${l.parcel_id}`} style={{ fontSize: 11, fontFamily: 'monospace', color: 'var(--accent-light)', textDecoration: 'none' }}>
                        {l.parcel.parcel_number}
                      </a>
                    ) : '—'}
                  </td>
                  <td><span className="badge" style={{ color: st.color, background: st.bg }}>{l.status}</span></td>
                  <td style={{ fontSize: 11, color: 'var(--text-muted)' }}>{formatDate(l.sent_at ?? l.created_at, 'dd MMM HH:mm')}</td>
                  <td style={{ fontSize: 11, color: 'var(--danger)', maxWidth: 160, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {l.error_message ?? '—'}
                  </td>
                </tr>
              );
            })}
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
    </div>
  );
}
