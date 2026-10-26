// ─────────────────────────────────────────────────────────────────────────────
// API response shapes — kept in sync with the Laravel API
// ─────────────────────────────────────────────────────────────────────────────

export type BookingStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'no_show'
export type BookingSource = 'online' | 'manual'
export type UserRole      = 'admin' | 'staff'

// ── Public types ──────────────────────────────────────────────────────────────

export interface Service {
  id:           number
  name:         string
  description:  string | null
  duration_min: number
  buffer_min:   number
  price:        number      // in ETB cents, e.g. 15000 = 150.00 ETB
}

export interface StaffMember {
  id:           number
  display_name: string
  avatar_url:   string | null
}

export interface Slot {
  starts_at: string   // ISO-8601 UTC
  staff_id:  number
}

export interface BookingConfirmation {
  reference:  string
  status:     BookingStatus
  starts_at:  string
  ends_at:    string
}

export interface ManagedBooking {
  id:        number
  reference: string
  status:    BookingStatus
  starts_at: string          // ISO-8601 in local timezone
  ends_at:   string
  service:   { id: number; name: string }
  staff:     { id: number; name: string }
  customer:  { name: string; phone: string }
}

// ── Admin types ───────────────────────────────────────────────────────────────

export interface AdminUser {
  id:   number
  name: string
  role: UserRole
}

export interface AdminBooking {
  id:        number
  reference: string
  status:    BookingStatus
  source:    BookingSource
  starts_at: string
  ends_at:   string
  service:   { id: number; name: string }
  staff:     { id: number; name: string }
  customer:  { id: number; name: string; phone: string; email: string | null }
  note:      string | null
}

export interface AdminService extends Service {
  is_active:  boolean
  deleted_at: string | null
}

export interface WorkingHoursRow {
  id?:        number
  weekday:    number   // 0 = Sunday … 6 = Saturday
  start_time: string   // HH:mm:ss
  end_time:   string   // HH:mm:ss  ('00:00:00' = midnight)
}

export interface StaffDetail extends StaffMember {
  user_id:       number
  is_active:     boolean
  services:      Service[]
  working_hours: WorkingHoursRow[]
}

export interface Customer {
  id:    number
  name:  string
  phone: string
  email: string | null
  notes: string | null
}

export interface MonthlyReport {
  month:             string
  total:             number
  by_status:         Record<BookingStatus, number>
  by_service:        { name: string; count: number; revenue: number }[]
  by_staff:          { name: string; count: number }[]
  estimated_revenue: number
}

export interface AppSettings {
  business_name:        string
  business_phone:       string | null
  business_email:       string | null
  business_address:     string | null
  business_timezone:    string
  auto_confirm:         boolean
  cancel_window_hours:  number
  min_notice_minutes:   number
  telegram_owner_chat_id: string | null
  google_maps_url:      string | null
}

// ── API pagination wrapper ────────────────────────────────────────────────────

export interface PaginatedResponse<T> {
  data:          T[]
  current_page:  number
  last_page:     number
  per_page:      number
  total:         number
}

// ── API error shape ───────────────────────────────────────────────────────────

export interface ApiError {
  message: string
  code:    string
  errors?: Record<string, string[]>
}
