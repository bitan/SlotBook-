'use client'

import { Suspense } from 'react'
import { useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { CheckCircle, CalendarDays } from 'lucide-react'

function ConfirmationContent() {
  const params    = useSearchParams()
  const reference = params.get('ref')

  return (
    <div className="container-page max-w-md py-20 text-center">
      <div className="flex justify-center mb-4">
        <CheckCircle className="h-16 w-16 text-brand-600" aria-hidden="true" />
      </div>

      <h1 className="text-2xl font-bold text-gray-900 mb-2">Booking confirmed!</h1>

      <p className="text-gray-500 mb-6">
        Your booking reference is:
      </p>

      <div className="inline-block rounded-xl bg-brand-50 border border-brand-200 px-8 py-4 mb-6">
        <span className="text-3xl font-bold tracking-widest text-brand-700 font-mono">
          {reference ?? '—'}
        </span>
      </div>

      <p className="text-sm text-gray-500 mb-8">
        If you provided an email address, you&apos;ll receive a confirmation with a link to manage your booking.
        You can cancel or reschedule up to 2 hours before your appointment.
      </p>

      <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
        <Link
          href="/"
          className="flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
        >
          Back to home
        </Link>
        <Link
          href="/book"
          className="flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 transition-colors"
        >
          <CalendarDays className="h-4 w-4" aria-hidden="true" />
          Book another
        </Link>
      </div>
    </div>
  )
}

export default function ConfirmationPage() {
  return (
    <Suspense>
      <ConfirmationContent />
    </Suspense>
  )
}
