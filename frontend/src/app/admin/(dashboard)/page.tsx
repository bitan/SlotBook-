'use client'

import { useQuery } from '@tanstack/react-query'
import { adminApi, getApiErrorMessage } from '@/lib/api'
import { formatLocal } from '@/lib/utils'
import { useAuth } from '@/hooks/useAuth'
import Badge from '@/components/ui/Badge'
import { SkeletonRow } from '@/components/ui/Skeleton'
import ErrorMessage from '@/components/ui/ErrorMessage'
import { format } from 'date-fns'
import Link from 'next/link'
import { CalendarDays, Users, CheckCircle, Clock } from 'lucide-react'

export default function DashboardPage() {
  const { user }   = useAuth()
  const today      = format(new Date(), 'yyyy-MM-dd')

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['admin', 'bookings', 'today'],
    queryFn:  () => adminApi.getBookings({ date: today, per_page: 50 }),
    refetchInterval: 60_000, // refresh every minute
  })

  const bookings = data?.data ?? []

  const stats = {
    total:     bookings.length,
    confirmed: bookings.filter((b) => b.status === 'confirmed').length,
    pending:   bookings.filter((b) => b.status === 'pending').length,
    completed: bookings.filter((b) => b.status === 'completed').length,
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">
          Good {getGreeting()}, {user?.name.split(' ')[0]}
        </h1>
        <p className="text-sm text-gray-500 mt-0.5">
          Today is {format(new Date(), 'EEEE, MMMM d')}
        </p>
      </div>

      {/* Stat cards */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <StatCard label="Today's bookings" value={stats.total}     icon={<CalendarDays className="h-5 w-5" />} color="brand" />
        <StatCard label="Confirmed"        value={stats.confirmed} icon={<CheckCircle   className="h-5 w-5" />} color="blue"  />
        <StatCard label="Pending"          value={stats.pending}   icon={<Clock         className="h-5 w-5" />} color="yellow" />
        <StatCard label="Completed"        value={stats.completed} icon={<CheckCircle   className="h-5 w-5" />} color="green" />
      </div>

      {/* Today's bookings list */}
      <div className="rounded-xl border border-gray-200 bg-white">
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <h2 className="font-semibold text-gray-900">Today&apos;s schedule</h2>
          <Link href="/admin/bookings" className="text-sm text-brand-600 hover:text-brand-700 font-medium">
            View all
          </Link>
        </div>

        {isLoading && (
          <div className="px-5 py-3 divide-y divide-gray-100">
            {[1, 2, 3].map((n) => <SkeletonRow key={n} />)}
          </div>
        )}

        {error && (
          <div className="p-5">
            <ErrorMessage message={getApiErrorMessage(error)} onRetry={refetch} />
          </div>
        )}

        {!isLoading && bookings.length === 0 && (
          <p className="px-5 py-8 text-center text-sm text-gray-400">
            No bookings scheduled for today.
          </p>
        )}

        {bookings.length > 0 && (
          <ul className="divide-y divide-gray-100" role="list">
            {bookings.map((b) => (
              <li key={b.id} className="flex items-center gap-4 px-5 py-3">
                <div className="text-sm font-medium text-gray-700 w-20 shrink-0 tabular-nums">
                  {formatLocal(b.starts_at, 'h:mm a')}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 truncate">{b.customer.name}</p>
                  <p className="text-xs text-gray-500">{b.service.name} · {b.staff.name}</p>
                </div>
                <Badge status={b.status} />
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  )
}

function StatCard({
  label, value, icon, color,
}: {
  label: string
  value: number
  icon:  React.ReactNode
  color: 'brand' | 'blue' | 'yellow' | 'green'
}) {
  const colors = {
    brand:  'bg-brand-50  text-brand-700',
    blue:   'bg-blue-50   text-blue-700',
    yellow: 'bg-yellow-50 text-yellow-700',
    green:  'bg-green-50  text-green-700',
  }

  return (
    <div className="rounded-xl border border-gray-200 bg-white p-4">
      <div className={`inline-flex items-center justify-center rounded-lg p-2 mb-3 ${colors[color]}`}>
        {icon}
      </div>
      <p className="text-2xl font-bold text-gray-900">{value}</p>
      <p className="text-xs text-gray-500 mt-0.5">{label}</p>
    </div>
  )
}

function getGreeting(): string {
  const h = new Date().getHours()
  if (h < 12) return 'morning'
  if (h < 17) return 'afternoon'
  return 'evening'
}
