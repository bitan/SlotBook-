import { cn, formatDuration, formatPrice } from '@/lib/utils'
import type { Service } from '@/types'
import { Clock, DollarSign } from 'lucide-react'

interface ServiceCardProps {
  service:    Service
  selected:   boolean
  onSelect:   (service: Service) => void
}

export default function ServiceCard({ service, selected, onSelect }: ServiceCardProps) {
  return (
    <button
      onClick={() => onSelect(service)}
      className={cn(
        'w-full rounded-xl border-2 p-5 text-left transition-all',
        'hover:border-brand-400 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
        selected
          ? 'border-brand-500 bg-brand-50 shadow-sm'
          : 'border-gray-200 bg-white',
      )}
      aria-pressed={selected}
    >
      <h3 className="font-semibold text-gray-900">{service.name}</h3>

      {service.description && (
        <p className="mt-1 text-sm text-gray-500 line-clamp-2">{service.description}</p>
      )}

      <div className="mt-3 flex items-center gap-4 text-sm text-gray-600">
        <span className="flex items-center gap-1">
          <Clock className="h-4 w-4 text-gray-400" aria-hidden="true" />
          {formatDuration(service.duration_min)}
        </span>
        <span className="flex items-center gap-1 font-medium text-brand-700">
          {formatPrice(service.price)}
        </span>
      </div>
    </button>
  )
}
