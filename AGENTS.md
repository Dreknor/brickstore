# AGENTS.md – BrickStore Coding Agent Guide

## Project Overview
**BrickStore** is a **multi-tenant Laravel 12 / PHP 8.2** application for managing BrickLink stores. Each `User` owns exactly one `Store` (1:1). All business logic is scoped per store via `store_id`.

## Architecture

### Multi-Tenancy Pattern
Every resource (orders, invoices, inventory) belongs to a `Store`, not directly to a `User`. Policies enforce ownership via `$order->store->user_id === $user->id`. Always scope queries with `store_id` from `auth()->user()->store`.

### Request Flow
1. `auth` middleware – user must be logged in  
2. `store.setup` middleware (`EnsureStoreIsSetup`) – redirects to `/store/setup` if `store->is_setup_complete` is false  
3. `admin` middleware (`EnsureUserIsAdmin`) – protects `/admin/*` routes  

Routes requiring a configured store are grouped under `middleware(['auth', 'store.setup'])` in `routes/web.php`.

### Service Layer
Business logic lives in `app/Services/`. Controllers are thin – they resolve services via `app(ServiceClass::class)` or constructor injection:
- `BrickLink/BrickLinkService` – all BrickLink API calls (OAuth 1.0a, hand-rolled in `generateOAuthHeader`)
- `InvoiceService` – invoice number generation (DB-locked counter), PDF via DomPDF (`Pdf::loadView('invoices.pdf', ...)`)
- `NextcloudService` – WebDAV uploads for invoice PDFs
- `ActivityLogger` – static facade-style audit logging: `ActivityLogger::info('event', 'description', $model)`
- `BrickLink/ImageCacheService` – caches external BrickLink image URLs locally under `storage/app/private/`

### Queued Jobs (async via `php artisan queue:work`)
- `UploadInvoiceToNextcloudJob` – dispatched automatically after PDF save; 3 retries, 60s backoff
- `SyncBrickLinkOrdersJob`, `SyncBrickLinkInventoryJob`, `SyncInventoryItemToBrickLinkJob`, `CacheInventoryImagesJob`

### Encrypted Model Fields
`Store` uses Laravel `encrypted` cast for all credentials: `bl_consumer_key`, `bl_consumer_secret`, `bl_token`, `bl_token_secret`, `smtp_password`, `nextcloud_password`. Never read/write these raw.

## Key Conventions

### BrickLink API Quirks
- Always returns HTTP 200; real status is in `$response['meta']['code']`
- Order items API wraps results in a nested array: `[[item1, item2]]` – unwrap the first level (see `fetchOrderItems`)
- No native `/messages` endpoint – messages are stored in the `remarks` field with `--- timestamp ---` separators
- OAuth 1.0a is implemented from scratch in `BrickLinkService::generateOAuthHeader`; do **not** replace with a library without testing IP-whitelisting behaviour

### Invoice Numbering
Generated with a `DB::transaction` + `lockForUpdate` on the store row to prevent duplicates. Format supports placeholders `{year}`, `{month}`, `{number}` (default: `RE-{year}-{number}`). One invoice per order (unique constraint on `invoices.order_id`).

### German Legal Compliance
`Store::is_small_business` (§19 UStG Kleinunternehmerregelung) forces `tax_rate = 0` and `tax_amount = 0` on invoices. Always check this flag before displaying/calculating tax.

## Developer Workflows

```bash
# Run tests (Pest v3, RefreshDatabase on all Feature tests)
php artisan test

# Frontend dev (Tailwind v4 + Alpine.js v3 via Vite)
npm run dev

# Build frontend assets
npm run build

# Start queue worker (required for Nextcloud uploads & BrickLink syncs)
php artisan queue:work

# Static analysis (Larastan level 7)
vendor/bin/phpstan analyse

# Code style
vendor/bin/pint

# Artisan commands for manual ops
php artisan nextcloud:test-connection
php artisan bricklink:sync-orders
php artisan inventory:sync
php artisan inventory:cache-images
```

## Testing Patterns
- Framework: **Pest v3**, feature tests use `RefreshDatabase` (configured in `tests/Pest.php`)
- Pattern: `beforeEach` creates `User::factory()` + `Store::factory(['user_id' => $this->user->id])`
- Services are resolved via `app(ServiceClass::class)`, not `new`
- Feature tests cover invoice logic, order sync, Nextcloud upload, and BrickLink credential errors

## Key File Map
| Area | Path |
|---|---|
| Route definitions | `routes/web.php` |
| Multi-tenant middleware | `app/Http/Middleware/EnsureStoreIsSetup.php` |
| BrickLink OAuth + API | `app/Services/BrickLink/BrickLinkService.php` |
| Invoice generation | `app/Services/InvoiceService.php` |
| Audit logging | `app/Services/ActivityLogger.php` |
| PDF template | `resources/views/invoices/pdf.blade.php` |
| Async jobs | `app/Jobs/` |
| Artisan commands | `app/Console/Commands/` |
| Migrations | `database/migrations/` |

