'use client';

import { createContext, useContext, useEffect, useState, ReactNode } from 'react';
import { getToken, getUser, clearAuth, type HubUser } from '@/lib/api';

interface AuthContextType {
  user: HubUser | null;
  token: string | null;
  isLoading: boolean;
  setAuth: (token: string, user: HubUser) => void;
  signOut: () => void;
}

const AuthContext = createContext<AuthContextType>({
  user: null, token: null, isLoading: true,
  setAuth: () => {}, signOut: () => {},
});

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user,      setUser]      = useState<HubUser | null>(null);
  const [token,     setToken]     = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    setToken(getToken());
    setUser(getUser());
    setIsLoading(false);
  }, []);

  function setAuth(t: string, u: HubUser) {
    setToken(t);
    setUser(u);
  }

  function signOut() {
    clearAuth();
    setToken(null);
    setUser(null);
    window.location.href = '/login';
  }

  return (
    <AuthContext.Provider value={{ user, token, isLoading, setAuth, signOut }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
