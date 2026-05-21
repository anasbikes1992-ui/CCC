'use client';

import { useEffect, useState, useCallback } from 'react';
import { getDisputes, updateDispute, type Dispute } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import { AlertTriangle, ChevronLeft, ChevronRight, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

const STATUS_COLORS: Record<string, string> = {
  open:         'bg-red-100 text-red-700',
  under_review: 'bg-amber-100 text-amber-700',
  resolved:     'bg-green-100 text-green-700',
  rejected:     'bg-gray-100 text-gray-600',
  closed:       'bg-gray-100 text-gray-500',
};

export default function DisputesPage() {
  const [disputes, setDisputes] = useState<Dispute[]>([]);
  const [meta,     setMeta]     = useState({ total: 0, page: 1, last_page: 1 });
  const [loading,  setLoading]  = useState(true);
  const [status,   setStatus]   = useState('');
  const [page,     setPage]     = useState(1);
  const [selected, setSelected] = useState<Dispute | null>(null);
  const [resolution, setResolution] = useState('');
  const [saving,   setSaving]   = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await getDisputes({ status: status || undefined, limit: 25, page } as Record<string, string | number | undefined>);
      setDisputes(res.data);
      setMeta({ total: res.meta.total, page: res.meta.page, last_page: res.meta.last_page });
    } finally { setLoading(false); }
  }, [status, page]);

  useEffect(() => { load(); }, [load]);

  async function handleUpdate(newStatus: string) {
    if (!selected) return;
    if (['resolved', 'rejected'].includes(newStatus) && !resolution.trim()) {
      toast.error('Resolution note is required');
      return;
    }
    setSaving(true);
    try {
      await updateDispute(selected.id, { status: newStatus, resolution });
      toast.success('Dispute updated');
      setSelected(null);
      setResolution('');
      load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'Failed');
    } finally { setSaving(false); }
  }

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div>
          <h1 className="page-title">Disputes</h1>
          <p className="page-subtitle">{meta.total} total disputes</p>
        </div>
      </div>

      {/* Filters */}
      <div className="flex gap-3 mb-5 flex-wrap">
        {['', 'open', 'under_review', 'resolved', 'rejected', 'closed'].map(s => (
          <button
            key={s}
            onClick={() => { setStatus(s); setPage(1); }}
            className={`px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors ${
              status === s ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:border-slate-400'
            }`}
          >
            {s === '' ? 'All' : s.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="flex items-center gap-2 text-slate-500 py-12 justify-center"><Loader2 size={20} className="animate-spin" /> Loading…</div>
      ) : disputes.length === 0 ? (
        <div className="text-center py-16 text-slate-400">
          <AlertTriangle size={40} className="mx-auto mb-3 opacity-30" />
          <p>No disputes found</p>
        </div>
      ) : (
        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-4">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="text-left px-4 py-3 font-semibold text-slate-600">Parcel</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Customer</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600">Type</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600">Status</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Opened</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {disputes.map(d => (
                <tr key={d.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-4 py-3 font-mono font-semibold text-slate-800">
                    {d.parcel?.parcel_number ?? '—'}
                  </td>
                  <td className="px-4 py-3 text-slate-600 hidden md:table-cell">{d.raised_by?.name ?? '—'}</td>
                  <td className="px-4 py-3 text-slate-600 capitalize">{d.type.replace(/_/g, ' ')}</td>
                  <td className="px-4 py-3">
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${STATUS_COLORS[d.status] ?? 'bg-gray-100 text-gray-600'}`}>
                      {d.status.replace(/_/g, ' ')}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-slate-500 hidden lg:table-cell">{formatDate(d.created_at)}</td>
                  <td className="px-4 py-3">
                    <button
                      onClick={() => { setSelected(d); setResolution(d.resolution ?? ''); }}
                      className="text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                    >
                      Review
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      {meta.last_page > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-slate-500">Page {meta.page} of {meta.last_page}</p>
          <div className="flex gap-2">
            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={meta.page === 1} className="p-2 rounded-lg border border-slate-300 disabled:opacity-40 hover:bg-slate-50">
              <ChevronLeft size={16} />
            </button>
            <button onClick={() => setPage(p => Math.min(meta.last_page, p + 1))} disabled={meta.page === meta.last_page} className="p-2 rounded-lg border border-slate-300 disabled:opacity-40 hover:bg-slate-50">
              <ChevronRight size={16} />
            </button>
          </div>
        </div>
      )}

      {/* Review drawer */}
      {selected && (
        <div className="fixed inset-0 z-50 flex">
          <div className="flex-1 bg-black/40" onClick={() => setSelected(null)} />
          <div className="w-full max-w-md bg-white h-full overflow-y-auto shadow-xl flex flex-col">
            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-200">
              <h2 className="font-bold text-slate-900">Review Dispute</h2>
              <button onClick={() => setSelected(null)} className="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <div className="px-5 py-4 flex-1 space-y-4">
              <div>
                <p className="text-xs text-slate-500 font-medium mb-1">Parcel</p>
                <p className="font-mono font-bold text-slate-800">{selected.parcel?.parcel_number}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500 font-medium mb-1">Type</p>
                <p className="capitalize text-slate-700">{selected.type.replace(/_/g, ' ')}</p>
              </div>
              <div>
                <p className="text-xs text-slate-500 font-medium mb-1">Description</p>
                <p className="text-slate-700 text-sm leading-relaxed">{selected.description}</p>
              </div>
              <div>
                <label className="block text-xs text-slate-500 font-medium mb-1">Resolution Note</label>
                <textarea
                  value={resolution}
                  onChange={e => setResolution(e.target.value)}
                  rows={4}
                  className="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"
                  placeholder="Explain the outcome or decision…"
                />
              </div>
            </div>

            <div className="px-5 pb-6 pt-2 border-t border-slate-100 flex flex-col gap-2">
              {['under_review', 'resolved', 'rejected', 'closed'].map(s => (
                <button
                  key={s}
                  onClick={() => handleUpdate(s)}
                  disabled={saving}
                  className={`py-2.5 rounded-xl text-sm font-semibold transition-colors ${
                    s === 'resolved'     ? 'bg-green-600 hover:bg-green-500 text-white' :
                    s === 'rejected'     ? 'bg-red-600 hover:bg-red-500 text-white' :
                    s === 'under_review' ? 'bg-amber-500 hover:bg-amber-400 text-white' :
                    'bg-slate-200 hover:bg-slate-300 text-slate-700'
                  } disabled:opacity-60`}
                >
                  {saving ? <Loader2 size={14} className="animate-spin inline mr-1" /> : null}
                  Mark as {s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}
                </button>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
