/**
 * Client-side auth helpers.
 * Token is stored in localStorage (Sanctum token auth, not cookies,
 * because the API and frontend are on different origins).
 */

import type { AdminUser } from '@/types'

const TOKEN_KEY = 'sb_token'
const USER_KEY  = 'sb_user'

export function saveAuth(token: string, user: AdminUser): void {
  localStorage.setItem(TOKEN_KEY, token)
  localStorage.setItem(USER_KEY, JSON.stringify(user))
}

export function clearAuth(): void {
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
}

export function getStoredUser(): AdminUser | null {
  if (typeof window === 'undefined') return null
  const raw = localStorage.getItem(USER_KEY)
  if (!raw) return null
  try {
    return JSON.parse(raw) as AdminUser
  } catch {
    return null
  }
}

export function getStoredToken(): string | null {
  if (typeof window === 'undefined') return null
  return localStorage.getItem(TOKEN_KEY)
}

export function isAuthenticated(): boolean {
  return !!getStoredToken()
}
