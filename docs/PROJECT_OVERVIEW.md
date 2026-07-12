# FinTech Project — Overview, Packages, How It Works, Client & Interview Notes

## Executive Summary
This Laravel 11 application provides an admin dashboard (Filament) for managing loan applications and CIBIL reports, an EMI calculator, and related utilities. The admin UI is built with Filament and Livewire. Key recent work includes: seeding dummy data, enabling search & filters for Filament resources, fixing Livewire view issues, theming (dark mode) for EMI pages, caching dashboard widgets, adding relationships and eager-loading to avoid N+1 queries, adding test/admin users, and improving logout behavior.

---

**Primary Features Implemented / Fixed**
- Seeders and factories: 20 `LoanApplication` and 20 `CibilReport` records; added 10 admin users (admin1..admin10@gmail.com, password `admin@123`) for development/demo.
- Filament resources: `LoanResource` and `CibilResource` — global table search enabled, column-level `->searchable()` flags, and filters (loan_type, status, created_at range).
- Dashboard widgets: optimized by caching aggregate stats and eager-loading related models to reduce DB load.
- EMI calculator page: fixed Livewire multi-root issue, added dark-mode styling and correct Filament route links.
- Relations: added `cibilReport()` on `LoanApplication` (belongsTo via `pan_number`) to support `cibilReport.cibil_score` in tables/widgets.
- Logout: added a GET fallback to gracefully invalidate session and redirect to login (avoids 419 on some client flows).

---

## Key Packages (from composer.json) and Purpose
- `laravel/framework` (v11): The PHP framework.
- `filament/filament`: Admin UI scaffolding (resources, widgets, pages, tables).
- `bezhansalleh/filament-shield`: Role/permission management integration for Filament.
- `filament/notifications`: In-app notification support in Filament.
- `spatie/laravel-medialibrary`: File/media handling for uploads (PAN, Aadhaar, docs).
- `intervention/image`: Image manipulation (resizing/processing uploaded images).
- `thiagoalessio/tesseract_ocr`: Tesseract OCR wrapper used in some OCR processing flows (loan docs).
- `laravel/passport` & `laravel/sanctum`: API authentication (if API endpoints used).
- `guzzlehttp/guzzle`: HTTP client for external API calls.
- `flowframe/laravel-trend`: Small utility for trends/charts (optional analytics tooling).
- Dev tools: `fakerphp/faker`, `phpunit/phpunit`, `nunomaduro/collision`, `laravel/pint` for testing/formatting.

---

## Architecture & Where To Look
- App: `app/` — models, Filament resources, widgets, pages, providers.
  - Filament resources: `app/Filament/Resources/*` (look for resource definitions, table columns, filters, navigation badge methods)
  - Widgets: `app/Filament/Widgets/*` (dashboard stat widgets, charts, recent lists)
  - Models: `app/Models/*` (relationships and casts; e.g., `LoanApplication`, `CibilReport`)
- Database: `database/factories/` and `database/seeders/DatabaseSeeder.php` (dummy data generation)
- Views: Filament uses Livewire components and Blade views in `resources/views/filament/...` (EMI calculator page)
- Routes: Filament mounts admin routes under `/admin/*`; additional web fallbacks live in `routes/web.php`.

---

## How It Works — High Level Flow
1. Admin logs into Filament (`/admin/login`). Filament routes provide resources to view and manage loans and CIBIL reports.
2. `LoanResource` and `CibilResource` expose tables with search and filters; single-record actions are available (view/edit/approve/reject).
3. Dashboard widgets are small Livewire components that run aggregated queries. To avoid repeated heavy queries, the aggregated stats are cached for 5 minutes and recent lists eager-load relations.
4. EMI calculator is a Filament page backed by a Livewire class that computes EMI and amortization schedule; theming uses Tailwind dark variants and the page is wrapped correctly to satisfy Livewire's single-root constraint.
5. Seeders create demo data and admin users. Use seeded admin credentials to demo the app quickly.

---

## Developer Notes — Recent Code Changes
- `database/seeders/DatabaseSeeder.php`: added idempotent creation for `test@example.com` and `admin1..admin10@gmail.com` with password `admin@123`; seeded 20 loans and 20 CIBILs via factories.
- `app/Models/LoanApplication.php`: added `cibilReport()` relationship using `belongsTo(CibilReport::class, 'pan_number', 'pan_number')`.
- `app/Filament/Resources/CibilResource.php`: enabled table-level search and per-column `->searchable()`; added a global filter input for fallback searches.
- `app/Filament/Resources/LoanResource.php`: enabled `->searchable()` on table; marked `loan_type` searchable; added filters for loan type, status and a date-range filter (select+DatePicker form filter) implemented with `Tables\Filters\Filter`.
- `resources/views/filament/.../emi-calculator.blade.php`: fixed Livewire single-root requirement and dark mode classes; replaced hard-coded URLs with Filament route names.
- `app/Filament/Widgets/*`: added caching to `LoanApplicationStats` and `CibilOverviewStats`; eager-loaded `cibilReport` in `RecentLoanApplications` to avoid N+1.
- `routes/web.php`: added a GET fallback route for `admin/logout` that logs out the user and regenerates the session token to prevent 419 pages.

---

## How to Demo to a Client (Script)
1. Login: Start at `/admin/login`. Use `admin1@gmail.com / admin@123`.
2. Dashboard: Show the stats widgets — explain caching (speeds up dashboard) and that data is coming from aggregated queries over CIBIL and loan records.
3. Recent Applications: Open the recent loans table; demonstrate approve/reject actions and show the CIBIL score badge (explain relation to CIBIL reports).
4. Search & Filters: Go to `Loans` resource; type a name or loan type in the global search; open the filters panel and filter by `loan_type` and date range.
5. EMI Calculator: Open EMI page, toggle dark mode, run a sample calculation and show amortization schedule.
6. Logout: Click logout to confirm you are redirected to the login page (the fallback route avoids 419 pages).

Mention: "Data shown is seeded demo data; production will connect to live database and external services (if configured)."

---

## Interview Talking Points (Concise, Bullet-Style)
- Architecture: Laravel 11 backend, Filament admin + Livewire for reactive UI, Tailwind for styling.
- Data handling: Eloquent models with explicit relationships and casts; factories + seeders for reproducible demo data.
- Performance: Avoided N+1 via `with()` eager-loading, cached expensive aggregates with `cache()->remember()`, and enabled route/config caching.
- Security: Filament + Filament-Shield for role-based access; logout fixes to invalidate session and regenerate CSRF token.
- Testing & Dev ergonomics: Seeders for test accounts, `phpunit` available for automated tests, `pint` for code style.
- Troubleshooting: Explained Livewire one-root requirement and how we fixed view issues; added robust filters instead of unsupported DateFilter.
- Extensibility: Media library for document uploads, OCR integration for document parsing, and Passport/Sanctum for future API needs.

---

## Quick Commands (Dev & Test)
- Seed database (applies `DatabaseSeeder`):

```bash
php artisan db:seed
```

- Clear and cache config/routes/views:

```bash
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan optimize
```

- Run tests:

```bash
vendor/bin/phpunit
```

---

## Notes & Next Improvements (Suggestions)
- Cache dashboard widgets per-user or use fragment caching to further speed repeated views.
- Add more selective indexes on `loan_type`, `pan_number`, and `created_at` if queries remain slow with production-sized datasets.
- Add unit and feature tests for Filament resources (actions, filters) and for EMI calculation correctness.
- Secure seeded accounts for production (never keep default passwords in production).
- Add background jobs to process OCR and media tasks asynchronously.

---

## Contact / Handoff
If you want, I can:
- Produce a short PDF handout from this markdown for the client.
- Add unit tests for the most critical flows (EMI calculations, resource filters, logout flow).
- Run a small profiling session (DB query log) to find any remaining hotspots on your local environment.


---

*Document generated from recent code changes and repo inspection.*
