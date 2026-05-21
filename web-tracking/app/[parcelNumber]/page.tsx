import Link from "next/link";

type TrackingEvent = {
  event_type: string;
  occurred_at: string | null;
  hub_code?: string | null;
  notes?: string | null;
};

type TrackingPayload = {
  parcel_number: string;
  current_status: string;
  route_code: string;
  receiver_name: string;
  events: TrackingEvent[];
};

type ApiEnvelope<T> = {
  success: boolean;
  data: T;
  error: { code: string; message: string } | null;
};

const API_BASE =
  process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8000/api/v1";

const STAGES = [
  "BOOKED",
  "LABEL_PRINTED",
  "PICKED_UP",
  "RECEIVED_AT_ORIGIN_HUB",
  "LOADED_ON_LORRY",
  "IN_TRANSIT",
  "ARRIVED_AT_DESTINATION_HUB",
  "OUT_FOR_DELIVERY",
  "DELIVERED",
] as const;

function stageLabel(stage: string): string {
  return stage.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

export const revalidate = 30;

export default async function ParcelTrackingPage({
  params,
}: {
  params: Promise<{ parcelNumber: string }>;
}) {
  const { parcelNumber } = await params;
  const safeParcelNumber = decodeURIComponent(parcelNumber);

  const response = await fetch(
    `${API_BASE}/public/parcels/${encodeURIComponent(safeParcelNumber)}/track`,
    { next: { revalidate: 30 } },
  );

  let payload: ApiEnvelope<TrackingPayload> | null = null;
  try {
    payload = (await response.json()) as ApiEnvelope<TrackingPayload>;
  } catch {
    payload = null;
  }

  const hasData = response.ok && payload?.success;
  const tracking = hasData && payload ? payload.data : null;
  const currentStageIndex = tracking
    ? STAGES.indexOf(tracking.current_status as (typeof STAGES)[number])
    : -1;

  return (
    <main className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-4 py-8 md:px-8 animate-fade-up">

      {/* Header card */}
      <section className="relative overflow-hidden rounded-3xl bg-foreground px-8 py-8 text-white shadow-[0_20px_60px_rgba(16,42,67,0.2)]">
        <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-accent-2 to-transparent opacity-90" />
        <div className="pointer-events-none absolute -right-8 -top-8 h-48 w-48 rounded-full bg-accent/20 blur-3xl" />
        <p className="relative text-[10px] font-bold uppercase tracking-[0.3em] text-white/40">
          Parcel Tracking
        </p>
        <h1 className="relative mt-1.5 font-mono text-2xl font-bold tracking-tight md:text-3xl">
          {safeParcelNumber}
        </h1>
        {!tracking ? (
          <p className="relative mt-3 text-sm text-accent-2">
            {payload?.error?.message ?? "Tracking information unavailable for this parcel."}
          </p>
        ) : (
          <div className="relative mt-4 flex flex-wrap gap-4 text-sm">
            <Chip label="Status" value={tracking.current_status} highlight />
            <Chip label="Route" value={tracking.route_code} />
            <Chip label="Receiver" value={tracking.receiver_name} />
          </div>
        )}
      </section>

      {/* Progress stepper */}
      {tracking && (
        <section className="rounded-3xl border border-line bg-surface p-6 shadow-[0_4px_24px_rgba(16,42,67,0.06)]">
          <h2 className="text-base font-semibold">Journey Progress</h2>
          <div className="mt-5 flex flex-col gap-0">
            {STAGES.map((stage, idx) => {
              const isActive = tracking.current_status === stage;
              const isDone = idx <= currentStageIndex && !isActive;
              const isFuture = idx > currentStageIndex;

              return (
                <div key={stage} className="flex items-start gap-4">
                  {/* Connector line + dot */}
                  <div className="flex flex-col items-center">
                    <div
                      className={`h-6 w-6 shrink-0 rounded-full border-2 flex items-center justify-center text-[9px] font-bold transition-all ${
                        isActive
                          ? "border-accent bg-accent text-white shadow-md"
                          : isDone
                            ? "border-accent-2 bg-accent-2 text-foreground"
                            : "border-line bg-white text-muted"
                      }`}
                    >
                      {isDone ? "✓" : idx + 1}
                    </div>
                    {idx < STAGES.length - 1 && (
                      <div
                        className={`mt-1 w-0.5 flex-1 min-h-[20px] rounded-full transition-colors ${
                          isDone ? "bg-accent-2/60" : "bg-line"
                        }`}
                      />
                    )}
                  </div>
                  {/* Label */}
                  <div className={`pb-5 pt-0.5 ${idx === STAGES.length - 1 ? "pb-0" : ""}`}>
                    <p
                      className={`text-sm font-semibold leading-tight ${
                        isActive ? "text-accent" : isFuture ? "text-muted" : "text-foreground"
                      }`}
                    >
                      {stageLabel(stage)}
                    </p>
                    {isActive && (
                      <span className="mt-0.5 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-accent">
                        <span className="h-1.5 w-1.5 rounded-full bg-accent animate-pulse-dot" />
                        Current
                      </span>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      )}

      {/* Event timeline */}
      {tracking && (
        <section className="rounded-3xl border border-line bg-surface p-6 shadow-[0_4px_24px_rgba(16,42,67,0.06)]">
          <h2 className="text-base font-semibold">Event Timeline</h2>
          {tracking.events.length === 0 ? (
            <p className="mt-3 text-sm text-muted">No events recorded yet.</p>
          ) : (
            <ul className="mt-5 space-y-3">
              {tracking.events.map((event, index) => (
                <li
                  key={`${event.event_type}-${event.occurred_at ?? "na"}-${index}`}
                  className="flex gap-4 rounded-2xl border border-line bg-white px-4 py-4"
                >
                  <div className="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-accent/50" />
                  <div className="flex-1 min-w-0">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                      <p className="text-sm font-semibold">{stageLabel(event.event_type)}</p>
                      <p className="shrink-0 text-xs text-muted">
                        {event.occurred_at ? new Date(event.occurred_at).toLocaleString() : "N/A"}
                      </p>
                    </div>
                    {event.hub_code && (
                      <p className="mt-1 text-xs text-muted">
                        Hub: <span className="font-mono font-semibold text-foreground">{event.hub_code}</span>
                      </p>
                    )}
                    {event.notes && <p className="mt-1 text-xs text-muted">{event.notes}</p>}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>
      )}

      <Link
        href="/"
        className="w-fit rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-medium transition hover:bg-background"
      >
        ← Back to Search
      </Link>
    </main>
  );
}

function Chip({ label, value, highlight }: { label: string; value: string; highlight?: boolean }) {
  return (
    <div className={`rounded-xl px-4 py-2.5 ${highlight ? "bg-white/12 ring-1 ring-white/15" : "bg-white/8"}`}>
      <p className="text-[9px] uppercase tracking-[0.2em] text-white/40">{label}</p>
      <p className={`mt-0.5 text-sm font-bold ${highlight ? "text-accent-2" : "text-white/85"}`}>{value}</p>
    </div>
  );
}
