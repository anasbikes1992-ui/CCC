"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { fetchApi } from "../../lib/api";
import { ArrowLeft, CheckCircle2, ChevronRight, MapPin, Package, CreditCard, Truck } from "lucide-react";

export default function BookParcelPage() {
  const router = useRouter();
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [successData, setSuccessData] = useState<any>(null);

  const [formData, setFormData] = useState({
    route_code: "CMB-KDY",
    package_size_code: "S",
    weight_kg: "1",
    pickup_type: "hub",
    pickup_address: "",
    drop_type: "hub",
    drop_address: "",
    receiver_name: "",
    receiver_phone: "+947",
    payment_method: "cod",
  });

  const updateForm = (key: string, value: string) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  const nextStep = () => setStep((s) => Math.min(s + 1, 4));
  const prevStep = () => setStep((s) => Math.max(s - 1, 1));

  async function handleSubmit() {
    setLoading(true);
    setError("");

    const payload = {
      ...formData,
      weight_kg: parseFloat(formData.weight_kg),
    };

    const res = await fetchApi<any>("/customer/parcels", {
      method: "POST",
      body: JSON.stringify(payload),
    });

    setLoading(false);
    if (res.success && res.data) {
      setSuccessData(res.data.parcel);
      setStep(5);
    } else {
      setError(res.error?.message || "Booking failed");
    }
  }

  if (step === 5 && successData) {
    return (
      <main className="mx-auto flex min-h-screen max-w-2xl flex-col items-center justify-center p-4 text-center">
        <div className="mb-6 rounded-full bg-green-100 p-4 text-green-600">
          <CheckCircle2 className="h-16 w-16" />
        </div>
        <h1 className="text-3xl font-bold">Booking Confirmed!</h1>
        <p className="mt-2 text-muted">Your parcel has been successfully booked.</p>
        
        <div className="mt-8 w-full max-w-sm overflow-hidden rounded-2xl border border-line bg-surface shadow-sm">
          <div className="bg-accent/10 py-6">
            <h2 className="text-sm font-semibold uppercase tracking-wider text-accent">Tracking Number</h2>
            <p className="mt-1 text-2xl font-mono font-bold text-foreground">{successData.parcel_number}</p>
          </div>
          <div className="p-6">
             {/* If we had the qr_token, we could render a QR code here. 
                 The spec says QR is rendered client-side from qr_token. 
                 We will just show the text for now or a placeholder. */}
             <div className="mx-auto mb-4 h-40 w-40 rounded-xl bg-muted/20 flex items-center justify-center border-2 border-dashed border-muted/30">
               <span className="text-sm text-muted">QR Code Space</span>
             </div>
             
             <a
              href={`${process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1'}/customer/parcels/${successData.id}/label.pdf`}
              target="_blank"
              rel="noopener noreferrer"
              className="mt-4 flex w-full justify-center rounded-xl bg-accent px-4 py-3 font-semibold text-white transition hover:brightness-110 active:scale-95"
            >
              Download Label (PDF)
            </a>
          </div>
        </div>
        
        <div className="mt-8 flex gap-4">
          <Link href="/dashboard" className="text-sm font-semibold text-accent hover:underline">
            Go to Dashboard
          </Link>
          <Link href={`http://track.cargo.lk/${successData.parcel_number}`} className="text-sm font-semibold text-accent hover:underline">
            View Public Tracking
          </Link>
        </div>
      </main>
    );
  }

  return (
    <main className="min-h-screen bg-background pb-20">
      <header className="bg-surface sticky top-0 z-10 border-b border-line px-4 py-4">
        <div className="mx-auto flex max-w-3xl items-center gap-4">
          <Link href="/dashboard" className="rounded-full p-2 hover:bg-muted/10">
            <ArrowLeft className="h-5 w-5" />
          </Link>
          <h1 className="text-lg font-bold">Book a Parcel</h1>
        </div>
      </header>

      <div className="mx-auto mt-8 max-w-3xl px-4">
        {/* Stepper */}
        <div className="mb-8 flex items-center justify-between">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="flex flex-1 items-center">
              <div
                className={`flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold ${
                  step >= i ? "bg-accent text-white" : "bg-muted/20 text-muted"
                }`}
              >
                {i}
              </div>
              {i < 4 && (
                <div
                  className={`h-1 flex-1 mx-2 rounded-full ${
                    step > i ? "bg-accent" : "bg-muted/20"
                  }`}
                />
              )}
            </div>
          ))}
        </div>

        <div className="rounded-3xl border border-line bg-surface p-6 shadow-sm sm:p-8">
          {error && (
            <div className="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-600">
              {error}
            </div>
          )}

          {step === 1 && (
            <div className="animate-fade-up space-y-6">
              <h2 className="flex items-center gap-2 text-xl font-bold">
                <Truck className="h-6 w-6 text-accent" />
                Select Route
              </h2>
              <div className="space-y-4">
                <label className="block">
                  <span className="mb-2 block text-sm font-medium">Route</span>
                  <select
                    value={formData.route_code}
                    onChange={(e) => updateForm("route_code", e.target.value)}
                    className="w-full rounded-xl border border-line bg-white px-4 py-3 outline-none ring-accent/20 focus:ring-2"
                  >
                    <option value="CMB-KDY">Colombo ↔ Kandy</option>
                  </select>
                </label>
                <div className="rounded-xl bg-blue-50 p-4 text-sm text-blue-800">
                  <p>Currently, only the Colombo ↔ Kandy pilot route is active.</p>
                </div>
              </div>
            </div>
          )}

          {step === 2 && (
            <div className="animate-fade-up space-y-6">
              <h2 className="flex items-center gap-2 text-xl font-bold">
                <Package className="h-6 w-6 text-accent" />
                Package Details
              </h2>
              <div className="space-y-4">
                <label className="block">
                  <span className="mb-2 block text-sm font-medium">Size Category</span>
                  <select
                    value={formData.package_size_code}
                    onChange={(e) => updateForm("package_size_code", e.target.value)}
                    className="w-full rounded-xl border border-line bg-white px-4 py-3 outline-none ring-accent/20 focus:ring-2"
                  >
                    <option value="S">Small (Up to 5kg)</option>
                    <option value="M">Medium (Up to 25kg)</option>
                    <option value="L">Large (Up to 75kg)</option>
                    <option value="XL">Extra Large (Up to 200kg)</option>
                    <option value="BALE">Bale / Pallet (200kg+)</option>
                  </select>
                </label>
                
                <label className="block">
                  <span className="mb-2 block text-sm font-medium">Estimated Weight (kg)</span>
                  <input
                    type="number"
                    min="0.1"
                    step="0.1"
                    value={formData.weight_kg}
                    onChange={(e) => updateForm("weight_kg", e.target.value)}
                    autoComplete="off"
                    className="w-full rounded-xl border border-line bg-white px-4 py-3 outline-none ring-accent/20 focus:ring-2"
                  />
                </label>
              </div>
            </div>
          )}

          {step === 3 && (
            <div className="animate-fade-up space-y-6">
              <h2 className="flex items-center gap-2 text-xl font-bold">
                <MapPin className="h-6 w-6 text-accent" />
                Pickup & Dropoff
              </h2>
              <div className="grid gap-6 md:grid-cols-2">
                <div className="space-y-4 rounded-2xl border border-line p-5">
                  <h3 className="font-semibold text-accent">Origin</h3>
                  <label className="block">
                    <span className="mb-1 block text-sm font-medium">Pickup Type</span>
                    <select
                      value={formData.pickup_type}
                      onChange={(e) => updateForm("pickup_type", e.target.value)}
                      className="w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
                    >
                      <option value="hub">Drop at Hub</option>
                      <option value="doorstep">Doorstep Pickup</option>
                    </select>
                  </label>
                  {formData.pickup_type === "doorstep" && (
                    <label className="block">
                      <span className="mb-1 block text-sm font-medium">Pickup Address</span>
                      <textarea
                        required
                        rows={2}
                        value={formData.pickup_address}
                        onChange={(e) => updateForm("pickup_address", e.target.value)}
                        autoComplete="street-address"
                        className="w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
                      />
                    </label>
                  )}
                </div>

                <div className="space-y-4 rounded-2xl border border-line p-5">
                  <h3 className="font-semibold text-accent">Destination</h3>
                  <label className="block">
                    <span className="mb-1 block text-sm font-medium">Dropoff Type</span>
                    <select
                      value={formData.drop_type}
                      onChange={(e) => updateForm("drop_type", e.target.value)}
                      className="w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
                    >
                      <option value="hub">Hold at Hub</option>
                      <option value="doorstep">Doorstep Delivery</option>
                    </select>
                  </label>
                  {formData.drop_type === "doorstep" && (
                    <label className="block">
                      <span className="mb-1 block text-sm font-medium">Delivery Address</span>
                      <textarea
                        required
                        rows={2}
                        value={formData.drop_address}
                        onChange={(e) => updateForm("drop_address", e.target.value)}
                        autoComplete="street-address"
                        className="w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
                      />
                    </label>
                  )}
                </div>
              </div>

              <div className="mt-6 space-y-4 border-t border-line pt-6">
                <h3 className="font-semibold">Receiver Details</h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <label className="block">
                    <span className="mb-1 block text-sm font-medium">Full Name</span>
                    <input
                      type="text"
                      required
                      value={formData.receiver_name}
                      onChange={(e) => updateForm("receiver_name", e.target.value)}
                      autoComplete="name"
                      className="w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
                    />
                  </label>
                  <label className="block">
                    <span className="mb-1 block text-sm font-medium">Phone Number</span>
                    <input
                      type="tel"
                      required
                      placeholder="+94712345678"
                      value={formData.receiver_phone}
                      onChange={(e) => updateForm("receiver_phone", e.target.value)}
                      autoComplete="tel"
                      className="w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
                    />
                  </label>
                </div>
              </div>
            </div>
          )}

          {step === 4 && (
            <div className="animate-fade-up space-y-6">
              <h2 className="flex items-center gap-2 text-xl font-bold">
                <CreditCard className="h-6 w-6 text-accent" />
                Review & Payment
              </h2>
              
              <div className="rounded-2xl bg-muted/5 p-6">
                <dl className="grid grid-cols-2 gap-y-4 text-sm">
                  <dt className="text-muted">Route</dt>
                  <dd className="font-medium text-right">{formData.route_code}</dd>
                  <dt className="text-muted">Size</dt>
                  <dd className="font-medium text-right">{formData.package_size_code} ({formData.weight_kg}kg)</dd>
                  <dt className="text-muted">Pickup</dt>
                  <dd className="font-medium text-right capitalize">{formData.pickup_type}</dd>
                  <dt className="text-muted">Delivery</dt>
                  <dd className="font-medium text-right capitalize">{formData.drop_type}</dd>
                  <dt className="text-muted">Receiver</dt>
                  <dd className="font-medium text-right">{formData.receiver_name}</dd>
                </dl>
              </div>

              <div className="space-y-4">
                <label className="block">
                  <span className="mb-2 block text-sm font-medium">Payment Method</span>
                  <div className="grid grid-cols-2 gap-4">
                    <button
                      type="button"
                      onClick={() => updateForm("payment_method", "cod")}
                      className={`rounded-xl border-2 p-4 text-center transition ${
                        formData.payment_method === "cod"
                          ? "border-accent bg-accent/5 text-accent font-bold"
                          : "border-line bg-white text-muted hover:border-accent/50"
                      }`}
                    >
                      Cash on Delivery
                    </button>
                    <button
                      type="button"
                      onClick={() => updateForm("payment_method", "bank_transfer")}
                      className={`rounded-xl border-2 p-4 text-center transition ${
                        formData.payment_method === "bank_transfer"
                          ? "border-accent bg-accent/5 text-accent font-bold"
                          : "border-line bg-white text-muted hover:border-accent/50"
                      }`}
                    >
                      Bank Transfer
                    </button>
                  </div>
                </label>
              </div>
            </div>
          )}

          <div className="mt-10 flex items-center justify-between border-t border-line pt-6">
            <button
              onClick={prevStep}
              disabled={step === 1 || loading}
              className="px-6 py-2.5 font-medium text-muted transition hover:text-foreground disabled:opacity-0"
            >
              Back
            </button>
            
            {step < 4 ? (
              <button
                onClick={nextStep}
                className="flex items-center gap-2 rounded-xl bg-accent px-6 py-2.5 font-semibold text-white transition hover:brightness-110 active:scale-95"
              >
                Continue
                <ChevronRight className="h-4 w-4" />
              </button>
            ) : (
              <button
                onClick={handleSubmit}
                disabled={loading}
                className="flex items-center gap-2 rounded-xl bg-accent px-8 py-3 font-bold text-white shadow-lg transition hover:brightness-110 active:scale-95 disabled:opacity-70"
              >
                {loading ? "Booking..." : "Confirm Booking"}
              </button>
            )}
          </div>
        </div>
      </div>
    </main>
  );
}
