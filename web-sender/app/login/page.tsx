"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { fetchApi, setToken } from "../../lib/api";

export default function LoginPage() {
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
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
    <>
      {/* Skip to main content link */}
      <a 
        href="#main-content" 
        className="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:left-4 focus:top-4 focus:px-4 focus:py-2 focus:bg-accent focus:text-white focus:rounded-lg"
      >
        Skip to main content
      </a>

      <main id="main-content" tabIndex={-1} className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center px-4 py-12 animate-fade-up">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold">Welcome Back</h1>
          <p className="mt-2 text-sm text-muted">Log in to manage your shipments.</p>
        </div>

        <form onSubmit={handleLogin} className="rounded-3xl border border-line bg-surface p-8 shadow-sm" aria-label="Login form">
        <div className="space-y-4">
          <div>
            <label htmlFor="phone" className="block text-sm font-medium">
              Phone Number
            </label>
            <input
              id="phone"
              name="phone"
              type="tel"
              autoComplete="tel"
              inputMode="tel"
              required
              aria-required="true"
              aria-invalid={error ? "true" : "false"}
              aria-describedby={error ? "login-error" : undefined}
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              placeholder="+94712345678"
              className="mt-1.5 w-full rounded-xl border border-line bg-white px-3 py-2 outline-none focus:outline-2 focus:outline-accent focus:outline-offset-0 focus:ring-4 focus:ring-accent/20"
            />
          </div>
          <div>
            <label htmlFor="password" className="block text-sm font-medium">
              Password
            </label>
            <div className="relative">
              <input
                id="password"
                name="password"
                type={showPassword ? "text" : "password"}
                autoComplete="current-password"
                required
                aria-required="true"
                aria-invalid={error ? "true" : "false"}
                aria-describedby={error ? "login-error" : undefined}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                className="mt-1.5 w-full rounded-xl border border-line bg-white px-3 py-2 pr-10 outline-none focus:outline-2 focus:outline-accent focus:outline-offset-0 focus:ring-4 focus:ring-accent/20"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                aria-label={showPassword ? "Hide password" : "Show password"}
                className="absolute right-2 top-1/2 -translate-y-1/2 mt-0.75 p-2 rounded-lg hover:bg-gray-100 focus:outline-2 focus:outline-accent focus:outline-offset-2"
              >
                {showPassword ? (
                  <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                ) : (
                  <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                )}
              </button>
            </div>
          </div>
        </div>

        {error && (
          <div 
            id="login-error" 
            role="alert" 
            aria-live="assertive" 
            className="mt-4 flex items-center gap-2 text-sm font-medium text-red-600"
          >
            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
            {error}
          </div>
        )}

        <button
          type="submit"
          disabled={loading}
          aria-busy={loading}
          className="mt-6 w-full rounded-xl bg-accent px-5 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 active:scale-[0.97] disabled:opacity-50 focus:outline-2 focus:outline-accent focus:outline-offset-2"
        >
          {loading ? (
            <>
              <span className="inline-block animate-spin mr-2" aria-hidden="true">⏳</span>
              <span>Logging in...</span>
              <span className="sr-only">Please wait</span>
            </>
          ) : (
            "Log In"
          )}
        </button>

        <p className="mt-4 text-center text-sm text-muted">
          Don't have an account?{" "}
          <a 
            href="/register" 
            className="text-accent hover:underline focus:outline-2 focus:outline-accent focus:outline-offset-2 focus:rounded"
          >
            Register
          </a>
        </p>
      </form>
    </main>
    </>
  );
}
