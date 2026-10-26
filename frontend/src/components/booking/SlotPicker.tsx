import { cn, formatLocalTime } from '@/lib/utils'
import type { Slot } from '@/types'
import { SkeletonRow } from '@/components/ui/Skeleton'

interface SlotPickerProps {
  slots:    Slot[]
  selected: string | null    // ISO starts_at string
  onSelect: (slot: Slot) => void
  loading?: boolean
}

export default function SlotPicker({ slots, selected, onSelect, loading }: SlotPickerProps) {
  if (loading) {
    return (
      <div className="space-y-2">
        {[1, 2, 3, 4].map((n) => <SkeletonRow key={n} />)}
      </div>
    )
  }

  if (slots.length === 0) {
    return (
      <p className="rounded-lg bg-gray-50 p-4 text-center text-sm text-gray-500">
        No slots available on this date. Please try another day.
      </p>
    )
  }

  // Group slots by AM / PM for easier scanning
  const am = slots.filter((s) => new Date(s.starts_at).getUTCHours() < 9)   // before local noon
  const pm = slots.filter((s) => new Date(s.starts_at).getUTCHours() >= 9)

  const renderGroup = (label: string, group: Slot[]) => (
    group.length > 0 && (
      <div key={label}>
        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{label}</p>
        <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
          {group.map((slot) => {
            const isSelected = selected === slot.starts_at
            return (
              <button
                key={slot.starts_at}
                onClick={() => onSelect(slot)}
                className={cn(
                  'rounded-lg border py-2 text-sm font-medium transition-all',
                  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
                  isSelected
                    ? 'border-brand-500 bg-brand-600 text-white'
                    : 'border-gray-200 bg-white text-gray-700 hover:border-brand-400 hover:bg-brand-50',
                )}
                aria-pressed={isSelected}
              >
                {formatLocalTime(slot.starts_at)}
              </button>
            )
          })}
        </div>
      </div>
    )
  )

  return (
    <div className="space-y-4" aria-label="Available time slots">
      {renderGroup('Morning', am)}
      {renderGroup('Afternoon & Evening', pm)}
    </div>
  )
}
