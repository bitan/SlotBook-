'use client'

import { useServices } from '@/hooks/useServices'
import { formatDuration, formatPrice } from '@/lib/utils'
import { SkeletonCard } from '@/components/ui/Skeleton'
import ErrorMessage from '@/components/ui/ErrorMessage'
import { getApiErrorMessage } from '@/lib/api'
import Link from 'next/link'
import { Clock, ArrowRight } from 'lucide-react'

export default function ServicesPage() {
  const { data: services, isLoading, error, refetch } = useServices()

  return (
    <div className="container-page py-12">
      <h1 className="text-2xl font-bold text-gray-900 mb-2">Our Services</h1>
      <p className="text-gray-500 mb-8">Choose from our available services and book online.</p>

      {isLoading && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {[1, 2, 3].map((n) => <SkeletonCard key={n} />)}
        </div>
      )}

      {error && (
        <ErrorMessage message={getApiErrorMessage(error)} onRetry={refetch} />
      )}

      {services && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {services.map((service) => (
            <div
              key={service.id}
              className="rounded-xl border border-gray-200 bg-white p-5 flex flex-col gap-3"
            >
              <h2 className="font-semibold text-gray-900">{service.name}</h2>

              {service.description && (
                <p className="text-sm text-gray-500">{service.description}</p>
              )}

              <div className="flex items-center gap-3 text-sm text-gray-600 mt-auto">
                <span className="flex items-center gap-1">
                  <Clock className="h-4 w-4 text-gray-400" aria-hidden="true" />
                  {formatDuration(service.duration_min)}
                </span>
                <span className="font-semibold text-brand-700">{formatPrice(service.price)}</span>
              </div>

              <Link
                href={`/book?service=${service.id}`}
                className="mt-1 flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
              >
                Book this service
                <ArrowRight className="h-4 w-4" aria-hidden="true" />
              </Link>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
