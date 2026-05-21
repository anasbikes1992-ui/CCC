'use client';

import HubShell from '@/components/HubShell';
import { AuthProvider } from '@/components/AuthProvider';
import { Toaster } from 'sonner';

export default function HubLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthProvider>
      <HubShell>{children}</HubShell>
      <Toaster richColors position="top-center" />
    </AuthProvider>
  );
}
