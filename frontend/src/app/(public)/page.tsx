import Link from 'next/link'
import { CalendarDays, Clock, CheckCircle } from 'lucide-react'

export default function HomePage() {
  return (
    <>
      {/* Hero */}
      <section className="bg-gradient-to-br from-brand-600 to-brand-800 text-white">
        <div className="container-page py-20 text-center">
          <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">
            Book your appointment
          </h1>
          <p className="mt-4 text-lg text-brand-100 max-w-xl mx-auto">
            Fast, easy online booking. Choose your service and your preferred time — confirmed instantly.
          </p>
          <Link
            href="/book"
            className="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3 text-brand-700 font-semibold text-lg shadow hover:bg-brand-50 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-brand-700"
          >
            <CalendarDays className="h-5 w-5" aria-hidden="true" />
            Book now — it&apos;s free
          </Link>
        </div>
      </section>

      {/* Why us */}
      <section className="container-page py-16">
        <div className="grid gap-8 sm:grid-cols-3">
          {[
            {
              icon:  <CalendarDays className="h-7 w-7 text-brand-600" />,
              title: 'Book in under a minute',
              body:  'Pick a service, choose your time, fill in your name. Done.',
            },
            {
              icon:  <Clock className="h-7 w-7 text-brand-600" />,
              title: 'Real-time availability',
              body:  'Slots update live. No more back-and-forth phone calls.',
            },
            {
              icon:  <CheckCircle className="h-7 w-7 text-brand-600" />,
              title: 'Instant confirmation',
              body:  'Get a confirmation email with a link to view or change your booking.',
            },
          ].map((item) => (
            <div key={item.title} className="flex flex-col items-start gap-3 rounded-xl bg-white border border-gray-200 p-6">
              {item.icon}
              <h2 className="font-semibold text-gray-900">{item.title}</h2>
              <p className="text-sm text-gray-500">{item.body}</p>
            </div>
          ))}
        </div>
      </section>
    </>
  )
}
