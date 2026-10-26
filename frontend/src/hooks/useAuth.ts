'use client'

import { useState, useEffect, useCallback } from 'react'
import { useRouter } from 'next/navigation'
import { authApi, getApiErrorMessage } from '@/lib/api'
import { saveAuth, clearAuth, getStoredUser } from '@/lib/auth'
import type { AdminUser } from '@/types'

export function useAuth() {
  const router = useRouter()
  const [user, setUser]       = useState<AdminUser | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const stored = getStoredUser()
    setUser(stored)
    setLoading(false)
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    const { token, user: me } = await authApi.login(email, password)
    saveAuth(token, me)
    setUser(me)
    router.push('/admin')
  }, [router])

  const logout = useCallback(async () => {
    try {
      await authApi.logout()
    } catch {
      // Ignore — clear local state regardless
    }
    clearAuth()
    setUser(null)
    router.push('/admin/login')
  }, [router])

  return { user, loading, login, logout, isAdmin: user?.role === 'admin' }
}
