import { cn, statusColor } from '@/lib/utils'
import type { BookingStatus } from '@/types'

interface BadgeProps {
  status: BookingStatus
  className?: string
}

const LABELS: Record<BookingStatus, string> = {
  pending:   'Pending',
  confirmed: 'Confirmed',
  completed: 'Completed',
  cancelled: 'Cancelled',
  no_show:   'No Show',
}

export default function Badge({ status, className }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize',
        statusColor(status),
        className,
      )}
    >
      {LABELS[status] ?? status}
    </span>
  )
}
