import { cn } from '@/lib/utils'
import type { StaffMember } from '@/types'
import { User } from 'lucide-react'

interface StaffPickerProps {
  staff:      StaffMember[]
  selected:   number | null   // null = "any available"
  onSelect:   (id: number | null) => void
}

export default function StaffPicker({ staff, selected, onSelect }: StaffPickerProps) {
  return (
    <div className="space-y-3">
      <h2 className="text-sm font-medium text-gray-700">Choose a team member</h2>

      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        {/* Any available option */}
        <button
          onClick={() => onSelect(null)}
          className={cn(
            'flex flex-col items-center gap-2 rounded-xl border-2 p-4 text-sm transition-all',
            'hover:border-brand-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
            selected === null
              ? 'border-brand-500 bg-brand-50'
              : 'border-gray-200 bg-white',
          )}
          aria-pressed={selected === null}
        >
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
            <User className="h-5 w-5 text-gray-500" aria-hidden="true" />
          </div>
          <span className="font-medium text-gray-700">Any available</span>
        </button>

        {staff.map((member) => (
          <button
            key={member.id}
            onClick={() => onSelect(member.id)}
            className={cn(
              'flex flex-col items-center gap-2 rounded-xl border-2 p-4 text-sm transition-all',
              'hover:border-brand-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
              selected === member.id
                ? 'border-brand-500 bg-brand-50'
                : 'border-gray-200 bg-white',
            )}
            aria-pressed={selected === member.id}
          >
            {member.avatar_url ? (
              <img
                src={member.avatar_url}
                alt=""
                className="h-10 w-10 rounded-full object-cover"
              />
            ) : (
              <div className="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-semibold">
                {member.display_name.charAt(0).toUpperCase()}
              </div>
            )}
            <span className="font-medium text-gray-700 text-center">{member.display_name}</span>
          </button>
        ))}
      </div>
    </div>
  )
}
