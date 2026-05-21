'use client';

import { useEffect, useState } from 'react';
import { getInbound, type TripManifest } from '@/lib/api';
import { StatusBadge } from '@/components/StatusBadge';
import { formatDate, cn } from '@/lib/utils';
import { ArrowDownToLine, Loader2, ChevronDown, ChevronRight, Package } from 'lucide-react';

export default function InboundPage() {
  const [trips,   setTrips]   = useState<TripManifest[]>([]);
  const [loading, setLoading] = useState(true);
  const [open,    setOpen]    = useState<Record<string, boolean>>({});

  useEffect(() => {
    setLoading(true);
    getInbound()
      .then(data => { setTrips(data); setLoading(false); })
      .catch(() => setLoading(false));
  }, []);

  function toggle(id: string) {
    setOpen(o => ({ ...o, [id]: !o[id] }));
  }

  return (
    <div>
      <div className="flex items-center gap-3 mb-6">
        <ArrowDownToLine size={22} className="text-teal-600" />
        <h1 className="text-xl font-bold text-slate-900">Inbound Today</h1>
        {!loading && (
          <span className="ml-auto bg-teal-100 text-teal-700 text-xs font-semibold px-2.5 py-1 rounded-full">
            {trips.length} trip{trips.length !== 1 ? 's' : ''}
          </span>
        )}
      </div>

      {loading ? (
        <div className="flex items-center gap-2 text-slate-500 py-12 justify-center">
          <Loader2 size={20} className="animate-spin" /> Loading…
        </div>
      ) : trips.length === 0 ? (
        <div className="text-center py-16 text-slate-400">
          <ArrowDownToLine size={40} className="mx-auto mb-3 opacity-30" />
          <p className="font-medium">No inbound trips today</p>
        </div>
      ) : (
        <div className="space-y-3">
          {trips.map(trip => (
            <div key={trip.id} className="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
              <button
                onClick={() => toggle(trip.id)}
                className="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 transition-colors"
              >
                <div className="flex items-center gap-3">
                  {open[trip.id] ? <ChevronDown size={18} /> : <ChevronRight size={18} />}
                  <div>
                    <p className="font-bold text-slate-900">{trip.route.code}</p>
                    <p className="text-xs text-slate-500 mt-0.5">
                      {trip.lorry.plate_number} · {trip.driver.name} · ETA {formatDate(trip.scheduled_arrival_at)}
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Package size={14} className="text-slate-400" />
                  <span className="text-sm font-semibold text-slate-700">{trip.parcels.length}</span>
                  <StatusBadge status={trip.status.toUpperCase()} />
                </div>
              </button>

              {open[trip.id] && (
                <div className="border-t border-slate-100 divide-y divide-slate-100">
                  {trip.parcels.map(p => (
                    <div key={p.id} className="px-5 py-3 flex items-center justify-between text-sm">
                      <div>
                        <p className="font-mono font-semibold text-slate-800">{p.parcel_number}</p>
                        <p className="text-slate-500 text-xs">{p.sender_name} → {p.receiver_name}</p>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-medium">{p.size_code}</span>
                        <StatusBadge status={p.status} />
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
