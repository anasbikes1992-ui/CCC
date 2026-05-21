"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { fetchApi, setToken } from "../../lib/api";

export default function RegisterPage() {
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const router = useRouter();

  async function handleRegister(e: React.FormEvent) {
    e.preventDefault();
    if (password !== passwordConfirmation) {
      setError("Passwords do not match");
      return;
    }

    setLoading(true);
    setError("");

    const response = await fetchApi<{ token: string }>("/auth/register", {
      method: "POST",
      body: JSON.stringify({ name, phone, password, password_confirmation: passwordConfirmation, role: "customer" }),
    });

    setLoading(false);

    if (response.success && response.data?.token) {
      setToken(response.data.token);
      router.push("/dashboard");
    } else {
      setError(response.error?.message || "Registration failed");
    }
  }

  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-12 animate-fade-up">
      <div className="text-center mb-8">
        <h1 className="text-3xl font-bold">Create Account</h1>
        <p className="mt-2 text-sm text-muted">Join Colombo Cargo Connect to send parcels.</p>
      </div>

      <form onSubmit={handleRegister} className="rounded-3xl border border-line bg-surface p-8 shadow-sm">
        <div className="space-y-4">
          <label className="block text-sm font-medium">
            Full Name
            <input
              type="text"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="John Doe"
              className="mt-1.5 w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
            />
          </label>
          <label className="block text-sm font-medium">
            Phone Number
            <input
              type="tel"
              required
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              placeholder="+94712345678"
              className="mt-1.5 w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
            />
          </label>
          <label className="block text-sm font-medium">
            Password
            <input
              type="password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              className="mt-1.5 w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
            />
          </label>
          <label className="block text-sm font-medium">
            Confirm Password
            <input
              type="password"
              required
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              placeholder="••••••••"
              className="mt-1.5 w-full rounded-xl border border-line bg-white px-3 py-2 outline-none ring-accent/20 focus:ring-2"
            />
          </label>
        </div>

        {error && <p className="mt-4 text-sm font-medium text-red-600">{error}</p>}

        <button
          type="submit"
          disabled={loading}
          className="mt-6 w-full rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 active:scale-[0.97] disabled:opacity-50"
        >
          {loading ? "Registering..." : "Register"}
        </button>

        <p className="mt-4 text-center text-sm text-muted">
          Already have an account? <a href="/login" className="text-accent hover:underline">Log In</a>
        </p>
      </form>
    </main>
  );
}
