'use client';

import { useEffect, useState } from 'react';
import { getDashboardStats, DashboardStats } from '@/lib/api';
import { formatCurrency, formatNumber, formatDate } from '@/lib/utils';
import { ParcelStatusBadge } from '@/components/StatusBadge';
import {
  Package, Truck, Users, TrendingUp, DollarSign,
  Activity, RefreshCw, ArrowUpRight, BarChart3,
} from 'lucide-react';
import {
  AreaChart, Area, BarChart, Bar, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts';

const STATUS_COLORS = [
  '#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6',
  '#8b5cf6','#ec4899','#14b8a6','#f97316','#84cc16',
];

function KpiCard({ label, value, icon: Icon, sub, color }: {
  label: string; value: string | number; icon: React.ElementType;
  sub?: string; color: string;
}) {
  return (
    <div className="kpi-card" style={{ padding: '20px 22px' }}>
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between' }}>
        <div>
          <div style={{ fontSize: 12, color: 'var(--text-muted)', fontWeight: 600, letterSpacing: '0.04em', textTransform: 'uppercase', marginBottom: 8 }}>
            {label}
          </div>
          <div style={{ fontSize: 28, fontWeight: 800, color: 'var(--text-primary)', lineHeight: 1 }}>
            {value}
          </div>
          {sub && <div style={{ fontSize: 12, color: 'var(--text-muted)', marginTop: 6 }}>{sub}</div>}
        </div>
        <div style={{
          width: 42, height: 42, borderRadius: 10,
          background: `${color}22`, border: `1px solid ${color}44`,
          display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
        }}>
          <Icon size={18} color={color} />
        </div>
      </div>
    </div>
  );
}

export default function DashboardPage() {
  const [stats, setStats]     = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState('');
  const [lastRefresh, setLastRefresh] = useState(new Date());

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const data = await getDashboardStats();
      setStats(data);
      setLastRefresh(new Date());
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, []);

  const statusPieData = stats
    ? Object.entries(stats.parcels_by_status).map(([name, value]) => ({ name, value: Number(value) }))
    : [];

  return (
    <div className="animate-fade-in">
      {/* Header */}
      <div className="page-header">
        <div>
          <h1 className="page-title">God&apos;s View Dashboard</h1>
          <p className="page-subtitle">
            Last refreshed: {lastRefresh.toLocaleTimeString()}
          </p>
        </div>
        <button onClick={load} className="btn btn-secondary" disabled={loading}>
          <RefreshCw size={14} className={loading ? 'animate-spin' : ''} />
          Refresh
        </button>
      </div>

      {error && (
        <div style={{
          marginBottom: 20, padding: '12px 16px', borderRadius: 8,
          background: 'var(--danger-dim)', border: '1px solid rgba(239,68,68,0.3)',
          color: 'var(--danger)', fontSize: 13,
        }}>
          ⚠ {error}
        </div>
      )}

      {/* KPI Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 16, marginBottom: 28 }}>
        <KpiCard label="Bookings Today"   value={formatNumber(stats?.kpis.bookings_today)}  icon={Package}    sub={`${formatNumber(stats?.kpis.bookings_mtd)} this month`} color="#6366f1" />
        <KpiCard label="Revenue Today"    value={formatCurrency(stats?.kpis.revenue_today)}  icon={DollarSign} sub={`${formatCurrency(stats?.kpis.revenue_mtd)} MTD`}          color="#10b981" />
        <KpiCard label="Active Trips"     value={formatNumber(stats?.kpis.active_trips)}     icon={Truck}      sub="En route or loading"                                          color="#f59e0b" />
        <KpiCard label="Total Customers"  value={formatNumber(stats?.kpis.total_customers)}  icon={Users}      sub="Registered senders"                                           color="#3b82f6" />
      </div>

      {/* Charts row 1 */}
      <div style={{ display: 'grid', gridTemplateColumns: '2fr 1fr', gap: 18, marginBottom: 18 }}>
        {/* 7-day trend */}
        <div className="glass" style={{ padding: '20px 22px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 20 }}>
            <BarChart3 size={16} color="var(--accent)" />
            <span style={{ fontSize: 14, fontWeight: 600, color: 'var(--text-primary)' }}>7-Day Booking Trend</span>
          </div>
          <ResponsiveContainer width="100%" height={200}>
            <AreaChart data={stats?.last_7_days ?? []}>
              <defs>
                <linearGradient id="colorCount" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%"  stopColor="#6366f1" stopOpacity={0.3} />
                  <stop offset="95%" stopColor="#6366f1" stopOpacity={0} />
                </linearGradient>
                <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%"  stopColor="#10b981" stopOpacity={0.3} />
                  <stop offset="95%" stopColor="#10b981" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.04)" />
              <XAxis dataKey="date" tick={{ fontSize: 11, fill: '#64748b' }} tickFormatter={(v) => v.slice(5)} />
              <YAxis tick={{ fontSize: 11, fill: '#64748b' }} />
              <Tooltip
                contentStyle={{ background: '#1a2235', border: '1px solid #1e2d45', borderRadius: 8, fontSize: 12 }}
                labelStyle={{ color: '#94a3b8' }}
              />
              <Area type="monotone" dataKey="count"   stroke="#6366f1" fill="url(#colorCount)"   name="Bookings" strokeWidth={2} />
              <Area type="monotone" dataKey="revenue" stroke="#10b981" fill="url(#colorRevenue)" name="Revenue"  strokeWidth={2} />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        {/* Status distribution */}
        <div className="glass" style={{ padding: '20px 22px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 16 }}>
            <Activity size={16} color="var(--accent)" />
            <span style={{ fontSize: 14, fontWeight: 600, color: 'var(--text-primary)' }}>Parcel Status Mix</span>
          </div>
          <ResponsiveContainer width="100%" height={160}>
            <PieChart>
              <Pie data={statusPieData} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={65} innerRadius={35}>
                {statusPieData.map((_, i) => (
                  <Cell key={i} fill={STATUS_COLORS[i % STATUS_COLORS.length]} />
                ))}
              </Pie>
              <Tooltip contentStyle={{ background: '#1a2235', border: '1px solid #1e2d45', borderRadius: 8, fontSize: 11 }} />
            </PieChart>
          </ResponsiveContainer>
          <div style={{ marginTop: 8, display: 'flex', flexWrap: 'wrap', gap: '4px 12px' }}>
            {statusPieData.slice(0, 6).map((d, i) => (
              <div key={d.name} style={{ display: 'flex', alignItems: 'center', gap: 5, fontSize: 10, color: 'var(--text-muted)' }}>
                <div style={{ width: 7, height: 7, borderRadius: '50%', background: STATUS_COLORS[i % STATUS_COLORS.length] }} />
                {d.name.replace(/_/g, ' ')} ({d.value})
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Recent Parcels */}
      <div className="glass" style={{ padding: '20px 22px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
            <TrendingUp size={16} color="var(--accent)" />
            <span style={{ fontSize: 14, fontWeight: 600, color: 'var(--text-primary)' }}>Recent Parcels</span>
          </div>
          <a href="/parcels" style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 12, color: 'var(--accent-light)', textDecoration: 'none' }}>
            View all <ArrowUpRight size={12} />
          </a>
        </div>
        <table className="data-table">
          <thead>
            <tr>
              <th>Parcel #</th>
              <th>Customer</th>
              <th>Route</th>
              <th>Status</th>
              <th>Value</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            {loading && !stats ? (
              <tr><td colSpan={6} style={{ textAlign: 'center', padding: 30, color: 'var(--text-muted)' }}>Loading...</td></tr>
            ) : (stats?.recent_parcels ?? []).map((p) => (
              <tr key={p.id} style={{ cursor: 'pointer' }} onClick={() => window.location.href = `/parcels/${p.id}`}>
                <td><span style={{ fontFamily: 'monospace', fontSize: 12, color: 'var(--accent-light)' }}>{p.parcel_number}</span></td>
                <td style={{ color: 'var(--text-secondary)' }}>{p.customer_name ?? '—'}</td>
                <td style={{ color: 'var(--text-secondary)', fontSize: 12 }}>{p.route ?? '—'}</td>
                <td><ParcelStatusBadge status={p.status} /></td>
                <td style={{ fontWeight: 600 }}>{formatCurrency(p.total_price)}</td>
                <td style={{ color: 'var(--text-muted)', fontSize: 12 }}>{formatDate(p.created_at)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
