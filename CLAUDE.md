# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

SalePro / LIMS Stock Manager Pro with POS — a CodeCanyon inventory, POS, accounting and HRM app built on **Laravel 12 / PHP 8.3**, MySQL, server-rendered Blade + jQuery (Bootstrap 4, DataTables). Vendor version **6.1.5** (`VERSION` in `.env`), upgraded from 5.8.0 on branch `upgrade/6.1.5`; our own changes sit on top of the vendor code (see "Local customizations"). Effectively no test suite. `README.md` is Laravel boilerplate.

## Commands

```bash
composer install
php artisan serve                 # or serve the project root via Apache; root .htaccess rewrites to public/
php artisan migrate               # core migrations (database/migrations) and module migrations
php artisan translations:sync     # non-destructively adds missing keys from database/seeders/Tenant/translations
php artisan optimize:clear
vendor/bin/phpunit                          # only example tests exist (sqlite :memory:)
vendor/bin/phpunit --filter UserTest        # single test
```

Docker (`Dockerfile`, `docker-compose.yml`, `docker/`): `docker compose up -d --build` → app on http://localhost:8000, MySQL on host port 3308 (db/user/pass `salepro`). Compose env vars override `.env` DB settings. Run artisan via `docker compose exec app php artisan ...`. SQL dumps placed in `docker/mysql/init/` (currently empty) are imported only when the `db_data` volume is empty (`docker compose down -v` to reset). Fresh DB without the vendor installer's dump: `migrate --force`, then `db:seed --class='Database\Seeders\Tenant\TenantDatabaseSeeder' --force` (languages/translations/theme/permissions), then `db:seed --class='Database\Seeders\LocalBootstrapSeeder' --force` (settings, roles 1/2/4/5, all permissions from `database/seeders/data/permissions.txt`, default warehouse/biller/walk-in customer, login `admin`/`admin123`; written for 5.8.0 and not yet re-checked on 6.1.5). Vendor lives in a named volume; after changing `composer.lock` run `docker compose exec app composer install`.

JS/CSS are prebuilt static files under `public/` (`public/js`, `public/vendor`, `public/modules`, …); the root `webpack.mix.js` only rebuilds a few bundles and isn't needed for normal work.

## Dangerous things to know

- **`reset:db` is scheduled twice daily** (`app/Console/Kernel.php`). It is the vendor's demo reset: it drops **every table** and reimports `salepropos.sql` (`app/Console/Commands/ResetDB.php`; that file isn't in this copy, so the job would leave the DB empty). Never run `schedule:run`/`schedule:work` or `php artisan reset:db` against a real database; remove the schedule entry for any real deployment.
- `routes/web.php` exposes unauthenticated `GET /migrate` (runs migrate + db:seed) and `GET /clear` (optimize:clear + cache forgets).
- `.env` holds real credentials — don't print it.
- **Avoid `php artisan view:cache` / `php artisan optimize`.** On 5.8.0 they compiled `@can` to a Gate check that crashed every page (missing `model_has_permissions`). 6.1.5 creates that table and checks `@can` through `Gate::before` (see Permissions), so this may no longer apply, but it hasn't been verified. If pages fail after caching, run `php artisan view:clear`. To syntax-check Blade, load the page instead.
- The vendor code also ships demo/diagnostic commands (`app/Console/Commands/RunScenario*`, `SaleProDemoBuildCommand`, `FreshBusinessReset*`, accounting remediation commands) that write to or reset business data. Don't run them against a real database.

## Architecture

### Request pipeline and global state
- Old-style (pre-Laravel-11) wiring: `app/Http/Kernel.php`, `app/Console/Kernel.php`, `RouteServiceProvider` — not the `bootstrap/app.php` fluent config.
- Almost all back-office routes sit in `routes/web.php` inside `['common', 'auth', 'active']`. **`App\Http\Middleware\Common`** is the central bootstrapper: it loads `general_settings`, sets timezone, and `View::share`s / `config([...])`s globals used everywhere (`general_setting`, `currency`, `currency_list`, `role`, `permission_list`, `categories_list`, alert counts, `config('currency')`, `config('decimal')`, `config('date_format')`, `config('staff_access')`, `config('without_stock')`, `config('addons')`, …). Blade views assume these exist; many views also call the `gen_setting()` helper.
- `app/Helpers/helpers.php` (autoloaded) provides `format_currency()`, `gen_setting()` and `normalize_to_sql_datetime()`, which read those runtime configs.

### Caching — must invalidate manually
Settings and lookups are cached under fixed keys: `general_setting`, `pos_setting`, `currency`, `currency_list`, `permissions`, `role_has_permissions`, `role_has_permissions_list{role_id}`, `user_role`, `category_list`, `product_list`, `warehouse_list`, `languages_list`, etc. (`translations_{locale}` uses `rememberForever`). When changing the underlying rows, call `cache()->forget(...)` (the `App\Traits\CacheForget` trait) as existing controllers do, or changes won't appear. Local `.env` uses `CACHE_DRIVER=array`.

### Permissions
Spatie permission tables are used, but users have a single `role_id` column (not Spatie's model_has_roles). Enforcement styles:
- Controllers: `Role::find(Auth::user()->role_id)->hasPermissionTo('sales-index')` (most common).
- Routes: custom `permission:<name>` middleware (`PermissionMiddleware`), `import.permission:<name>`, `hasPermanentDeletePermission`, and module middleware (`EnsureModuleAccess`, `EnsureBusinessModuleEnabled`).
- Blade/Gate: `@can('perm')` goes through `Gate::before` in `AuthServiceProvider`, which asks `App\Services\PermissionService` whether the user's role has the permission.
Permission names follow `<resource>-index|view|add|edit|delete` plus `sidebar_*` entries. New permissions need a row in `permissions` and assignment in `role_has_permissions` (see `RoleController`); 6.1.5 adds them with migrations (e.g. `add_tax_report_permission`). Also add them to `database/seeders/data/permissions.txt` so `LocalBootstrapSeeder` installs get them.

Row-level scoping: `App\Traits\StaffAccess::staffAccessCheck($query)` limits users with `role_id > 2` to their own records (`config('staff_access') == 'own'`) or their warehouse (`'warehouse'`). 6.1.5 adds model-level warehouse scopes (`App\Traits\WarehouseScoped` and `*WarehouseScoped`, `EnforceWarehouseAccess`). Listing queries for new features should follow the existing ones.

### Translations
Locale comes from the `language` cookie or the default row in `languages`. Strings live in the **`translations` DB table** and are injected by `App\Services\SaleProTranslator` (`AppServiceProvider`); there is no `lang/` directory. Use `__('db.Some Key')`. Add new keys to every file in `database/seeders/Tenant/translations/*.php` (18 locales, key/value rows), then run `php artisan translations:sync` and forget `translations_{locale}`. `database/seeders/translations/` and `Tenant/TranslationsTableSeeder.php` are 5.8.0 leftovers.

### Modules (nwidart/laravel-modules)
Add-ons live in `Modules/<Name>` (Config, Entities = models, Http, Resources/views, Routes, Database/Migrations). Bundled in 6.1.5: AIAssistant, Ecommerce, IndiaGST, Manufacturing, Project, Repair, Restaurant, SocialCommerce, VCardNfc, Woocommerce. `modules_statuses.json` also lists Gym, Middleware, Tailoring and ZatcaIntegrationKsa, which are **not present**. Code guards modules with `file_exists(base_path('Modules/X'))`, `config('addons')` (`general_settings.modules`) or `ModuleAccessService`/`ModuleRegistry`. Module views are referenced as `repair::...`.

### Multi-tenant SaaS code paths (inactive)
Code paths conditioned on `config('database.connections.saleprosaas_landlord')` (tenant lookup, `App\Models\landlord\*`, stancl/tenancy middleware, `TenantInfo` trait) belong to the SaaS edition. That connection, the landlord models and stancl/tenancy are not installed; `PRODUCT_MODE=CLIENT`. Leave these branches alone unless adding SaaS support.

### API
`routes/api.php` (Sanctum + `common` + `validate_mobile_token`, for the mobile app) imports `App\Http\Controllers\Api\*`, but **that directory does not exist** in this copy, so API routes will fail at dispatch and `php artisan route:list` may error.

### Domain layout
- Controllers in `app/Http/Controllers` are large (e.g. `SaleController`), heavily using `DB::table` alongside Eloquent models in `app/Models`. Listing pages use server-side DataTables via POST `*-data` endpoints. 6.1.5 moves much logic into services: sale creation in `App\Services\Domain\SaleDomainService`, accounting in `app/Services/Accounting*` (`config/accounting.php`, journals, chart of accounts), plus `StockLedgerService`, `ImportBatchService`, `RegisterReconciliationService`, `NotificationService`.
- Stock is tracked per warehouse (`product_warehouse`), per batch (`product_batches`, with expiry), per variant (`product_variants`) and per import layer (`import_stock_layers`). Follow an existing flow (e.g. `SaleDomainService`, `PurchaseController@store`) when touching quantities.
- POS: `resources/views/backend/sale/pos.blade.php` only includes `sale/partials/pos_common.blade.php`, which holds the whole POS. `SaleController::limsProductSearch` (`sales/lims_product_search`, used by POS, Sale add/edit and Exchange) returns **named keys** (`price`, `wholesale_price`, `discount`, `group_price`, …). `QuotationController::limsProductSearch` still returns a numbered array.
- Views: `resources/views/backend/<feature>/`, main layout `backend/layout/main.blade.php`, sidebar `backend/layout/sidebar.blade.php`.
- Pluggable integrations: payment gateways (`app/Services/PaymentService.php`, `app/Services/Payment`, `app/Contracts/Payble`), SMS providers (`app/SMSProviders`, `app/Contracts/Sms`, `SmsService`), couriers (`app/Services/Courier`), receipt printers (ESC/POS, `PrinterService`), ZATCA e-invoicing (`config('is_zatca')`).
- Scheduled commands: `purchase:auto`, `dsoalert:find`, `notifications:expiry-check`, `notifications:recover-stale`.

## Local customizations (re-apply on vendor upgrades)
- **Customer group prices:** table `customer_group_product_prices`, `App\Traits\CustomerGroupPrice::groupPriceFor()`, used in both `limsProductSearch` methods. Prices are set in the product form and on the Group Price List page (`CustomerGroupPriceController`, `customer-group-prices` routes). The screens skip the group % when the search result says `group_price` (index 19 for quotations).
- **POS:** confirm dialog before changing customer with a non-empty cart (`changeCustomerModal`, `repriceCartLines`). Wholesale mode falls back to the regular price (`linePrice`).
- **Per-line currency in POS:** `product_sales.currency_id/exchange_rate`, currency picker modal, `currencies.show_in_pos/rounding`, `sale_currency_totals` (`SaleCurrencyTotal::syncForSale` via `Sale::booted`), shown on invoices and in sale details.
- **Google Map link:** `map_link` on customers and suppliers.
- **Exchange screen:** 6.1.5 reads the search result by position, so `sale-exchange/create.blade.php` converts it with `legacyProductRow()`.
- **PrintNode key:** read from env in `config/printing.php` (6.1.5 hard-codes it).
