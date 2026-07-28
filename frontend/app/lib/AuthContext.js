"use client";

import { createContext, useCallback, useContext, useEffect, useState } from "react";
import { login as apiLogin, logout as apiLogout, me as apiMe, register as apiRegister } from "./auth-api";

const AuthContext = createContext(null);
const TOKEN_KEY = "xiangqi_token";

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(true);

  // Try to restore a previous session from localStorage on first load.
  // Everything that touches state is wrapped in the async function below so
  // no setState call ever runs synchronously within the effect body itself.
  useEffect(() => {
    let cancelled = false;

    async function restore() {
      const stored = window.localStorage.getItem(TOKEN_KEY);
      if (!stored) return;

      try {
        const u = await apiMe(stored);
        if (!cancelled) {
          setUser(u);
          setToken(stored);
        }
      } catch {
        if (!cancelled) window.localStorage.removeItem(TOKEN_KEY);
      }
    }

    restore().finally(() => {
      if (!cancelled) setLoading(false);
    });

    return () => {
      cancelled = true;
    };
  }, []);

  const login = useCallback(async (credentials) => {
    const res = await apiLogin(credentials);
    window.localStorage.setItem(TOKEN_KEY, res.token);
    setToken(res.token);
    setUser(res.user);
    return res.user;
  }, []);

  const register = useCallback(async (data) => {
    const res = await apiRegister(data);
    window.localStorage.setItem(TOKEN_KEY, res.token);
    setToken(res.token);
    setUser(res.user);
    return res.user;
  }, []);

  const logout = useCallback(async () => {
    if (token) {
      await apiLogout(token).catch(() => {});
    }
    window.localStorage.removeItem(TOKEN_KEY);
    setToken(null);
    setUser(null);
  }, [token]);

  return (
    <AuthContext.Provider value={{ user, token, loading, login, register, logout }}>{children}</AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return ctx;
}
