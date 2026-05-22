"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { fetchApi, setToken } from "../../lib/api";

export default function LoginPage() {
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const router = useRouter();

  async function handleLogin(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const response = await fetchApi<{ token: string }>("/auth/login", {
        method: "POST",
        body: JSON.stringify({ phone, password }),
      });

      if (response.success && response.data?.token) {
        setToken(response.data.token);
        router.push("/dashboard");
      } else {
        setError(response.error?.message || "Login failed");
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Login failed");
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-12 animate-fade-up">
      <div className="text-center mb-8">
        <h1 className="text-3xl font-bold">Welcome Back</h1>
        <p className="mt-2 text-sm text-muted">Log in to manage your shipments.</p>
      </div>

      <form onSubmit={handleLogin} className="rounded-3xl border border-line bg-surface p-8 shadow-sm">
        <div className="space-y-4">
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
        </div>

        {error && <p className="mt-4 text-sm font-medium text-red-600">{error}</p>}

        <button
          type="submit"
          disabled={loading}
          className="mt-6 w-full rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 active:scale-[0.97] disabled:opacity-50"
        >
          {loading ? "Logging in..." : "Log In"}
        </button>

        <p className="mt-4 text-center text-sm text-muted">
          Don't have an account? <a href="/register" className="text-accent hover:underline">Register</a>
        </p>
      </form>
    </main>
  );
}
