'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { AuthProvider, useAuth } from '@/components/AuthProvider';
import { login } from '@/lib/api';
import { Boxes, Lock, Mail, Eye, EyeOff, AlertCircle } from 'lucide-react';

function LoginForm() {
  const [email, setEmail]       = useState('');
  const [password, setPassword] = useState('');
  const [showPw, setShowPw]     = useState(false);
  const [loading, setLoading]   = useState(false);
  const [error, setError]       = useState('');
  const { setAuth }             = useAuth();
  const router                  = useRouter();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const { user, token } = await login(email.trim(), password);

      if (!['admin_super', 'super_admin', 'ops_admin', 'finance_admin', 'support_admin'].includes(user.role)) {
        setError('Access denied. Admin accounts only.');
        setLoading(false);
        return;
      }

      setAuth(user, token);
      router.replace('/');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Connection error. Is the backend running?');
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      {/* Skip to main content link */}
      <a 
        href="#main-content" 
        style={{
          position: 'absolute',
          left: '-9999px',
          zIndex: 999,
          padding: '1em',
          backgroundColor: '#6366f1',
          color: 'white',
          textDecoration: 'none',
          borderRadius: '4px',
        }}
        onFocus={(e) => { e.currentTarget.style.left = '10px'; e.currentTarget.style.top = '10px'; }}
        onBlur={(e) => { e.currentTarget.style.left = '-9999px'; }}
      >
        Skip to main content
      </a>

      <div id="main-content" tabIndex={-1} style={{
        minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center',
        background: 'var(--bg-primary)', padding: 20,
      }}>
      {/* Background gradient orbs */}
      <div style={{
        position: 'fixed', top: -100, left: -100, width: 500, height: 500,
        borderRadius: '50%',
        background: 'radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%)',
        pointerEvents: 'none',
      }} />
      <div style={{
        position: 'fixed', bottom: -100, right: -100, width: 400, height: 400,
        borderRadius: '50%',
        background: 'radial-gradient(circle, rgba(16,185,129,0.08) 0%, transparent 70%)',
        pointerEvents: 'none',
      }} />

      <div className="animate-fade-in" style={{ width: '100%', maxWidth: 400 }}>
        {/* Header */}
        <header style={{ textAlign: 'center', marginBottom: 32 }}>
          <div style={{
            width: 56, height: 56, borderRadius: 14, margin: '0 auto 16px',
            background: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            boxShadow: '0 8px 32px rgba(99,102,241,0.35)',
          }}
          role="img"
          aria-label="CCC Admin Portal Logo"
          >
            <Boxes size={26} color="white" aria-hidden="true" />
          </div>
          <h1 style={{ fontSize: 24, fontWeight: 800, color: 'var(--text-primary)', marginBottom: 6 }}>
            CCC Admin Portal
          </h1>
          <p style={{ fontSize: 13, color: 'var(--text-muted)' }}>
            Colombo Cargo Connect — Operations Dashboard
          </p>
        </header>

        {/* Card */}
        <div className="glass" style={{ padding: 32 }}>
          <form onSubmit={handleSubmit} aria-label="Admin login form">
            {/* Email */}
            <div style={{ marginBottom: 16 }}>
              <label 
                htmlFor="email"
                style={{ display: 'block', fontSize: 12, fontWeight: 600, color: 'var(--text-secondary)', marginBottom: 6 }}
              >
                Email or Phone
              </label>
              <div style={{ position: 'relative' }}>
                <Mail size={15} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} aria-hidden="true" />
                <input
                  id="email"
                  name="email"
                  type="text"
                  autoComplete="email"
                  className="form-input"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="anasbikes1992@gmail.com"
                  required
                  aria-required="true"
                  aria-invalid={error ? "true" : "false"}
                  aria-describedby={error ? "login-error" : undefined}
                  style={{ paddingLeft: 36 }}
                />
              </div>
            </div>

            {/* Password */}
            <div style={{ marginBottom: 24 }}>
              <label 
                htmlFor="password"
                style={{ display: 'block', fontSize: 12, fontWeight: 600, color: 'var(--text-secondary)', marginBottom: 6 }}
              >
                Password
              </label>
              <div style={{ position: 'relative' }}>
                <Lock size={15} style={{ position: 'absolute', left: 12, top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} aria-hidden="true" />
                <input
                  id="password"
                  name="password"
                  type={showPw ? 'text' : 'password'}
                  autoComplete="current-password"
                  className="form-input"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••"
                  required
                  aria-required="true"
                  aria-invalid={error ? "true" : "false"}
                  aria-describedby={error ? "login-error" : undefined}
                  style={{ paddingLeft: 36, paddingRight: 40 }}
                />
                <button
                  type="button"
                  onClick={() => setShowPw(!showPw)}
                  aria-label={showPw ? "Hide password" : "Show password"}
                  style={{
                    position: 'absolute', right: 10, top: '50%', transform: 'translateY(-50%)',
                    background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted)',
                    padding: 8, borderRadius: 4, minWidth: 32, minHeight: 32,
                  }}
                >
                  {showPw ? <EyeOff size={15} aria-hidden="true" /> : <Eye size={15} aria-hidden="true" />}
                </button>
              </div>
            </div>

            {/* Error */}
            {error && (
              <div 
                id="login-error"
                role="alert"
                aria-live="assertive"
                style={{
                  marginBottom: 18, padding: '10px 12px', borderRadius: 8,
                  background: 'var(--danger-dim)', border: '1px solid rgba(239,68,68,0.3)',
                  display: 'flex', alignItems: 'center', gap: 8,
                }}
              >
                <AlertCircle size={14} color="var(--danger)" aria-hidden="true" />
                <span style={{ fontSize: 12.5, color: 'var(--danger)' }}>{error}</span>
              </div>
            )}

            {/* Submit */}
            <button
              id="login-btn"
              type="submit"
              className="btn btn-primary"
              style={{ width: '100%', justifyContent: 'center', padding: '11px 16px', fontSize: 14, minHeight: 44 }}
              disabled={loading}
              aria-busy={loading}
            >
              {loading ? (
                <>
                  <div style={{ width: 14, height: 14, border: '2px solid rgba(255,255,255,0.3)', borderTopColor: 'white', borderRadius: '50%' }} className="animate-spin" aria-hidden="true" />
                  <span>Signing in...</span>
                  <span className="sr-only">Please wait</span>
                </>
              ) : (
                'Sign In'
              )}
            </button>
          </form>

          <div style={{ marginTop: 20, padding: '10px 12px', borderRadius: 8, background: 'rgba(255,255,255,0.02)', border: '1px solid var(--border)' }} role="complementary" aria-label="Demo credentials">
            <div style={{ fontSize: 11, color: 'var(--text-muted)', marginBottom: 4, fontWeight: 600 }}>DEMO CREDENTIALS</div>
            <div style={{ fontSize: 12, color: 'var(--text-secondary)' }}>Email or Phone: anasbikes1992@gmail.com / +94771350035</div>
            <div style={{ fontSize: 12, color: 'var(--text-secondary)' }}>Password: Aa123456</div>
          </div>
        </div>

        <footer style={{ textAlign: 'center', fontSize: 12, color: 'var(--text-muted)', marginTop: 20 }} role="contentinfo">
          Colombo Cargo Connect © 2026 · Admin v1.0
        </footer>
      </div>
    </div>
    </>
  );
}

export default function LoginPage() {
  return (
    <AuthProvider>
      <LoginForm />
    </AuthProvider>
  );
}
