'use client'

import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useAuth } from '@/hooks/useAuth'
import { getApiErrorMessage } from '@/lib/api'
import Input from '@/components/ui/Input'
import Button from '@/components/ui/Button'
import { CalendarDays } from 'lucide-react'

const schema = z.object({
  email:    z.string().email('Invalid email'),
  password: z.string().min(1, 'Password is required'),
})

type LoginForm = z.infer<typeof schema>

export default function LoginPage() {
  const { login }           = useAuth()
  const [error, setError]   = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  const { register, handleSubmit, formState: { errors } } = useForm<LoginForm>({
    resolver: zodResolver(schema),
  })

  const onSubmit = async (data: LoginForm) => {
    setError(null)
    setLoading(true)
    try {
      await login(data.email, data.password)
    } catch (e) {
      setError(getApiErrorMessage(e))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
      <div className="w-full max-w-sm">
        <div className="text-center mb-8">
          <div className="flex justify-center mb-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white">
              <CalendarDays className="h-6 w-6" aria-hidden="true" />
            </div>
          </div>
          <h1 className="text-2xl font-bold text-gray-900">Sign in to SlotBook</h1>
          <p className="mt-1 text-sm text-gray-500">Admin &amp; staff portal</p>
        </div>

        <form
          onSubmit={handleSubmit(onSubmit)}
          className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4"
          noValidate
        >
          {error && (
            <div role="alert" className="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          )}

          <Input
            label="Email"
            type="email"
            autoComplete="email"
            required
            error={errors.email?.message}
            {...register('email')}
          />

          <Input
            label="Password"
            type="password"
            autoComplete="current-password"
            required
            error={errors.password?.message}
            {...register('password')}
          />

          <Button type="submit" className="w-full" size="lg" loading={loading}>
            Sign in
          </Button>
        </form>
      </div>
    </div>
  )
}
