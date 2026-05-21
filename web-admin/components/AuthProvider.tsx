'use client';

import React, { createContext, useContext, useEffect, useState } from 'react';
import { AuthUser } from '@/lib/api';

interface AuthContextType {
  user: AuthUser | null;
  token: string | null;
  setAuth: (user: AuthUser, token: string) => void;
  clearAuth: () => void;
  isLoading: boolean;
}

const AuthContext = createContext<AuthContextType>({
  user: null,
  token: null,
  setAuth: () => {},
  clearAuth: () => {},
  isLoading: true,
});

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser]     = useState<AuthUser | null>(null);
  const [token, setToken]   = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const storedToken = localStorage.getItem('ccc_admin_token');
    const storedUser  = localStorage.getItem('ccc_admin_user');
    if (storedToken && storedUser) {
      try {
        setToken(storedToken);
        setUser(JSON.parse(storedUser));
      } catch {}
    }
    setIsLoading(false);
  }, []);

  const setAuth = (u: AuthUser, t: string) => {
    setUser(u);
    setToken(t);
    localStorage.setItem('ccc_admin_token', t);
    localStorage.setItem('ccc_admin_user', JSON.stringify(u));
    // Set cookie for middleware
    document.cookie = `ccc_admin_token=${t}; path=/; max-age=86400`;
  };

  const clearAuth = () => {
    setUser(null);
    setToken(null);
    localStorage.removeItem('ccc_admin_token');
    localStorage.removeItem('ccc_admin_user');
    document.cookie = 'ccc_admin_token=; path=/; max-age=0';
  };

  return (
    <AuthContext.Provider value={{ user, token, setAuth, clearAuth, isLoading }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
