import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'
import { formatInTimeZone } from 'date-fns-tz'
import { format } from 'date-fns'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

const BUSINESS_TZ = 'Africa/Addis_Ababa'

/** Format a UTC ISO string in the business timezone. */
export function formatLocal(isoUtc: string, fmt = 'EEE, MMM d · h:mm a'): string {
  return formatInTimeZone(new Date(isoUtc), BUSINESS_TZ, fmt)
}

/** Format a UTC ISO string as a short date (e.g. "Mon, Oct 7"). */
export function formatLocalDate(isoUtc: string): string {
  return formatInTimeZone(new Date(isoUtc), BUSINESS_TZ, 'EEE, MMM d, yyyy')
}

/** Format a UTC ISO string as a time only (e.g. "10:30 AM"). */
export function formatLocalTime(isoUtc: string): string {
  return formatInTimeZone(new Date(isoUtc), BUSINESS_TZ, 'h:mm a')
}

/** Format price in ETB cents to display string (e.g. 15000 → "150.00 ETB"). */
export function formatPrice(cents: number): string {
  return (cents / 100).toFixed(2) + ' ETB'
}

/** Duration in minutes → human string (e.g. 90 → "1 hr 30 min"). */
export function formatDuration(minutes: number): string {
  if (minutes < 60) return `${minutes} min`
  const hrs = Math.floor(minutes / 60)
  const rem = minutes % 60
  return rem === 0 ? `${hrs} hr` : `${hrs} hr ${rem} min`
}

/** Status → Tailwind color class. */
export function statusColor(status: string): string {
  const map: Record<string, string> = {
    pending:   'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-gray-100 text-gray-700',
    no_show:   'bg-red-100 text-red-800',
  }
  return map[status] ?? 'bg-gray-100 text-gray-700'
}

/** Weekday index → label. */
export const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
