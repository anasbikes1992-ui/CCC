'use client';

import { useEffect, useState, useCallback } from 'react';
import { getTickets, getTicket, updateTicket, replyToTicket, type SupportTicket } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import { MessageSquare, Loader2, ChevronLeft, ChevronRight, Send } from 'lucide-react';
import { toast } from 'sonner';

const PRIORITY_COLORS: Record<string, string> = {
  low:    'bg-slate-100 text-slate-500',
  medium: 'bg-blue-100 text-blue-600',
  high:   'bg-orange-100 text-orange-600',
  urgent: 'bg-red-100 text-red-700',
};

const STATUS_COLORS: Record<string, string> = {
  open:        'bg-red-100 text-red-700',
  pending:     'bg-amber-100 text-amber-700',
  in_progress: 'bg-blue-100 text-blue-700',
  resolved:    'bg-green-100 text-green-700',
  closed:      'bg-gray-100 text-gray-500',
};

export default function TicketsPage() {
  const [tickets,  setTickets]  = useState<SupportTicket[]>([]);
  const [meta,     setMeta]     = useState({ total: 0, page: 1, last_page: 1 });
  const [loading,  setLoading]  = useState(true);
  const [status,   setStatus]   = useState('');
  const [page,     setPage]     = useState(1);
  const [selected, setSelected] = useState<SupportTicket | null>(null);
  const [thread,   setThread]   = useState<SupportTicket | null>(null);
  const [reply,    setReply]    = useState('');
  const [sending,  setSending]  = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await getTickets({ status: status || undefined, limit: 25, page } as Record<string, string | number | undefined>);
      setTickets(res.data);
      setMeta({ total: res.meta.total, page: res.meta.page, last_page: res.meta.last_page });
    } finally { setLoading(false); }
  }, [status, page]);

  useEffect(() => { load(); }, [load]);

  async function openThread(ticket: SupportTicket) {
    setSelected(ticket);
    const full = await getTicket(ticket.id);
    setThread(full);
  }

  async function handleReply() {
    if (!selected || !reply.trim()) return;
    setSending(true);
    try {
      await replyToTicket(selected.id, reply);
      setReply('');
      const full = await getTicket(selected.id);
      setThread(full);
      toast.success('Reply sent');
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'Failed');
    } finally { setSending(false); }
  }

  async function handleStatusChange(newStatus: string) {
    if (!selected) return;
    try {
      await updateTicket(selected.id, { status: newStatus });
      toast.success('Status updated');
      setSelected(null);
      setThread(null);
      load();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : 'Failed');
    }
  }

  return (
    <div className="animate-fade-in">
      <div className="page-header">
        <div>
          <h1 className="page-title">Support Tickets</h1>
          <p className="page-subtitle">{meta.total} total tickets</p>
        </div>
      </div>

      {/* Status tabs */}
      <div className="flex gap-2 mb-5 flex-wrap">
        {['', 'open', 'pending', 'in_progress', 'resolved', 'closed'].map(s => (
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
      ) : tickets.length === 0 ? (
        <div className="text-center py-16 text-slate-400">
          <MessageSquare size={40} className="mx-auto mb-3 opacity-30" />
          <p>No tickets found</p>
        </div>
      ) : (
        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-4">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="text-left px-4 py-3 font-semibold text-slate-600">Subject</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Customer</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600">Priority</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600">Status</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Opened</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {tickets.map(t => (
                <tr key={t.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-4 py-3">
                    <p className="font-medium text-slate-800 truncate max-w-xs">{t.subject}</p>
                    {t.parcel && (
                      <p className="text-xs text-slate-400 font-mono">{t.parcel.parcel_number}</p>
                    )}
                  </td>
                  <td className="px-4 py-3 text-slate-600 hidden md:table-cell">{t.user?.name ?? '—'}</td>
                  <td className="px-4 py-3">
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full capitalize ${PRIORITY_COLORS[t.priority] ?? 'bg-gray-100 text-gray-600'}`}>
                      {t.priority}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${STATUS_COLORS[t.status] ?? 'bg-gray-100 text-gray-600'}`}>
                      {t.status.replace(/_/g, ' ')}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-slate-500 hidden lg:table-cell">{formatDate(t.created_at)}</td>
                  <td className="px-4 py-3">
                    <button onClick={() => openThread(t)} className="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                      Open
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
            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={meta.page === 1} className="p-2 rounded-lg border border-slate-300 disabled:opacity-40 hover:bg-slate-50"><ChevronLeft size={16} /></button>
            <button onClick={() => setPage(p => Math.min(meta.last_page, p + 1))} disabled={meta.page === meta.last_page} className="p-2 rounded-lg border border-slate-300 disabled:opacity-40 hover:bg-slate-50"><ChevronRight size={16} /></button>
          </div>
        </div>
      )}

      {/* Thread drawer */}
      {selected && (
        <div className="fixed inset-0 z-50 flex">
          <div className="flex-1 bg-black/40" onClick={() => { setSelected(null); setThread(null); }} />
          <div className="w-full max-w-lg bg-white h-full overflow-y-auto shadow-xl flex flex-col">
            <div className="flex items-start justify-between px-5 py-4 border-b border-slate-200">
              <div>
                <h2 className="font-bold text-slate-900 text-sm leading-tight">{selected.subject}</h2>
                <p className="text-xs text-slate-500 mt-0.5">{selected.user?.name} · {selected.user?.email}</p>
                {selected.parcel && <p className="text-xs text-slate-400 font-mono">{selected.parcel.parcel_number}</p>}
              </div>
              <button onClick={() => { setSelected(null); setThread(null); }} className="text-slate-400 hover:text-slate-600 ml-3 shrink-0">✕</button>
            </div>

            {/* Status + priority controls */}
            <div className="px-5 py-3 border-b border-slate-100 flex gap-2 flex-wrap">
              {['in_progress', 'pending', 'resolved', 'closed'].map(s => (
                <button
                  key={s}
                  onClick={() => handleStatusChange(s)}
                  className={`text-xs font-medium px-2.5 py-1 rounded-lg border transition-colors ${
                    selected.status === s
                      ? 'bg-slate-900 text-white border-slate-900'
                      : 'bg-white text-slate-600 border-slate-300 hover:border-slate-500'
                  }`}
                >
                  {s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}
                </button>
              ))}
            </div>

            {/* Message thread */}
            <div className="flex-1 px-5 py-4 space-y-4 overflow-y-auto">
              {!thread ? (
                <div className="flex items-center gap-2 text-slate-400 justify-center py-8"><Loader2 size={16} className="animate-spin" /> Loading thread…</div>
              ) : thread.messages?.length === 0 ? (
                <p className="text-slate-400 text-sm text-center py-8">No messages yet</p>
              ) : thread.messages?.map(msg => {
                const isAdmin = msg.sender?.id !== thread.user?.id;
                return (
                  <div key={msg.id} className={`flex ${isAdmin ? 'justify-end' : 'justify-start'}`}>
                    <div className={`max-w-sm rounded-2xl px-4 py-3 text-sm ${isAdmin ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-800'}`}>
                      <p className="font-semibold text-xs mb-1 opacity-70">{msg.sender?.name}</p>
                      <p className="leading-relaxed">{msg.body}</p>
                      <p className={`text-xs mt-1.5 opacity-60`}>{formatDate(msg.sent_at)}</p>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Reply box */}
            {!['resolved', 'closed'].includes(selected.status) && (
              <div className="px-5 pb-5 pt-3 border-t border-slate-100">
                <div className="flex gap-2">
                  <textarea
                    value={reply}
                    onChange={e => setReply(e.target.value)}
                    rows={3}
                    placeholder="Write a reply…"
                    className="flex-1 border border-slate-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"
                    onKeyDown={e => { if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) handleReply(); }}
                  />
                  <button
                    onClick={handleReply}
                    disabled={!reply.trim() || sending}
                    className="px-3 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-xl transition-colors self-end"
                  >
                    {sending ? <Loader2 size={16} className="animate-spin" /> : <Send size={16} />}
                  </button>
                </div>
                <p className="text-xs text-slate-400 mt-1">⌘/Ctrl+Enter to send</p>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
