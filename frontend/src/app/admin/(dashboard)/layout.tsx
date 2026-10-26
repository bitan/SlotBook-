'use client'

import { useEffect, type ReactNode } from 'react'
import { useRouter, usePathname } from 'next/navigation'
import Link from 'next/link'
import { useAuth } from '@/hooks/useAuth'
import { cn } from '@/lib/utils'
import {
  CalendarDays, LayoutDashboard, Users, Scissors,
  UserCog, BarChart2, Settings, LogOut, Menu, X,
} from 'lucide-react'
import { useState } from 'react'

const NAV = [
  { href: '/admin',            label: 'Dashboard',  icon: LayoutDashboard, adminOnly: false },
  { href: '/admin/bookings',   label: 'Bookings',   icon: CalendarDays,    adminOnly: false },
  { href: '/admin/services',   label: 'Services',   icon: Scissors,        adminOnly: true  },
  { href: '/admin/staff',      label: 'Staff',      icon: UserCog,         adminOnly: true  },
  { href: '/admin/customers',  label: 'Customers',  icon: Users,           adminOnly: true  },
  { href: '/admin/reports',    label: 'Reports',    icon: BarChart2,       adminOnly: true  },
  { href: '/admin/settings',   label: 'Settings',   icon: Settings,        adminOnly: true  },
]

export default function DashboardLayout({ children }: { children: ReactNode }) {
  const { user, loading, logout, isAdmin } = useAuth()
  const router    = useRouter()
  const pathname  = usePathname()
  const [open, setOpen] = useState(false)

  useEffect(() => {
    if (!loading && !user) {
      router.replace('/admin/login')
    }
  }, [user, loading, router])

  if (loading || !user) return null

  const navItems = NAV.filter((n) => !n.adminOnly || isAdmin)

  return (
    <div className="flex h-screen overflow-hidden bg-gray-50">
      {/* Sidebar */}
      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-20 flex w-64 flex-col bg-white border-r border-gray-200 transition-transform duration-200',
          open ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        )}
        aria-label="Sidebar navigation"
      >
        <div className="flex h-14 items-center gap-2 px-4 border-b border-gray-200">
          <CalendarDays className="h-5 w-5 text-brand-600" aria-hidden="true" />
          <span className="font-bold text-gray-900">SlotBook</span>
        </div>

        <nav className="flex-1 overflow-y-auto p-3 space-y-0.5">
          {navItems.map(({ href, label, icon: Icon }) => {
            const active = pathname === href || (href !== '/admin' && pathname.startsWith(href))
            return (
              <Link
                key={href}
                href={href}
                onClick={() => setOpen(false)}
                className={cn(
                  'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
                  active
                    ? 'bg-brand-50 text-brand-700'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                )}
                aria-current={active ? 'page' : undefined}
              >
                <Icon className="h-4 w-4 shrink-0" aria-hidden="true" />
                {label}
              </Link>
            )
          })}
        </nav>

        <div className="border-t border-gray-200 p-3">
          <div className="px-3 py-2 mb-1">
            <p className="text-xs font-medium text-gray-900 truncate">{user.name}</p>
            <p className="text-xs text-gray-400 capitalize">{user.role}</p>
          </div>
          <button
            onClick={logout}
            className="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
          >
            <LogOut className="h-4 w-4" aria-hidden="true" />
            Sign out
          </button>
        </div>
      </aside>

      {/* Mobile overlay */}
      {open && (
        <div
          className="fixed inset-0 z-10 bg-black/30 md:hidden"
          onClick={() => setOpen(false)}
          aria-hidden="true"
        />
      )}

      {/* Main area */}
      <div className="flex flex-1 flex-col overflow-hidden md:pl-64">
        {/* Top bar (mobile) */}
        <header className="flex h-14 items-center gap-3 border-b border-gray-200 bg-white px-4 md:hidden">
          <button
            onClick={() => setOpen(true)}
            className="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100"
            aria-label="Open navigation"
          >
            <Menu className="h-5 w-5" />
          </button>
          <span className="font-semibold text-gray-900">SlotBook</span>
        </header>

        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
          {children}
        </main>
      </div>
    </div>
  )
}
