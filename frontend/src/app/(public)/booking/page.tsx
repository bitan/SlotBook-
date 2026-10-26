'use client'

// Manage-booking page: /booking?t=<token>
// Using a query param instead of a path segment avoids the
// generateStaticParams requirement for static export while keeping
// the URL short and shareable.

import { Suspense } from 'react'
import { useSearchParams } from 'next/navigation'
import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { publicApi, getApiErrorMessage } from '@/lib/api'
import { formatLocal } from '@/lib/utils'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import ErrorMessage from '@/components/ui/ErrorMessage'
import { SkeletonCard } from '@/components/ui/Skeleton'
import { CalendarDays, Clock, User, AlertTriangle } from 'lucide-react'

function ManageBookingContent() {
  const params         = useSearchParams()
  const token          = params.get('t') ?? ''
  const queryClient    = useQueryClient()
  const [showConfirm, setShowConfirm] = useState<'cancel' | null>(null)

  const { data: booking, isLoading, error } = useQuery({
    queryKey: ['booking', token],
    queryFn:  () => publicApi.getManagedBooking(token),
    enabled:  !!token,
    retry:    false,
  })

  const { mutate: cancelBooking, isPending: cancelling } = useMutation({
    mutationFn: () => publicApi.cancelBooking(token),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['booking', token] })
      setShowConfirm(null)
    },
  })

  if (!token) return (
    <div className="container-page max-w-lg py-12">
      <ErrorMessage message="No booking token provided." />
    </div>
  )

  if (isLoading) return (
    <div className="container-page max-w-lg py-12">
      <SkeletonCard />
    </div>
  )

  if (error) return (
    <div className="container-page max-w-lg py-12">
      <ErrorMessage message="This booking link is invalid or has expired." />
    </div>
  )

  if (!booking) return null

  const isActive   = booking.status === 'pending' || booking.status === 'confirmed'
  const isTerminal = ['completed', 'cancelled', 'no_show'].includes(booking.status)

  return (
    <div className="container-page max-w-lg py-12">
      <h1 className="text-2xl font-bold text-gray-900 mb-1">Your booking</h1>
      <p className="text-sm text-gray-500 mb-6">
        Ref: <span className="font-mono font-semibold">{booking.reference}</span>
      </p>

      <div className="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
        <div className="flex items-center justify-between">
          <span className="text-sm font-medium text-gray-500">Status</span>
          <Badge status={booking.status} />
        </div>
        <InfoRow icon={<CalendarDays className="h-4 w-4 text-gray-400" />} label="Date & time" value={formatLocal(booking.starts_at)} />
        <InfoRow icon={<Clock        className="h-4 w-4 text-gray-400" />} label="Service"    value={booking.service.name} />
        <InfoRow icon={<User         className="h-4 w-4 text-gray-400" />} label="Team member" value={booking.staff.name} />
      </div>

      {isActive && (
        <div className="mt-6">
          {showConfirm === 'cancel' ? (
            <div className="rounded-xl border border-red-200 bg-red-50 p-4 space-y-3">
              <div className="flex items-start gap-2">
                <AlertTriangle className="h-5 w-5 text-red-500 mt-0.5 shrink-0" aria-hidden="true" />
                <p className="text-sm text-red-700">
                  Are you sure you want to cancel? This cannot be undone.
                </p>
              </div>
              <div className="flex gap-2">
                <Button variant="danger" size="sm" onClick={() => cancelBooking()} loading={cancelling}>
                  Yes, cancel
                </Button>
                <Button variant="secondary" size="sm" onClick={() => setShowConfirm(null)} disabled={cancelling}>
                  Keep booking
                </Button>
              </div>
            </div>
          ) : (
            <Button variant="secondary" className="w-full" onClick={() => setShowConfirm('cancel')}>
              Cancel booking
            </Button>
          )}
        </div>
      )}

      {isTerminal && (
        <p className="mt-6 text-center text-sm text-gray-400">
          This booking is {booking.status.replace('_', ' ')} and cannot be changed.
        </p>
      )}
    </div>
  )
}

function InfoRow({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
  return (
    <div className="flex items-center justify-between text-sm gap-4">
      <span className="flex items-center gap-1.5 text-gray-500">{icon}{label}</span>
      <span className="font-medium text-gray-900 text-right">{value}</span>
    </div>
  )
}

export default function ManageBookingPage() {
  return (
    <Suspense>
      <ManageBookingContent />
    </Suspense>
  )
}
