# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

SalePro / LIMS Stock Manager Pro with POS — a CodeCanyon inventory, POS, accounting and HRM app built on **Laravel 12 / PHP 8.3**, MySQL, server-rendered Blade + jQuery (Bootstrap 4, DataTables). There is no git repo and effectively no test suite. `README.md` is Laravel boilerplate.

## Commands

```bash
composer install
php artisan serve                 # or serve the project root via Apache; root .htaccess rewrites to public/
php artisan migrate               # core migrations: database/migrations
php artisan module:migrate Repair # nwidart module migrations: Modules/<Name>/Database/Migrations
php artisan optimize:clear
vendor/bin/phpunit                          # only example tests exist (sqlite :memory:)
vendor/bin/phpunit --filter UserTest        # single test
```

Docker (`Dockerfile`, `docker-compose.yml`, `docker/`): `docker compose up -d --build` → app on http://localhost:8000, MySQL on host port 3308 (db/user/pass `salepro`). Compose env vars override `.env` DB settings. Run artisan via `docker compose exec app php artisan ...`. SQL dumps placed in `docker/mysql/init/` (currently empty) are imported only when the `db_data` volume is empty (`docker compose down -v` to reset). Fresh DB without the vendor installer's dump: `migrate --force`, then `db:seed --class='Database\Seeders\Tenant\TenantDatabaseSeeder' --force` (languages/translations/theme), then `db:seed --class='Database\Seeders\LocalBootstrapSeeder' --force` (settings, roles 1/2/4/5, all permissions from `database/seeders/data/permissions.txt`, default warehouse/biller/walk-in customer, login `admin`/`admin123`). Vendor lives in a named volume; after changing `composer.lock` run `docker compose exec app composer install`.

No frontend build is needed: `package.json` references Laravel Mix but there is no root `webpack.mix.js`; JS/CSS are prebuilt static files under `public/` (`public/js`, `public/vendor`, …).

## Dangerous things to know

- **`reset:db` is scheduled twice daily** (`app/Console/Kernel.php`). It is the vendor's demo reset: it drops **every table** and reimports `salepropos.sql` (`app/Console/Commands/ResetDB.php`; that file isn't in this copy, so the job would leave the DB empty). Never run `schedule:run`/`schedule:work` or `php artisan reset:db` against a real database; remove the schedule entry for any real deployment.
- `routes/web.php` exposes unauthenticated `GET /migrate` (runs migrate + db:seed) and `GET /clear` (optimize:clear + cache forgets).
- `.env` holds real credentials — don't print it.
- **Never run `php artisan view:cache` or `php artisan optimize`.** `AppServiceProvider::boot()` returns early in the console, so the custom `@can` override isn't registered and views get compiled to Laravel's Gate check, which hits Spatie's missing `model_has_permissions` table (SQLSTATE 42S02 on every page). Fix with `php artisan view:clear` and let web requests compile views. To syntax-check Blade, load the page instead.

## Architecture

### Request pipeline and global state
- Old-style (pre-Laravel-11) wiring: `app/Http/Kernel.php`, `app/Console/Kernel.php`, `RouteServiceProvider` — not the `bootstrap/app.php` fluent config.
- Almost all back-office routes sit in `routes/web.php` inside `['common', 'auth', 'active']`. **`App\Http\Middleware\Common`** is the central bootstrapper: it loads `general_settings`, sets timezone, and `View::share`s / `config([...])`s globals used everywhere (`general_setting`, `currency`, `role`, `permission_list`, `role_has_permissions_list`, `categories_list`, alert counts, `config('currency')`, `config('decimal')`, `config('date_format')`, `config('staff_access')`, `config('without_stock')`, `config('addons')`, …). Blade views assume these exist.
- `app/Helpers/helpers.php` (autoloaded) provides `format_currency()` and `normalize_to_sql_datetime()` which read those runtime configs.

### Caching — must invalidate manually
Settings and lookups are cached for a year (`Cache::remember(..., 60*60*24*365)`) under fixed keys: `general_setting`, `pos_setting`, `currency`, `permissions`, `role_has_permissions`, `role_has_permissions_list{role_id}`, `user_role`, `category_list`, `product_list`, `warehouse_list`, `languages_list`, etc. (`translations_{locale}` uses `rememberForever`). When changing the underlying rows, call `cache()->forget(...)` (the `App\Traits\CacheForget` trait) as existing controllers do, or changes won't appear. Local `.env` uses `CACHE_DRIVER=array`.

### Permissions
Spatie permission tables are used, but users have a single `role_id` column (not Spatie's model_has_roles). Three enforcement styles coexist:
- Controllers: `Role::find(Auth::user()->role_id)->hasPermissionTo('sales-index')` (most common).
- Routes: custom `permission:<name>` middleware (`PermissionMiddleware`) and `hasPermanentDeletePermission`.
- Blade: `@can('perm')` is **overridden** in `AppServiceProvider` to check the cached `role_has_permissions_list` instead of Laravel gates.
Permission names follow `<resource>-index|view|add|edit|delete` plus `sidebar_*` entries. New permissions need a row in `permissions` and assignment in `role_has_permissions` (see `RoleController`), and should be added to `database/seeders/data/permissions.txt` so fresh installs get them.

Row-level scoping is separate: `App\Traits\StaffAccess::staffAccessCheck($query)` limits users with `role_id > 2` to their own records (`config('staff_access') == 'own'`, by `user_id`) or their warehouse (`'warehouse'`, by `warehouse_id`). Listing queries for new features should call it as the existing ones do.

### Translations
Locale comes from the `language` cookie or the default row in `languages`. Strings are stored in the **`translations` DB table** (seeded from `database/seeders/translations/*.php`) and injected into the translator in `AppServiceProvider`; there is no `lang/` directory. Use `__('db.Some Key')` keys, and add new keys to the translations seeders/table.

### Modules (nwidart/laravel-modules)
Add-ons live in `Modules/<Name>` (Config, Entities = models, Http, Resources/views, Routes, Database/Migrations). Present here: **Manufacturing** and **Repair**. `modules_statuses.json` and code references also mention Ecommerce, Woocommerce, Restaurant, Gym, Project, Middleware — those are sold separately and **not present**; code guards them with `file_exists(base_path('Modules/X'))` or `config('addons')` (`general_settings.modules`). `Kernel.php` aliases `customerauth`/`customerauth0` to missing module middleware — only a problem if used. Module views are referenced as `repair::...`.

### Multi-tenant SaaS code paths (inactive)
Code paths conditioned on `config('database.connections.saleprosaas_landlord')` (tenant lookup, `App\Models\landlord\*`, stancl/tenancy middleware, `TenantInfo` trait) belong to the SaaS edition. That connection, the landlord models and stancl/tenancy are not installed; `PRODUCT_MODE=CLIENT`. Leave these branches alone unless adding SaaS support.

### API
`routes/api.php` (Sanctum + `common` + `validate_mobile_token`, for the mobile app) imports `App\Http\Controllers\Api\*`, but **that directory does not exist** in this copy, so API routes will fail at dispatch and `php artisan route:list` may error.

### Domain layout
- Controllers in `app/Http/Controllers` are large and do most logic inline (e.g. `SaleController` is thousands of lines), heavily using `DB::table` alongside Eloquent models in `app/Models`. Listing pages use server-side DataTables via POST `*-data` endpoints.
- Stock is tracked per warehouse (`product_warehouse`), per batch (`product_batches`, with expiry), and per variant (`product_variants`); sales/purchases/transfers/returns/adjustments all update these tables — follow an existing flow (e.g. `SaleController@store`, `PurchaseController@store`) when touching quantities.
- Views: `resources/views/backend/<feature>/`, main layout `backend/layout/main.blade.php` (RTL variant `0main_rtl`).
- Pluggable integrations: payment gateways (`app/Services/PaymentService.php`, `app/Contracts/Payble`), SMS providers (`app/SMSProviders`, `app/Contracts/Sms`, `SmsService`), couriers (`app/Services/Courier`), receipt printers (ESC/POS, `PrinterService`), ZATCA e-invoicing (`config('is_zatca')`).
- Scheduled commands: `purchase:auto` (every 15 min) and `dsoalert:find` (daily).
