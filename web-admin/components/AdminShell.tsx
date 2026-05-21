'use client';

import { AuthProvider } from './AuthProvider';
import Sidebar from './Sidebar';

export default function AdminShell({ children }: { children: React.ReactNode }) {
  return (
    <AuthProvider>
      <div style={{ display: 'flex', minHeight: '100vh' }}>
        <Sidebar />
        <main
          className="main-content"
          style={{
            marginLeft: 240,
            flex: 1,
            padding: '28px 32px',
            minHeight: '100vh',
            background: 'var(--bg-primary)',
          }}
        >
          {children}
        </main>
      </div>
    </AuthProvider>
  );
}
