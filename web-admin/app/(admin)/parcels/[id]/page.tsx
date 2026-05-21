'use client';

import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { getParcel, updateParcel, Parcel } from '@/lib/api';
import { formatDate, formatCurrency, PARCEL_STATUSES } from '@/lib/utils';
import { ParcelStatusBadge } from '@/components/StatusBadge';
import { Spinner } from '@/components/ui';
import { ArrowLeft, Save, Package, MapPin, User, Clock, FileText, CheckCircle } from 'lucide-react';

const TIMELINE_ICONS: Record<string, string> = {
  BOOKED: '📦', LABEL_PRINTED: '🏷️', PICKED_UP: '🚪',
  RECEIVED_AT_ORIGIN_HUB: '🏢', LOADED_ON_LORRY: '🚛',
  IN_TRANSIT: '🛣️', ARRIVED_AT_DESTINATION_HUB: '📍',
  OUT_FOR_DELIVERY: '🏍️', DELIVERED: '✅',
  DELIVERY_FAILED: '❌', CANCELLED: '🚫', RETURNED_TO_ORIGIN: '↩️',
};

export default function ParcelDetailPage() {
  const { id }   = useParams<{ id: string }>();
  const router   = useRouter();
  const [parcel, setParcel]   = useState<Parcel | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving]   = useState(false);
  const [notes, setNotes]     = useState('');
  const [newStatus, setStatus] = useState('');
  const [saved, setSaved]     = useState(false);

  useEffect(() => {
    getParcel(id).then((p) => {
      setParcel(p);
      setNotes(p.notes ?? '');
      setStatus(p.status);
      setLoading(false);
    });
  }, [id]);

  const handleSave = async () => {
    if (!parcel) return;
    setSaving(true);
    try {
      const updated = await updateParcel(id, {
        notes,
        status: newStatus !== parcel.status ? newStatus : undefined,
      });
      setParcel(updated);
      setSaved(true);
      setTimeout(() => setSaved(false), 2500);
    } catch (e) {
      alert((e as Error).message);
    } finally {
      setSaving(false);
    }
  };

  if (loading) return (
    <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '60vh' }}>
      <Spinner />
    </div>
  );

  if (!parcel) return <div style={{ color: 'var(--text-muted)' }}>Parcel not found.</div>;

  return (
    <div className="animate-fade-in">
      {/* Header */}
      <div className="page-header">
        <div style={{ display: 'flex', alignItems: 'center', gap: 14 }}>
          <button className="btn btn-secondary btn-sm" onClick={() => router.push('/parcels')}>
            <ArrowLeft size={14} />
          </button>
          <div>
            <h1 className="page-title" style={{ fontFamily: 'monospace', fontSize: 18 }}>{parcel.parcel_number}</h1>
            <div style={{ marginTop: 4 }}><ParcelStatusBadge status={parcel.status} /></div>
          </div>
        </div>
        <button className="btn btn-primary" onClick={handleSave} disabled={saving}>
          {saving ? <Spinner /> : saved ? <CheckCircle size={14} /> : <Save size={14} />}
          {saved ? 'Saved!' : 'Save Changes'}
        </button>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 320px', gap: 18 }}>
        {/* Left column */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 18 }}>
          {/* Details card */}
          <div className="glass" style={{ padding: 22 }}>
            <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--text-muted)', marginBottom: 16, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
              Package Details
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px 20px' }}>
              {[
                ['Route',         parcel.route?.display_name ?? '—'],
                ['Package Size',  parcel.package_size?.code ?? '—'],
                ['Weight',        `${parcel.weight_kg} kg`],
                ['Trip',          parcel.trip?.trip_code ?? 'Not assigned'],
                ['Pickup Type',   parcel.pickup_type],
                ['Drop Type',     parcel.drop_type],
                ['Express',       parcel.is_express ? 'Yes' : 'No'],
                ['Total Price',   formatCurrency(parcel.total_price_lkr)],
                ['Receiver',      parcel.receiver_name],
                ['Receiver Phone', parcel.receiver_phone],
              ].map(([k, v]) => (
                <div key={k}>
                  <div style={{ fontSize: 11, color: 'var(--text-muted)', marginBottom: 2 }}>{k}</div>
                  <div style={{ fontSize: 13, color: 'var(--text-primary)', fontWeight: 500 }}>{v}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Admin Override */}
          <div className="glass" style={{ padding: 22 }}>
            <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--text-muted)', marginBottom: 16, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
              Admin Override
            </div>
            <div style={{ marginBottom: 14 }}>
              <label style={{ display: 'block', fontSize: 12, color: 'var(--text-secondary)', marginBottom: 6, fontWeight: 600 }}>
                Force Status Change
              </label>
              <select className="form-input" value={newStatus} onChange={(e) => setStatus(e.target.value)}>
                {PARCEL_STATUSES.map((s) => <option key={s} value={s}>{s.replace(/_/g, ' ')}</option>)}
              </select>
              {newStatus !== parcel.status && (
                <div style={{ marginTop: 6, fontSize: 11, color: 'var(--warning)' }}>
                  ⚠ Admin override — bypasses normal transition rules
                </div>
              )}
            </div>
            <div>
              <label style={{ display: 'block', fontSize: 12, color: 'var(--text-secondary)', marginBottom: 6, fontWeight: 600 }}>
                Admin Notes
              </label>
              <textarea
                className="form-input"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={3}
                placeholder="Add internal notes..."
                style={{ resize: 'vertical' }}
              />
            </div>
          </div>

          {/* Delivery Proof */}
          {parcel.delivery_proof && (
            <div className="glass" style={{ padding: 22 }}>
              <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--text-muted)', marginBottom: 16, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                Delivery Proof
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px 20px' }}>
                <div>
                  <div style={{ fontSize: 11, color: 'var(--text-muted)', marginBottom: 2 }}>Receiver Name</div>
                  <div style={{ fontSize: 13, fontWeight: 500 }}>{parcel.delivery_proof.receiver_name_input}</div>
                </div>
                <div>
                  <div style={{ fontSize: 11, color: 'var(--text-muted)', marginBottom: 2 }}>NIC (masked)</div>
                  <div style={{ fontSize: 13, fontWeight: 500, fontFamily: 'monospace' }}>
                    ******{parcel.delivery_proof.receiver_nic_last4}
                  </div>
                </div>
                <div>
                  <div style={{ fontSize: 11, color: 'var(--text-muted)', marginBottom: 2 }}>Delivered At</div>
                  <div style={{ fontSize: 13, fontWeight: 500 }}>{formatDate(parcel.delivery_proof.delivered_at)}</div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Right column — Timeline */}
        <div>
          <div className="glass" style={{ padding: 22 }}>
            <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--text-muted)', marginBottom: 16, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
              Event Timeline
            </div>
            {(parcel.events ?? []).length === 0 ? (
              <div style={{ textAlign: 'center', padding: '20px 0', color: 'var(--text-muted)', fontSize: 13 }}>No events yet</div>
            ) : (
              <div style={{ position: 'relative' }}>
                <div style={{ position: 'absolute', left: 14, top: 0, bottom: 0, width: 1, background: 'var(--border)' }} />
                {(parcel.events ?? []).map((ev, i) => (
                  <div key={ev.id} style={{ display: 'flex', gap: 12, marginBottom: 16, position: 'relative' }}>
                    <div style={{
                      width: 28, height: 28, borderRadius: '50%', flexShrink: 0,
                      background: 'var(--bg-primary)', border: '1px solid var(--border)',
                      display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 13, zIndex: 1,
                    }}>
                      {TIMELINE_ICONS[ev.event_type] ?? '●'}
                    </div>
                    <div style={{ paddingTop: 4 }}>
                      <div style={{ fontSize: 12, fontWeight: 600, color: 'var(--text-primary)' }}>
                        {ev.event_type.replace(/_/g, ' ')}
                      </div>
                      <div style={{ fontSize: 11, color: 'var(--text-muted)', marginTop: 1 }}>
                        {formatDate(ev.occurred_at)} · {ev.scan_mode}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Payments */}
          {(parcel.payments ?? []).length > 0 && (
            <div className="glass" style={{ padding: 22, marginTop: 18 }}>
              <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--text-muted)', marginBottom: 12, textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                Payments
              </div>
              {(parcel.payments ?? []).map((pay) => (
                <div key={pay.id} style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 8, fontSize: 13 }}>
                  <span style={{ color: 'var(--text-secondary)' }}>{pay.method}</span>
                  <span style={{ fontWeight: 600 }}>{formatCurrency(pay.amount_lkr)}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
