'use client';

import { useEffect, useState } from 'react';
import { getInventory, type ParcelSummary } from '@/lib/api';
import { StatusBadge } from '@/components/StatusBadge';
import { Package, Loader2, RefreshCw } from 'lucide-react';

export default function InventoryPage() {
  const [parcels, setParcels] = useState<ParcelSummary[]>([]);
  const [total,   setTotal]   = useState(0);
  const [loading, setLoading] = useState(true);
  const [page,    setPage]    = useState(1);

  async function load(p = 1) {
    setLoading(true);
    try {
      const res = await getInventory({ limit: 50, page: p });
      setParcels(res.data);
      setTotal(res.meta.total);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(page); }, [page]);

  return (
    <div>
      <div className="flex items-center gap-3 mb-6">
        <Package size={22} className="text-indigo-600" />
        <h1 className="text-xl font-bold text-slate-900">Hub Inventory</h1>
        <span className="ml-1 text-sm text-slate-500">({total} parcels at this hub)</span>
        <button
          onClick={() => load(page)}
          className="ml-auto p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition-colors"
          title="Refresh"
        >
          <RefreshCw size={16} className={loading ? 'animate-spin' : ''} />
        </button>
      </div>

      {loading && parcels.length === 0 ? (
        <div className="flex items-center gap-2 text-slate-500 py-12 justify-center">
          <Loader2 size={20} className="animate-spin" /> Loading…
        </div>
      ) : parcels.length === 0 ? (
        <div className="text-center py-16 text-slate-400">
          <Package size={40} className="mx-auto mb-3 opacity-30" />
          <p className="font-medium">Hub is clear</p>
          <p className="text-sm">No parcels currently at this hub</p>
        </div>
      ) : (
        <div className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 border-b border-slate-200">
              <tr>
                <th className="text-left px-4 py-3 font-semibold text-slate-600">Parcel #</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Sender</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Receiver</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600">Status</th>
                <th className="text-left px-4 py-3 font-semibold text-slate-600 hidden sm:table-cell">Size</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {parcels.map(p => (
                <tr key={p.id} className="hover:bg-slate-50 transition-colors">
                  <td className="px-4 py-3 font-mono font-semibold text-slate-800">{p.parcel_number}</td>
                  <td className="px-4 py-3 text-slate-600 hidden md:table-cell">{p.sender_name}</td>
                  <td className="px-4 py-3 text-slate-600 hidden md:table-cell">{p.receiver_name}</td>
                  <td className="px-4 py-3"><StatusBadge status={p.status} /></td>
                  <td className="px-4 py-3 text-slate-500 hidden sm:table-cell">
                    <span className="bg-slate-100 px-2 py-0.5 rounded text-xs font-medium">{p.size}</span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
