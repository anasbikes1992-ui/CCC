"use client";

// Component inspired by: https://21st.dev/community/components/hextaui/hero-1
import { useEffect, useMemo, useState } from "react";
import L, { type LatLngExpression } from "leaflet";
import { MapContainer, TileLayer, CircleMarker, Polyline, Popup, Marker } from "react-leaflet";

type Route = {
  id: string;
  name: string;
  color: string;
  points: [number, number][];
  speed: number;
  basePrice: string;
};

const HUBS = [
  { name: "Colombo Hub", position: [6.9271, 79.8612] as [number, number] },
  { name: "Kandy Hub", position: [7.2906, 80.6337] as [number, number] },
  { name: "Galle Hub", position: [6.0535, 80.221] as [number, number] },
  { name: "Jaffna Hub", position: [9.6615, 80.0255] as [number, number] },
];

const ROUTES: Route[] = [
  {
    id: "cmb-kdy",
    name: "Colombo to Kandy",
    color: "#0b5cd9",
    points: [
      [6.9271, 79.8612],
      [7.12, 80.18],
      [7.2906, 80.6337],
    ],
    speed: 0.012,
    basePrice: "LKR 700",
  },
  {
    id: "cmb-gal",
    name: "Colombo to Galle",
    color: "#ff8a3d",
    points: [
      [6.9271, 79.8612],
      [6.45, 79.98],
      [6.0535, 80.221],
    ],
    speed: 0.009,
    basePrice: "LKR 600",
  },
  {
    id: "cmb-jaf",
    name: "Colombo to Jaffna",
    color: "#238a55",
    points: [
      [6.9271, 79.8612],
      [8.0, 80.1],
      [9.0, 80.0],
      [9.6615, 80.0255],
    ],
    speed: 0.006,
    basePrice: "LKR 2,200",
  },
];

function interpolateRoutePoint(points: [number, number][], progress: number): [number, number] {
  if (points.length < 2) {
    return points[0] ?? [6.9271, 79.8612];
  }

  const totalSegments = points.length - 1;
  const scaled = progress * totalSegments;
  const segmentIndex = Math.min(Math.floor(scaled), totalSegments - 1);
  const local = scaled - segmentIndex;
  const start = points[segmentIndex];
  const end = points[segmentIndex + 1];

  return [
    start[0] + (end[0] - start[0]) * local,
    start[1] + (end[1] - start[1]) * local,
  ];
}

export default function CargoMap() {
  const [progressByRoute, setProgressByRoute] = useState<Record<string, number>>({
    "cmb-kdy": 0.08,
    "cmb-gal": 0.4,
    "cmb-jaf": 0.72,
  });

  useEffect(() => {
    const interval = window.setInterval(() => {
      setProgressByRoute((current) => {
        const nextState: Record<string, number> = {};
        for (const route of ROUTES) {
          const next = (current[route.id] ?? 0) + route.speed;
          nextState[route.id] = next > 1 ? next - 1 : next;
        }
        return nextState;
      });
    }, 90);

    return () => window.clearInterval(interval);
  }, []);

  const cargoIcon = useMemo(
    () =>
      L.divIcon({
        className: "",
        html: '<div class="cargo-marker">📦</div>',
        iconSize: [30, 30],
        iconAnchor: [15, 15],
      }),
    []
  );

  return (
    <div className="rounded-3xl border border-black/10 bg-surface p-3 shadow-xl">
      <MapContainer
        center={[7.6, 80.55] as LatLngExpression}
        zoom={8}
        className="h-[460px] w-full rounded-2xl"
        zoomControl={false}
        scrollWheelZoom={false}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        {HUBS.map((hub) => (
          <CircleMarker
            key={hub.name}
            center={hub.position}
            radius={8}
            pathOptions={{ color: "#ffffff", fillColor: "#0b5cd9", fillOpacity: 1, weight: 2 }}
          >
            <Popup>{hub.name}</Popup>
          </CircleMarker>
        ))}

        {ROUTES.map((route) => (
          <Polyline
            key={route.id}
            positions={route.points as LatLngExpression[]}
            pathOptions={{ color: route.color, weight: 4, opacity: 0.85 }}
          >
            <Popup>
              <div className="text-sm">
                <div className="font-semibold">{route.name}</div>
                <div>Base from: {route.basePrice}</div>
                <div>Schedule: 6 AM / 2 PM</div>
              </div>
            </Popup>
          </Polyline>
        ))}

        {ROUTES.map((route) => {
          const markerPosition = interpolateRoutePoint(route.points, progressByRoute[route.id] ?? 0);
          return <Marker key={`${route.id}-cargo`} position={markerPosition} icon={cargoIcon} />;
        })}
      </MapContainer>
    </div>
  );
}
