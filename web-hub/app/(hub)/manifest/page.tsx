'use client';

import { useEffect, useState } from 'react';
import { getOutbound, getManifest, type TripManifest } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import { FileText, Printer, Loader2, ChevronDown } from 'lucide-react';

export default function ManifestPage() {
  const [trips,    setTrips]    = useState<TripManifest[]>([]);
  const [selected, setSelected] = useState('');
  const [manifest, setManifest] = useState<TripManifest | null>(null);
  const [loading,  setLoading]  = useState(false);

  useEffect(() => {
    getOutbound().then(setTrips).catch(() => {});
  }, []);

  async function loadManifest(tripId: string) {
    setSelected(tripId);
    if (!tripId) { setManifest(null); return; }
    setLoading(true);
    try {
      const m = await getManifest(tripId);
      setManifest(m);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div>
      <div className="flex items-center gap-3 mb-6 no-print">
        <FileText size={22} className="text-slate-600" />
        <h1 className="text-xl font-bold text-slate-900">Trip Manifest</h1>
        {manifest && (
          <button
            onClick={() => window.print()}
            className="ml-auto flex items-center gap-2 px-4 py-2 bg-slate-900 text-white text-sm rounded-lg hover:bg-slate-700 transition-colors"
          >
            <Printer size={15} /> Print
          </button>
        )}
      </div>

      {/* Trip selector */}
      <div className="relative no-print mb-6">
        <select
          value={selected}
          onChange={e => loadManifest(e.target.value)}
          className="w-full appearance-none border border-slate-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white pr-10"
        >
          <option value="">— Select a trip —</option>
          {trips.map(t => (
            <option key={t.id} value={t.id}>
              {t.route.code} · {formatDate(t.scheduled_departure_at)} · {t.lorry.plate_number}
            </option>
          ))}
        </select>
        <ChevronDown size={16} className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
      </div>

      {loading && (
        <div className="flex items-center gap-2 text-slate-500 justify-center py-12">
          <Loader2 size={20} className="animate-spin" /> Loading manifest…
        </div>
      )}

      {manifest && !loading && (
        <div className="print-full">
          {/* Header */}
          <div className="bg-slate-900 text-white rounded-2xl p-5 mb-5 print:rounded-none print:mb-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-xs uppercase tracking-wider text-slate-400 font-medium mb-0.5">Trip Manifest</p>
                <p className="text-2xl font-bold">{manifest.route.code}</p>
                <p className="text-slate-300 text-sm mt-1">
                  {manifest.lorry.plate_number} · {manifest.driver.name} · {manifest.driver.phone}
                </p>
              </div>
              <div className="text-right text-sm text-slate-300">
                <p>Depart: {formatDate(manifest.scheduled_departure_at)}</p>
                <p>Arrive: {formatDate(manifest.scheduled_arrival_at)}</p>
                <p className="mt-1 font-semibold text-white">{manifest.parcels.length} parcels</p>
              </div>
            </div>
          </div>

          {/* Parcel table */}
          <div className="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 border-b border-slate-200">
                <tr>
                  <th className="text-left px-4 py-3 font-semibold text-slate-600">#</th>
                  <th className="text-left px-4 py-3 font-semibold text-slate-600">Parcel Number</th>
                  <th className="text-left px-4 py-3 font-semibold text-slate-600">Sender</th>
                  <th className="text-left px-4 py-3 font-semibold text-slate-600">Receiver</th>
                  <th className="text-left px-4 py-3 font-semibold text-slate-600">Route</th>
                  <th className="text-left px-4 py-3 font-semibold text-slate-600">Size</th>
                  <th className="text-left px-4 py-3 font-semibold text-slate-600">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {manifest.parcels.map((p, i) => (
                  <tr key={p.id} className="hover:bg-slate-50 print:hover:bg-transparent">
                    <td className="px-4 py-3 text-slate-400 font-medium">{i + 1}</td>
                    <td className="px-4 py-3 font-mono font-semibold text-slate-800">{p.parcel_number}</td>
                    <td className="px-4 py-3 text-slate-600">{p.sender_name}</td>
                    <td className="px-4 py-3 text-slate-600">
                      {p.receiver_name}
                      <span className="block text-xs text-slate-400">{p.receiver_phone}</span>
                    </td>
                    <td className="px-4 py-3 text-slate-500 text-xs">
                      {p.pickup_point} → {p.drop_point}
                    </td>
                    <td className="px-4 py-3">
                      <span className="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-xs font-bold">{p.size_code}</span>
                    </td>
                    <td className="px-4 py-3 text-xs text-slate-500">{p.status.replace(/_/g, ' ')}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <p className="text-xs text-slate-400 mt-3 text-right no-print">
            Printed {new Date().toLocaleString()}
          </p>
        </div>
      )}
    </div>
  );
}
