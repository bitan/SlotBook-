'use client'

import { useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { adminApi, getApiErrorMessage } from '@/lib/api'
import Input from '@/components/ui/Input'
import Button from '@/components/ui/Button'
import ErrorMessage from '@/components/ui/ErrorMessage'
import type { AppSettings } from '@/types'

export default function SettingsPage() {
  const queryClient = useQueryClient()

  const { data, isLoading, error } = useQuery({
    queryKey: ['admin', 'settings'],
    queryFn:  adminApi.getSettings,
  })

  const { register, handleSubmit, reset, formState: { isDirty } } = useForm<AppSettings>()

  useEffect(() => {
    if (data) reset(data)
  }, [data, reset])

  const { mutate: save, isPending, isSuccess } = useMutation({
    mutationFn: (values: AppSettings) => {
      const entries = Object.entries(values).map(([key, value]) => ({
        key,
        value: String(value ?? ''),
      }))
      return adminApi.updateSettings(entries)
    },
    onSuccess: (updated) => {
      queryClient.setQueryData(['admin', 'settings'], updated)
      reset(updated)
    },
  })

  if (isLoading) return <p className="text-sm text-gray-500">Loading settings…</p>
  if (error)     return <ErrorMessage message={getApiErrorMessage(error)} />

  return (
    <div className="space-y-6 max-w-2xl">
      <h1 className="text-2xl font-bold text-gray-900">Settings</h1>

      <form onSubmit={handleSubmit((v) => save(v))} className="space-y-6">
        <Section title="Business">
          <Input label="Business name" required {...register('business_name')} />
          <Input label="Phone number" {...register('business_phone')} />
          <Input label="Email address" type="email" {...register('business_email')} />
          <Input label="Address" {...register('business_address')} />
          <Input
            label="Timezone"
            hint="e.g. Africa/Addis_Ababa"
            {...register('business_timezone')}
          />
        </Section>

        <Section title="Booking rules">
          <div className="flex items-center gap-3">
            <input
              type="checkbox"
              id="auto_confirm"
              className="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
              {...register('auto_confirm')}
            />
            <label htmlFor="auto_confirm" className="text-sm text-gray-700">
              Auto-confirm new online bookings
            </label>
          </div>
          <Input
            label="Cancel/reschedule window (hours before)"
            type="number"
            min={0}
            {...register('cancel_window_hours', { valueAsNumber: true })}
          />
          <Input
            label="Minimum booking notice (minutes)"
            type="number"
            min={0}
            {...register('min_notice_minutes', { valueAsNumber: true })}
          />
        </Section>

        <Section title="Notifications">
          <Input
            label="Telegram owner chat ID"
            hint="Get this from @userinfobot on Telegram"
            {...register('telegram_owner_chat_id')}
          />
        </Section>

        <div className="flex items-center gap-3">
          <Button type="submit" loading={isPending} disabled={!isDirty}>
            Save changes
          </Button>
          {isSuccess && (
            <span className="text-sm text-green-600 font-medium">Saved ✓</span>
          )}
        </div>
      </form>
    </div>
  )
}

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
      <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">{title}</h2>
      {children}
    </div>
  )
}
