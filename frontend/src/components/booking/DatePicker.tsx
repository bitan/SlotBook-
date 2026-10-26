'use client'

import { cn } from '@/lib/utils'
import { format, parseISO, isToday, isTomorrow } from 'date-fns'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useState } from 'react'

interface DatePickerProps {
  availableDates: string[]        // 'Y-m-d' strings in local timezone
  selected:       string | null
  onSelect:       (date: string) => void
  loading?:       boolean
}

export default function DatePicker({ availableDates, selected, onSelect, loading }: DatePickerProps) {
  const availableSet = new Set(availableDates)

  // Determine the month to display — start from the first available date
  const firstAvail = availableDates[0] ? parseISO(availableDates[0]) : new Date()
  const [viewDate, setViewDate] = useState(firstAvail)

  const year  = viewDate.getFullYear()
  const month = viewDate.getMonth()

  // Build the calendar grid for this month
  const firstDayOfMonth  = new Date(year, month, 1)
  const startPadding     = firstDayOfMonth.getDay() // 0 = Sunday
  const daysInMonth      = new Date(year, month + 1, 0).getDate()

  const cells: (number | null)[] = [
    ...Array(startPadding).fill(null),
    ...Array.from({ length: daysInMonth }, (_, i) => i + 1),
  ]

  const dateStr = (day: number) =>
    `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`

  const prevMonth = () => setViewDate(new Date(year, month - 1, 1))
  const nextMonth = () => setViewDate(new Date(year, month + 1, 1))

  return (
    <div className="rounded-xl border border-gray-200 bg-white p-4">
      {/* Header */}
      <div className="flex items-center justify-between mb-3">
        <button
          onClick={prevMonth}
          className="rounded-lg p-1 text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
          aria-label="Previous month"
        >
          <ChevronLeft className="h-5 w-5" />
        </button>
        <span className="text-sm font-semibold text-gray-800">
          {format(viewDate, 'MMMM yyyy')}
        </span>
        <button
          onClick={nextMonth}
          className="rounded-lg p-1 text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
          aria-label="Next month"
        >
          <ChevronRight className="h-5 w-5" />
        </button>
      </div>

      {/* Day labels */}
      <div className="grid grid-cols-7 text-center mb-1">
        {['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].map((d) => (
          <span key={d} className="text-xs font-medium text-gray-400 py-1">
            {d}
          </span>
        ))}
      </div>

      {/* Calendar cells */}
      <div className="grid grid-cols-7 gap-0.5">
        {cells.map((day, idx) => {
          if (!day) return <div key={`pad-${idx}`} />

          const ds         = dateStr(day)
          const available  = availableSet.has(ds)
          const isSelected = selected === ds

          return (
            <button
              key={ds}
              onClick={() => available && onSelect(ds)}
              disabled={!available || loading}
              className={cn(
                'aspect-square rounded-lg text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
                isSelected
                  ? 'bg-brand-600 text-white font-semibold'
                  : available
                  ? 'text-gray-800 hover:bg-brand-50 hover:text-brand-700'
                  : 'text-gray-300 cursor-not-allowed',
              )}
              aria-label={`${ds}${available ? '' : ' — unavailable'}`}
              aria-pressed={isSelected}
            >
              {day}
            </button>
          )
        })}
      </div>

      {loading && (
        <p className="mt-3 text-center text-xs text-gray-400 animate-pulse">Loading availability…</p>
      )}
    </div>
  )
}
