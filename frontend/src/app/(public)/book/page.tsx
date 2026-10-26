'use client'

import { useState, Suspense } from 'react'
import { useSearchParams, useRouter } from 'next/navigation'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { publicApi, getApiErrorMessage, isSlotConflict } from '@/lib/api'
import { formatLocal, formatDuration, formatPrice } from '@/lib/utils'
import BookingSteps from '@/components/booking/BookingSteps'
import ServiceCard from '@/components/booking/ServiceCard'
import StaffPicker from '@/components/booking/StaffPicker'
import DatePicker from '@/components/booking/DatePicker'
import SlotPicker from '@/components/booking/SlotPicker'
import Button from '@/components/ui/Button'
import Input from '@/components/ui/Input'
import { SkeletonCard } from '@/components/ui/Skeleton'
import ErrorMessage from '@/components/ui/ErrorMessage'
import type { Service, Slot } from '@/types'

// ─── Step 3 form schema ───────────────────────────────────────────────────────

const detailsSchema = z.object({
  customer_name:  z.string().min(2, 'Please enter your name'),
  customer_phone: z.string().min(7, 'Please enter a valid phone number'),
  customer_email: z.string().email('Invalid email').optional().or(z.literal('')),
  note:           z.string().max(500).optional(),
})

type DetailsForm = z.infer<typeof detailsSchema>

// ─── Main component ───────────────────────────────────────────────────────────

function BookPage() {
  const router       = useRouter()
  const searchParams = useSearchParams()
  const preselect    = searchParams.get('service') ? Number(searchParams.get('service')) : null

  const [step,            setStep]            = useState(1)
  const [selectedService, setSelectedService] = useState<Service | null>(null)
  const [selectedStaffId, setSelectedStaffId] = useState<number | null>(null)  // null = any
  const [selectedDate,    setSelectedDate]    = useState<string | null>(null)
  const [selectedSlot,    setSelectedSlot]    = useState<Slot | null>(null)
  const [conflictError,   setConflictError]   = useState(false)

  // ── Step 1: Services ────────────────────────────────────────────────────────

  const { data: services, isLoading: loadingServices, error: servicesError, refetch: refetchServices } = useQuery({
    queryKey: ['services'],
    queryFn:  publicApi.getServices,
  })

  // Auto-select from URL param
  if (services && preselect && !selectedService) {
    const found = services.find((s) => s.id === preselect)
    if (found) {
      setSelectedService(found)
      setStep(2)
    }
  }

  // ── Step 2: Staff + Date + Slot ─────────────────────────────────────────────

  const { data: staff, isLoading: loadingStaff } = useQuery({
    queryKey: ['staff', selectedService?.id],
    queryFn:  () => publicApi.getStaff(selectedService!.id),
    enabled:  !!selectedService,
  })

  const { data: availableDates, isLoading: loadingDates } = useQuery({
    queryKey: ['availability', 'dates', selectedService?.id, selectedStaffId],
    queryFn:  () => publicApi.getAvailableDates(selectedService!.id, selectedStaffId ?? undefined),
    enabled:  !!selectedService,
  })

  const { data: slots, isLoading: loadingSlots } = useQuery({
    queryKey: ['availability', 'slots', selectedService?.id, selectedStaffId, selectedDate],
    queryFn:  () => publicApi.getSlots(selectedService!.id, selectedStaffId ?? undefined, selectedDate!),
    enabled:  !!selectedService && !!selectedDate,
  })

  // ── Step 3: Details form ────────────────────────────────────────────────────

  const { register, handleSubmit, formState: { errors }, getValues } = useForm<DetailsForm>({
    resolver: zodResolver(detailsSchema),
  })

  // ── Step 4 + Submit ─────────────────────────────────────────────────────────

  const { mutate: createBooking, isPending } = useMutation({
    mutationFn: (formData: DetailsForm) => {
      if (!selectedService || !selectedSlot) throw new Error('Incomplete selection')
      return publicApi.createBooking({
        service_id:     selectedService.id,
        staff_id:       selectedSlot.staff_id,
        starts_at:      selectedSlot.starts_at,
        customer_name:  formData.customer_name,
        customer_phone: formData.customer_phone,
        customer_email: formData.customer_email || undefined,
        note:           formData.note || undefined,
      })
    },
    onSuccess: (booking) => {
      router.push(`/book/confirmation?ref=${booking.reference}`)
    },
    onError: (error) => {
      if (isSlotConflict(error)) {
        setConflictError(true)
        // Go back to step 2 so user picks a new slot
        setSelectedSlot(null)
        setSelectedDate(null)
        setStep(2)
      }
    },
  })

  const onSubmit = (formData: DetailsForm) => {
    setConflictError(false)
    createBooking(formData)
  }

  // ─────────────────────────────────────────────────────────────────────────────

  return (
    <div className="container-page max-w-2xl py-10">
      <BookingSteps currentStep={step} />

      {/* ── STEP 1: Choose service ── */}
      {step === 1 && (
        <section aria-labelledby="step1-heading">
          <h2 id="step1-heading" className="mb-4 text-xl font-bold text-gray-900">
            Choose a service
          </h2>

          {loadingServices && (
            <div className="space-y-3">
              {[1, 2, 3].map((n) => <SkeletonCard key={n} />)}
            </div>
          )}

          {servicesError && (
            <ErrorMessage message={getApiErrorMessage(servicesError)} onRetry={refetchServices} />
          )}

          {services && (
            <div className="space-y-3">
              {services.map((service) => (
                <ServiceCard
                  key={service.id}
                  service={service}
                  selected={selectedService?.id === service.id}
                  onSelect={(s) => {
                    setSelectedService(s)
                    setSelectedStaffId(null)
                    setSelectedDate(null)
                    setSelectedSlot(null)
                  }}
                />
              ))}
            </div>
          )}

          <div className="mt-6 flex justify-end">
            <Button
              disabled={!selectedService}
              onClick={() => setStep(2)}
            >
              Next: Choose time
            </Button>
          </div>
        </section>
      )}

      {/* ── STEP 2: Staff, date, slot ── */}
      {step === 2 && selectedService && (
        <section aria-labelledby="step2-heading" className="space-y-6">
          <h2 id="step2-heading" className="text-xl font-bold text-gray-900">
            Choose your time
          </h2>

          {conflictError && (
            <ErrorMessage message="That slot was just taken. Please choose a new time." />
          )}

          {staff && (
            <StaffPicker
              staff={staff}
              selected={selectedStaffId}
              onSelect={(id) => {
                setSelectedStaffId(id)
                setSelectedDate(null)
                setSelectedSlot(null)
              }}
            />
          )}

          <DatePicker
            availableDates={availableDates ?? []}
            selected={selectedDate}
            onSelect={(d) => { setSelectedDate(d); setSelectedSlot(null) }}
            loading={loadingDates}
          />

          {selectedDate && (
            <SlotPicker
              slots={slots ?? []}
              selected={selectedSlot?.starts_at ?? null}
              onSelect={setSelectedSlot}
              loading={loadingSlots}
            />
          )}

          <div className="flex items-center justify-between">
            <Button variant="ghost" onClick={() => setStep(1)}>
              Back
            </Button>
            <Button disabled={!selectedSlot} onClick={() => setStep(3)}>
              Next: Your details
            </Button>
          </div>
        </section>
      )}

      {/* ── STEP 3: Customer details ── */}
      {step === 3 && (
        <section aria-labelledby="step3-heading">
          <h2 id="step3-heading" className="mb-4 text-xl font-bold text-gray-900">
            Your details
          </h2>

          <form onSubmit={handleSubmit(() => setStep(4))} className="space-y-4" noValidate>
            <Input
              label="Full name"
              required
              autoComplete="name"
              error={errors.customer_name?.message}
              {...register('customer_name')}
            />
            <Input
              label="Phone number"
              type="tel"
              required
              autoComplete="tel"
              hint="We'll only use this to confirm your booking."
              error={errors.customer_phone?.message}
              {...register('customer_phone')}
            />
            <Input
              label="Email address (optional)"
              type="email"
              autoComplete="email"
              hint="For a confirmation email with your booking link."
              error={errors.customer_email?.message}
              {...register('customer_email')}
            />
            <div className="space-y-1">
              <label htmlFor="note" className="block text-sm font-medium text-gray-700">
                Note for the team (optional)
              </label>
              <textarea
                id="note"
                rows={3}
                className="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                {...register('note')}
              />
            </div>

            <div className="flex items-center justify-between pt-2">
              <Button type="button" variant="ghost" onClick={() => setStep(2)}>
                Back
              </Button>
              <Button type="submit">
                Review booking
              </Button>
            </div>
          </form>
        </section>
      )}

      {/* ── STEP 4: Confirm ── */}
      {step === 4 && selectedService && selectedSlot && (
        <section aria-labelledby="step4-heading">
          <h2 id="step4-heading" className="mb-4 text-xl font-bold text-gray-900">
            Confirm your booking
          </h2>

          <div className="rounded-xl border border-gray-200 bg-white p-5 space-y-3 mb-6">
            <Row label="Service"  value={selectedService.name} />
            <Row label="Duration" value={formatDuration(selectedService.duration_min)} />
            <Row label="Price"    value={formatPrice(selectedService.price)} />
            <Row label="Date & time" value={formatLocal(selectedSlot.starts_at)} />
            <Row label="Name"     value={getValues('customer_name')} />
            <Row label="Phone"    value={getValues('customer_phone')} />
            {getValues('customer_email') && (
              <Row label="Email" value={getValues('customer_email')!} />
            )}
          </div>

          <div className="flex items-center justify-between">
            <Button variant="ghost" onClick={() => setStep(3)}>
              Back
            </Button>
            <Button
              onClick={handleSubmit(onSubmit)}
              loading={isPending}
              size="lg"
            >
              Confirm booking
            </Button>
          </div>
        </section>
      )}
    </div>
  )
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-start justify-between text-sm gap-4">
      <span className="text-gray-500 shrink-0">{label}</span>
      <span className="font-medium text-gray-900 text-right">{value}</span>
    </div>
  )
}

export default function BookPageWrapper() {
  return (
    <Suspense>
      <BookPage />
    </Suspense>
  )
}
