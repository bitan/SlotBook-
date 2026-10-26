# SlotBook

Online appointment booking and management system for local service businesses.

## Structure

```
SlotBook/
├── api/          Laravel 11 REST API (PHP 8.3)
├── frontend/     Next.js 15 frontend (TypeScript + Tailwind)
└── docs/         SRS & SDD document
```

## Quick start (local)

### Prerequisites
- PHP 8.3 + Composer
- MySQL 8
- Node.js 20+

### API

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
# Edit .env with your DB credentials
php artisan migrate --seed
php artisan serve          # http://localhost:8000
php artisan queue:work     # In a second terminal
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env.local
# Set NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
npm run dev                # http://localhost:3000
```

Default admin login after seed:
- Email: `admin@slotbook.app`
- Password: set `ADMIN_PASSWORD` in `.env` before seeding (defaults to `change-me-now`)

---

## Deployment

### Frontend → Netlify

1. Push to GitHub
2. Connect repo in Netlify → set build command `npm run build`, publish dir `out`
3. Add env var `NEXT_PUBLIC_API_URL` pointing to your API

### API → Render (or any PHP host)

1. Create a Web Service on Render, connect your repo, set root to `api/`
2. Build command: `composer install --no-dev --optimize-autoloader`
3. Start command: `php artisan serve --host=0.0.0.0 --port=$PORT`
4. Add all env vars from `.env.example`
5. Add a Background Worker for the queue: `php artisan queue:work`
6. Add a Cron Job (daily): `php artisan schedule:run`

---

## Design decisions

### Midnight / UTC edge case
Working hours are stored as local `TIME` values. `AvailabilityService` converts them
to UTC **per calendar date**, so a window ending at `00:00:00` (midnight) is correctly
interpreted as the next calendar date at 00:00 local — never as midnight UTC on the same day.

### Settings type safety
All settings keys are defined in `App\Enums\SettingKey`. The DB column is an ENUM.
No raw strings are used anywhere in the codebase for settings access.

### Manage token lifecycle
The token emailed to a customer is **reusable until the booking starts or reaches a
terminal status** (cancelled, completed, no_show). Rescheduling does not invalidate
it — the same link works for multiple reschedules, which is the friendliest UX for
customers who change their mind.

### Double-booking prevention
`BookingService::create()` and `::reschedule()` use `SELECT ... FOR UPDATE` inside
a transaction to lock the staff row before the overlap check. This prevents two
concurrent requests from both passing the check and creating overlapping bookings.
