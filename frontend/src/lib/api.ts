/**
 * Typed API client for the SlotBook API.
 *
 * All requests go through this module. The base URL is read from
 * NEXT_PUBLIC_API_URL so swapping backends requires only an env change.
 *
 * Authentication token is stored in localStorage and attached via the
 * Authorization header (Sanctum token-based auth for an SPA).
 */

import axios, { AxiosError } from 'axios'
import type {
  AdminBooking,
  AdminService,
  AdminUser,
  AppSettings,
  BookingConfirmation,
  Customer,
  ManagedBooking,
  MonthlyReport,
  PaginatedResponse,
  Service,
  Slot,
  StaffDetail,
  StaffMember,
  WorkingHoursRow,
} from '@/types'

const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api/v1'

export const http = axios.create({
  baseURL:         BASE_URL,
  withCredentials: false,
  headers: {
    'Content-Type': 'application/json',
    Accept:         'application/json',
  },
})

// Attach stored auth token on every request
http.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem('sb_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
  }
  return config
})

// ─────────────────────────────────────────────────────────────────────────────
// Public API
// ─────────────────────────────────────────────────────────────────────────────

export const publicApi = {
  getServices: () =>
    http.get<{ data: Service[] }>('/services').then((r) => r.data.data),

  getStaff: (serviceId: number) =>
    http.get<{ data: StaffMember[] }>('/staff', { params: { service_id: serviceId } })
      .then((r) => r.data.data),

  getAvailableDates: (serviceId: number, staffId?: number) =>
    http.get<{ data: string[] }>('/availability/dates', {
      params: { service_id: serviceId, staff_id: staffId ?? undefined },
    }).then((r) => r.data.data),

  getSlots: (serviceId: number, staffId: number | undefined, date: string) =>
    http.get<{ data: Slot[] }>('/availability/slots', {
      params: { service_id: serviceId, staff_id: staffId ?? undefined, date },
    }).then((r) => r.data.data),

  createBooking: (payload: {
    service_id:     number
    staff_id:       number
    starts_at:      string
    customer_name:  string
    customer_phone: string
    customer_email?: string
    note?:          string
  }) =>
    http.post<{ data: BookingConfirmation }>('/bookings', payload)
      .then((r) => r.data.data),

  getManagedBooking: (token: string) =>
    http.get<{ data: ManagedBooking }>(`/bookings/manage/${token}`)
      .then((r) => r.data.data),

  cancelBooking: (token: string) =>
    http.post<{ data: ManagedBooking }>(`/bookings/manage/${token}/cancel`)
      .then((r) => r.data.data),

  rescheduleBooking: (token: string, staffId: number, startsAt: string) =>
    http.post<{ data: ManagedBooking }>(`/bookings/manage/${token}/reschedule`, {
      staff_id:  staffId,
      starts_at: startsAt,
    }).then((r) => r.data.data),
}

// ─────────────────────────────────────────────────────────────────────────────
// Auth API
// ─────────────────────────────────────────────────────────────────────────────

export const authApi = {
  login: (email: string, password: string) =>
    http.post<{ data: { token: string; user: AdminUser } }>('/auth/login', { email, password })
      .then((r) => r.data.data),

  logout: () =>
    http.post('/auth/logout'),

  me: () =>
    http.get<{ data: AdminUser }>('/auth/me').then((r) => r.data.data),

  forgotPassword: (email: string) =>
    http.post('/auth/forgot-password', { email }),

  resetPassword: (token: string, email: string, password: string, passwordConfirmation: string) =>
    http.post('/auth/reset-password', {
      token,
      email,
      password,
      password_confirmation: passwordConfirmation,
    }),
}

// ─────────────────────────────────────────────────────────────────────────────
// Admin API
// ─────────────────────────────────────────────────────────────────────────────

export const adminApi = {
  // Bookings
  getBookings: (params?: {
    date?:     string
    staff_id?: number
    status?:   string
    page?:     number
    per_page?: number
  }) =>
    http.get<PaginatedResponse<AdminBooking>>('/admin/bookings', { params })
      .then((r) => r.data),

  createBooking: (payload: object) =>
    http.post<{ data: AdminBooking }>('/admin/bookings', payload)
      .then((r) => r.data.data),

  updateBooking: (id: number, payload: object) =>
    http.patch<{ data: AdminBooking }>(`/admin/bookings/${id}`, payload)
      .then((r) => r.data.data),

  // Services
  getServices: () =>
    http.get<{ data: AdminService[] }>('/admin/services').then((r) => r.data.data),

  createService: (payload: object) =>
    http.post<{ data: AdminService }>('/admin/services', payload).then((r) => r.data.data),

  updateService: (id: number, payload: object) =>
    http.patch<{ data: AdminService }>(`/admin/services/${id}`, payload).then((r) => r.data.data),

  deleteService: (id: number) =>
    http.delete(`/admin/services/${id}`),

  // Staff
  getStaff: () =>
    http.get<{ data: StaffDetail[] }>('/admin/staff').then((r) => r.data.data),

  createStaff: (payload: object) =>
    http.post<{ data: StaffDetail }>('/admin/staff', payload).then((r) => r.data.data),

  updateStaff: (id: number, payload: object) =>
    http.patch<{ data: StaffDetail }>(`/admin/staff/${id}`, payload).then((r) => r.data.data),

  getStaffHours: (staffId: number) =>
    http.get<{ data: WorkingHoursRow[] }>(`/admin/staff/${staffId}/hours`).then((r) => r.data.data),

  setStaffHours: (staffId: number, hours: WorkingHoursRow[]) =>
    http.put<{ data: WorkingHoursRow[] }>(`/admin/staff/${staffId}/hours`, { hours })
      .then((r) => r.data.data),

  addTimeOff: (staffId: number, payload: object) =>
    http.post(`/admin/staff/${staffId}/time-off`, payload).then((r) => r.data),

  removeTimeOff: (staffId: number, timeOffId: number) =>
    http.delete(`/admin/staff/${staffId}/time-off/${timeOffId}`),

  // Customers
  getCustomers: (params?: { search?: string; page?: number }) =>
    http.get<PaginatedResponse<Customer>>('/admin/customers', { params }).then((r) => r.data),

  updateCustomer: (id: number, payload: object) =>
    http.patch<{ data: Customer }>(`/admin/customers/${id}`, payload).then((r) => r.data.data),

  deleteCustomer: (id: number) =>
    http.delete(`/admin/customers/${id}`),

  // Reports
  getMonthlyReport: (month: string) =>
    http.get<{ data: MonthlyReport }>('/admin/reports/monthly', { params: { month } })
      .then((r) => r.data.data),

  // Settings
  getSettings: () =>
    http.get<{ data: AppSettings }>('/admin/settings').then((r) => r.data.data),

  updateSettings: (settings: { key: string; value: string }[]) =>
    http.put<{ data: AppSettings }>('/admin/settings', { settings }).then((r) => r.data.data),
}

// ─────────────────────────────────────────────────────────────────────────────
// Error helper
// ─────────────────────────────────────────────────────────────────────────────

export function getApiErrorMessage(error: unknown): string {
  if (error instanceof AxiosError && error.response?.data?.message) {
    return error.response.data.message as string
  }
  if (error instanceof Error) return error.message
  return 'An unexpected error occurred.'
}

export function isSlotConflict(error: unknown): boolean {
  return (
    error instanceof AxiosError &&
    error.response?.status === 409 &&
    error.response?.data?.code === 'SLOT_CONFLICT'
  )
}
