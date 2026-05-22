'use client';

import { useState, useRef, useEffect, useCallback } from 'react';
import { lookupParcel, scanParcel, getOutbound, type ParcelSummary, type ScanEvent, type TripManifest } from '@/lib/api';
import { StatusBadge } from '@/components/StatusBadge';
import { SCAN_EVENT_LABELS, beep, formatDate } from '@/lib/utils';
import { ScanLine, Keyboard, CheckCircle2, XCircle, Loader2, Camera, CameraOff } from 'lucide-react';
import { toast } from 'sonner';

type Mode = 'idle' | 'looking_up' | 'found' | 'scanning' | 'success' | 'error';

const AVAILABLE_EVENTS: Record<string, ScanEvent[]> = {
  BOOKED:                     ['RECEIVED_AT_ORIGIN_HUB'],
  LABEL_PRINTED:              ['RECEIVED_AT_ORIGIN_HUB'],
  PICKED_UP:                  ['RECEIVED_AT_ORIGIN_HUB'],
  RECEIVED_AT_ORIGIN_HUB:     ['LOADED_ON_LORRY'],
  ARRIVED_AT_DESTINATION_HUB: ['OUT_FOR_DELIVERY'],
  IN_TRANSIT:                 ['ARRIVED_AT_DESTINATION_HUB'],
};

export default function ScanPage() {
  const [input,      setInput]      = useState('');
  const [mode,       setMode]       = useState<Mode>('idle');
  const [parcel,     setParcel]     = useState<ParcelSummary | null>(null);
  const [event,      setEvent]      = useState<ScanEvent | ''>('');
  const [tripId,     setTripId]     = useState('');
  const [trips,      setTrips]      = useState<TripManifest[]>([]);
  const [result,     setResult]     = useState<{ status: string } | null>(null);
  const [cameraOn,   setCameraOn]   = useState(false);
  const inputRef     = useRef<HTMLInputElement>(null);
  const scannerRef   = useRef<unknown>(null);
  const scanAreaId   = 'qr-scan-area';

  // Focus the manual input on load and keyboard shortcut F2
  useEffect(() => {
    inputRef.current?.focus();
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'F2') inputRef.current?.focus();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, []);

  // Load today's outbound trips (needed for LOADED_ON_LORRY)
  useEffect(() => {
    getOutbound().then(setTrips).catch(() => {});
  }, []);

  const handleLookup = useCallback(async (value: string) => {
    const trimmed = value.trim();
    if (!trimmed) return;
    setMode('looking_up');
    try {
      const p = await lookupParcel(trimmed);
      setParcel(p);
      const available = AVAILABLE_EVENTS[p.status] ?? [];
      setEvent(available[0] ?? '');
      setMode('found');
      beep('success');
    } catch {
      setMode('error');
      beep('error');
      toast.error('Parcel not found');
      setTimeout(() => { setMode('idle'); setInput(''); inputRef.current?.focus(); }, 1500);
    }
  }, []);

  // QR camera scanner (html5-qrcode loaded dynamically)
  const startCamera = useCallback(async () => {
    setCameraOn(true);
    try {
      const { Html5Qrcode } = await import('html5-qrcode');
      const scanner = new Html5Qrcode(scanAreaId);
      scannerRef.current = scanner;
      await scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        (decoded) => {
          // Extract parcel number from JWT or plain string
          const match = decoded.match(/CCC-\d{8}-\d{6}-\w/);
          const number = match ? match[0] : decoded;
          scanner.stop().catch(() => {});
          setCameraOn(false);
          setInput(number);
          handleLookup(number);
        },
        undefined
      );
    } catch {
      toast.error('Camera not available');
      setCameraOn(false);
    }
  }, [handleLookup]);

  const stopCamera = useCallback(() => {
    if (scannerRef.current) {
      (scannerRef.current as { stop: () => Promise<void> }).stop().catch(() => {});
      scannerRef.current = null;
    }
    setCameraOn(false);
  }, []);

  useEffect(() => () => stopCamera(), [stopCamera]);

  async function handleScan() {
    if (!parcel || !event) return;
    if (event === 'LOADED_ON_LORRY' && !tripId) {
      toast.error('Select a trip first');
      return;
    }
    setMode('scanning');
    try {
      const res = await scanParcel(parcel.id, event as ScanEvent, tripId || undefined);
      setResult({ status: res.new_status });
      setMode('success');
      beep('success');
      toast.success(`✓ ${parcel.parcel_number} → ${res.new_status}`);
    } catch (err) {
      setMode('error');
      beep('error');
      toast.error(err instanceof Error ? err.message : 'Scan failed');
      setTimeout(() => setMode('found'), 1000);
    }
  }

  function reset() {
    setInput('');
    setParcel(null);
    setEvent('');
    setTripId('');
    setResult(null);
    setMode('idle');
    setTimeout(() => inputRef.current?.focus(), 50);
  }

  const availableEvents = parcel ? (AVAILABLE_EVENTS[parcel.status] ?? []) : [];

  return (
    <div className="max-w-xl mx-auto">
      <div className="flex items-center gap-3 mb-6">
        <ScanLine size={22} className="text-indigo-600" />
        <h1 className="text-xl font-bold text-slate-900">Scan Parcel</h1>
        <span className="ml-auto text-xs text-slate-400">F2 — focus input</span>
      </div>

      {/* Input row */}
      <div className="flex gap-2 mb-4">
        <div className="relative flex-1">
          <Keyboard size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            ref={inputRef}
            value={input}
            onChange={e => setInput(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && handleLookup(input)}
            placeholder="Scan or type parcel number…"
            className="w-full pl-9 pr-4 py-3 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
            disabled={mode === 'looking_up' || mode === 'scanning'}
          />
        </div>
        <button
          onClick={() => cameraOn ? stopCamera() : startCamera()}
          className="px-3 py-2 rounded-xl border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 transition-colors"
          title="Toggle camera"
        >
          {cameraOn ? <CameraOff size={18} /> : <Camera size={18} />}
        </button>
        <button
          onClick={() => handleLookup(input)}
          disabled={!input.trim() || mode === 'looking_up'}
          className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-xl text-sm font-medium transition-colors"
        >
          {mode === 'looking_up' ? <Loader2 size={16} className="animate-spin" /> : 'Lookup'}
        </button>
      </div>

      {/* Camera area */}
      {cameraOn && (
        <div className="mb-4 rounded-xl overflow-hidden border border-slate-200 bg-black">
          <div id={scanAreaId} className="w-full" style={{ minHeight: 280 }} />
        </div>
      )}

      {/* Parcel card */}
      {parcel && mode !== 'idle' && (
        <div className="bg-white border border-slate-200 rounded-2xl p-5 mb-4 shadow-sm">
          <div className="flex justify-between items-start mb-3">
            <div>
              <p className="font-mono font-bold text-slate-900 text-lg">{parcel.parcel_number}</p>
              <StatusBadge status={parcel.status} />
            </div>
            <button onClick={reset} className="text-slate-400 hover:text-slate-600 text-sm">✕ Clear</button>
          </div>

          <div className="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm text-slate-600 mb-4">
            <div><span className="font-medium text-slate-500">From:</span> {parcel.sender_name}</div>
            <div><span className="font-medium text-slate-500">To:</span> {parcel.receiver_name}</div>
            <div><span className="font-medium text-slate-500">Pickup:</span> {parcel.pickup_point}</div>
            <div><span className="font-medium text-slate-500">Drop:</span> {parcel.drop_point}</div>
            {parcel.trip && (
              <>
                <div><span className="font-medium text-slate-500">Trip:</span> {parcel.trip.route}</div>
                <div><span className="font-medium text-slate-500">Departs:</span> {formatDate(parcel.trip.scheduled_departure)}</div>
              </>
            )}
          </div>

          {/* Action selector */}
          {mode === 'success' ? (
            <div className="flex items-center gap-2 text-green-700 bg-green-50 rounded-xl p-3">
              <CheckCircle2 size={20} />
              <span className="font-semibold">Scanned → {result?.status.replace(/_/g, ' ')}</span>
            </div>
          ) : mode === 'error' ? (
            <div className="flex items-center gap-2 text-red-700 bg-red-50 rounded-xl p-3">
              <XCircle size={20} />
              <span className="font-semibold">Scan failed</span>
            </div>
          ) : availableEvents.length === 0 ? (
            <p className="text-sm text-slate-500 italic">No scan actions available for current status.</p>
          ) : (
            <div className="space-y-3">
              <div className="flex flex-wrap gap-2">
                {availableEvents.map(ev => (
                  <button
                    key={ev}
                    onClick={() => setEvent(ev)}
                    className={`px-3 py-2 rounded-lg text-sm font-medium border transition-colors ${
                      event === ev
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'bg-white text-slate-700 border-slate-300 hover:border-indigo-400'
                    }`}
                  >
                    {SCAN_EVENT_LABELS[ev] ?? ev}
                  </button>
                ))}
              </div>

              {event === 'LOADED_ON_LORRY' && (
                <select
                  value={tripId}
                  onChange={e => setTripId(e.target.value)}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                >
                  <option value="">— Select outbound trip —</option>
                  {trips.map(t => (
                    <option key={t.id} value={t.id}>
                      {t.route.code} • {formatDate(t.scheduled_departure_at)} • {t.lorry.plate_number}
                    </option>
                  ))}
                </select>
              )}

              <button
                onClick={handleScan}
                disabled={!event || mode === 'scanning'}
                className="w-full bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm"
              >
                {mode === 'scanning'
                  ? <><Loader2 size={16} className="animate-spin" /> Scanning…</>
                  : <><CheckCircle2 size={16} /> Confirm Scan</>
                }
              </button>
            </div>
          )}
        </div>
      )}

      {mode === 'success' && (
        <button
          onClick={reset}
          className="w-full border-2 border-dashed border-slate-300 rounded-2xl py-4 text-slate-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors text-sm font-medium"
        >
          + Scan next parcel
        </button>
      )}
    </div>
  );
}
