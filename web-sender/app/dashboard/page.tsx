"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { fetchApi, getToken, clearToken } from "../../lib/api";
import { Package, Plus, Truck, ChevronRight, LogOut } from "lucide-react";

const TRACKING_URL = process.env.NEXT_PUBLIC_TRACKING_URL ?? "https://web-tracking-sigma.vercel.app";

function getRouteEndpoints(parcel: any): { origin: string; destination: string } {
  const routeValue = parcel.route;

  if (typeof routeValue === "string") {
    const [origin = parcel.pickup_type || "CMB", destination = parcel.drop_type || "KDY"] = routeValue.split("-");
    return { origin, destination };
  }

  if (routeValue && typeof routeValue === "object") {
    const code = typeof routeValue.code === "string" ? routeValue.code : parcel.route_code;
    const [origin = parcel.pickup_type || "CMB", destination = parcel.drop_type || "KDY"] = typeof code === "string" ? code.split("-") : [];
    return {
      origin: routeValue.origin_hub?.code ?? origin,
      destination: routeValue.destination_hub?.code ?? destination,
    };
  }

  const routeCode = parcel.route_code;
  if (typeof routeCode === "string") {
    const [origin = parcel.pickup_type || "CMB", destination = parcel.drop_type || "KDY"] = routeCode.split("-");
    return { origin, destination };
  }

  return {
    origin: parcel.pickup_type || "CMB",
    destination: parcel.drop_type || "KDY",
  };
}

export default function DashboardPage() {
  const [parcels, setParcels] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  useEffect(() => {
    if (!getToken()) {
      router.push("/login");
      return;
    }
    loadParcels();
  }, [router]);

  async function loadParcels() {
    const res = await fetchApi<any[]>("/customer/parcels");
    if (res.success && res.data) {
      setParcels(res.data);
    } else {
      if (res.error?.message === "Unauthenticated.") {
        clearToken();
        router.push("/login");
      }
    }
    setLoading(false);
  }

  function handleLogout() {
    clearToken();
    router.push("/login");
  }

  return (
    <main className="min-h-screen bg-background pb-20">
      <header className="bg-surface sticky top-0 z-10 border-b border-line shadow-sm" role="banner">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
          <div className="flex items-center gap-2 text-accent">
            <Truck className="h-6 w-6" aria-hidden="true" />
            <span className="text-xl font-bold text-foreground">CCC</span>
          </div>
          <div className="flex items-center gap-4">
            <button
              onClick={handleLogout}
              aria-label="Sign out of your account"
              className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-muted transition hover:bg-muted/10 hover:text-foreground"
            >
              <LogOut className="h-4 w-4" aria-hidden="true" />
              <span>Sign Out</span>
            </button>
            <Link
              href="/book"
              aria-label="Book a new parcel shipment"
              className="flex items-center gap-2 rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:brightness-110 active:scale-95"
            >
              <Plus className="h-4 w-4" aria-hidden="true" />
              <span>Book Parcel</span>
            </Link>
          </div>
        </div>
      </header>

      <div className="mx-auto max-w-6xl px-4 mt-8">
        <div className="flex items-end justify-between mb-6">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Your Shipments</h1>
            <p className="mt-1 text-muted">Manage and track your parcels.</p>
          </div>
        </div>

        {loading ? (
          <div className="flex h-64 items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-accent border-t-transparent"></div>
          </div>
        ) : parcels.length === 0 ? (
          <div className="flex flex-col items-center justify-center rounded-3xl border border-dashed border-line bg-surface py-24 text-center" role="status">
            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-accent/10 text-accent mb-4" aria-hidden="true">
              <Package className="h-8 w-8" />
            </div>
            <h2 className="text-xl font-semibold">No shipments yet</h2>
            <p className="mt-2 text-muted max-w-sm">
              You haven't booked any parcels yet. Start your first shipment by clicking below.
            </p>
            <Link
              href="/book"
              aria-label="Book your first parcel shipment"
              className="mt-6 flex items-center gap-2 rounded-xl bg-accent px-6 py-3 font-semibold text-white shadow-md transition hover:brightness-110 active:scale-95"
            >
              <Plus className="h-5 w-5" aria-hidden="true" />
              <span>Book Your First Parcel</span>
            </Link>
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {parcels.map((parcel: any) => (
              (() => {
                const { origin, destination } = getRouteEndpoints(parcel);

                return (
              <a
                href={`${TRACKING_URL}/${parcel.parcel_number}`}
                key={parcel.id}
                target="_blank"
                rel="noreferrer"
                className="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-surface p-5 shadow-sm transition hover:shadow-md"
              >
                <div className="flex items-center justify-between">
                  <span className="text-xs font-mono font-bold text-accent bg-accent/10 px-2 py-1 rounded-md">
                    {parcel.parcel_number}
                  </span>
                  <span className="text-xs font-semibold uppercase tracking-wider text-muted">
                    {parcel.status}
                  </span>
                </div>
                
                <div className="mt-5 flex items-center gap-3">
                  <div className="flex flex-col items-center">
                    <div className="h-2 w-2 rounded-full bg-accent"></div>
                    <div className="h-6 w-0.5 bg-line"></div>
                    <div className="h-2 w-2 rounded-full border-2 border-accent bg-white"></div>
                  </div>
                  <div className="flex flex-col justify-between h-12 text-sm font-medium">
                    <span>{origin}</span>
                    <span>{destination}</span>
                  </div>
                </div>

                <div className="mt-5 border-t border-line pt-4 flex items-center justify-between text-sm">
                  <span className="text-muted">Receiver: {parcel.receiver_name}</span>
                  <ChevronRight className="h-4 w-4 text-muted transition group-hover:text-accent group-hover:translate-x-1" />
                </div>
              </a>
                );
              })()
            ))}
          </div>
        )}
      </div>
    </main>
  );
}
