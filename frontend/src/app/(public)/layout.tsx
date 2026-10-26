import Link from 'next/link'
import { CalendarDays } from 'lucide-react'
import type { ReactNode } from 'react'

export default function PublicLayout({ children }: { children: ReactNode }) {
  return (
    <div className="min-h-screen flex flex-col">
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="container-page flex h-14 items-center justify-between">
          <Link
            href="/"
            className="flex items-center gap-2 text-brand-700 font-bold text-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 rounded"
          >
            <CalendarDays className="h-5 w-5" aria-hidden="true" />
            SlotBook
          </Link>
          <nav className="flex items-center gap-4 text-sm">
            <Link href="/services" className="text-gray-600 hover:text-gray-900 transition-colors">
              Services
            </Link>
            <Link
              href="/book"
              className="rounded-lg bg-brand-600 px-4 py-1.5 text-white font-medium hover:bg-brand-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
            >
              Book now
            </Link>
          </nav>
        </div>
      </header>

      <main className="flex-1">{children}</main>

      <footer className="border-t border-gray-200 bg-white py-6 text-center text-xs text-gray-400">
        &copy; {new Date().getFullYear()} SlotBook. All rights reserved.
      </footer>
    </div>
  )
}
