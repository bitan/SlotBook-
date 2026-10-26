import { cn } from '@/lib/utils'
import { Check } from 'lucide-react'

interface Step {
  label: string
  index: number
}

const STEPS: Step[] = [
  { index: 1, label: 'Service'  },
  { index: 2, label: 'Schedule' },
  { index: 3, label: 'Details'  },
  { index: 4, label: 'Confirm'  },
]

interface BookingStepsProps {
  currentStep: number
}

export default function BookingSteps({ currentStep }: BookingStepsProps) {
  return (
    <nav aria-label="Booking progress" className="mb-8">
      <ol className="flex items-center">
        {STEPS.map((step, i) => {
          const done    = currentStep > step.index
          const current = currentStep === step.index
          const last    = i === STEPS.length - 1

          return (
            <li key={step.index} className={cn('flex items-center', !last && 'flex-1')}>
              <div className="flex flex-col items-center">
                <div
                  className={cn(
                    'flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-semibold transition-colors',
                    done
                      ? 'border-brand-600 bg-brand-600 text-white'
                      : current
                      ? 'border-brand-600 bg-white text-brand-600'
                      : 'border-gray-300 bg-white text-gray-400',
                  )}
                  aria-current={current ? 'step' : undefined}
                >
                  {done ? <Check className="h-4 w-4" aria-hidden="true" /> : step.index}
                </div>
                <span
                  className={cn(
                    'mt-1 hidden text-xs font-medium sm:block',
                    current ? 'text-brand-700' : done ? 'text-gray-600' : 'text-gray-400',
                  )}
                >
                  {step.label}
                </span>
              </div>

              {!last && (
                <div
                  className={cn(
                    'mx-2 h-0.5 flex-1 transition-colors',
                    done ? 'bg-brand-600' : 'bg-gray-200',
                  )}
                  aria-hidden="true"
                />
              )}
            </li>
          )
        })}
      </ol>
    </nav>
  )
}
