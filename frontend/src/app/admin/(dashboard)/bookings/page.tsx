'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { adminApi, getApiErrorMessage } from '@/lib/api'
import { formatLocal } from '@/lib/utils'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { SkeletonRow } from '@/components/ui/Skeleton'
import ErrorMessage from '@/components/ui/ErrorMessage'
import type { BookingStatus } from '@/types'

const STATUSES: { value: string; label: string }[] = [
  { value: '',          label: 'All'       },
  { value: 'pending',   label: 'Pending'   },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'no_show',   label: 'No Show'   },
]

export default function BookingsPage() {
  const queryClient  = useQueryClient()
  const [date,       setDate]   = useState('')
  const [status,     setStatus] = useState('')
  const [page,       setPage]   = useState(1)

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['admin', 'bookings', { date, status, page }],
    queryFn:  () => adminApi.getBookings({
      date:   date   || undefined,
      status: status || undefined,
      page,
    }),
  })

  const { mutate: updateStatus } = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      adminApi.updateBooking(id, { status }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'bookings'] }),
  })

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">Bookings</h1>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap gap-3 rounded-xl border border-gray-200 bg-white p-4">
        <div>
          <label htmlFor="filter-date" className="block text-xs font-medium text-gray-500 mb-1">Date</label>
          <input
            id="filter-date"
            type="date"
            value={date}
            onChange={(e) => { setDate(e.target.value); setPage(1) }}
            className="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
          />
        </div>

        <div>
          <label className="block text-xs font-medium text-gray-500 mb-1">Status</label>
          <div className="flex gap-1 flex-wrap">
            {STATUSES.map((s) => (
              <button
                key={s.value}
                onClick={() => { setStatus(s.value); setPage(1) }}
                className={`rounded-full px-3 py-1 text-xs font-medium transition-colors ${
                  status === s.value
                    ? 'bg-brand-600 text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                }`}
              >
                {s.label}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="rounded-xl border border-gray-200 bg-white overflow-hidden">
        {isLoading && (
          <div className="px-5 py-3 divide-y divide-gray-100">
            {[1, 2, 3, 4, 5].map((n) => <SkeletonRow key={n} />)}
          </div>
        )}

        {error && (
          <div className="p-5">
            <ErrorMessage message={getApiErrorMessage(error)} onRetry={refetch} />
          </div>
        )}

        {data && data.data.length === 0 && (
          <p className="py-12 text-center text-sm text-gray-400">No bookings found.</p>
        )}

        {data && data.data.length > 0 && (
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b border-gray-100 bg-gray-50">
                  {['Ref', 'Customer', 'Service', 'Staff', 'Time', 'Status', 'Actions'].map((h) => (
                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {data.data.map((b) => (
                  <tr key={b.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-mono text-xs text-gray-500">{b.reference}</td>
                    <td className="px-4 py-3">
                      <p className="font-medium text-gray-900">{b.customer.name}</p>
                      <p className="text-xs text-gray-400">{b.customer.phone}</p>
                    </td>
                    <td className="px-4 py-3 text-gray-700">{b.service.name}</td>
                    <td className="px-4 py-3 text-gray-700">{b.staff.name}</td>
                    <td className="px-4 py-3 text-gray-700 whitespace-nowrap">
                      {formatLocal(b.starts_at)}
                    </td>
                    <td className="px-4 py-3"><Badge status={b.status} /></td>
                    <td className="px-4 py-3">
                      {b.status === 'confirmed' && (
                        <button
                          onClick={() => updateStatus({ id: b.id, status: 'completed' })}
                          className="text-xs text-brand-600 hover:text-brand-800 font-medium"
                        >
                          Mark done
                        </button>
                      )}
                      {b.status === 'pending' && (
                        <button
                          onClick={() => updateStatus({ id: b.id, status: 'confirmed' })}
                          className="text-xs text-green-600 hover:text-green-800 font-medium"
                        >
                          Confirm
                        </button>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {data && data.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-gray-100 px-4 py-3">
            <p className="text-xs text-gray-500">
              Page {data.current_page} of {data.last_page} · {data.total} total
            </p>
            <div className="flex gap-2">
              <Button size="sm" variant="secondary" disabled={page === 1} onClick={() => setPage(p => p - 1)}>
                Prev
              </Button>
              <Button size="sm" variant="secondary" disabled={page === data.last_page} onClick={() => setPage(p => p + 1)}>
                Next
              </Button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
