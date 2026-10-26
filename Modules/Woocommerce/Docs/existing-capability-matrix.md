# WooCommerce existing capability matrix

This matrix is derived from the module controller, routes, settings views, migrations, and sync-log UI that predate the 2026 security work.

| Existing capability | Direction | Trigger | Current condition | Required correction |
| --- | --- | --- | --- | --- |
| API connection/settings | SalePro to WooCommerce | Admin form | Credentials are encrypted and URL-validated; legacy form also controls operational state | Preserve masked credentials, validate all fields, and keep disabled by default |
| Category synchronization | SalePro to WooCommerce | Manual POST | Creates/updates active categories, then assigns parents; remote calls occur inside a DB transaction and identity is a legacy core column | Move network work outside transactions, preserve stable IDs, make replay safe, and report partial failures |
| Category mapping reset | Local maintenance | Manual POST | Clears all legacy category mappings | Require explicit administrator authorization and preserve audit/history where available |
| Standard product synchronization | SalePro to WooCommerce | Manual POST | Creates/updates active standard products including name, SKU, description, category, price, tax, image, status and aggregate quantity | Use configured warehouse stock, validate units/currency/tax, move network work outside transactions, and prevent duplicate creates |
| Product mapping reset | Local maintenance | Manual POST | Clears product and media IDs | Require administrator authorization and prevent accidental broad reset |
| Tax-rate mapping | WooCommerce reference to SalePro | Manual POST | Persists selected WooCommerce tax IDs | Validate authorization and submitted tax IDs; avoid synchronous lookup during page rendering |
| Order import | WooCommerce to SalePro | Manual POST | Imports new orders, customers, sales, lines, payments and stock effects | Preserve scope but add idempotency, unit/warehouse checks, deterministic references, and transaction-safe remote/local boundaries |
| Synchronization log | Local | Manual actions | Records entity, operation, records and user | Sanitize failures and retain useful operator evidence |
| Webhook intake | WooCommerce to retained receipt | Signed public POST | Authenticated, replay-safe, ordered, recoverable and queued; business processing intentionally unimplemented | Preserve certified boundary; do not add new webhook business topics in this phase |

No repository evidence establishes bidirectional catalog import, multiple WooCommerce connections, media ingestion, remote archive policy, rewards synchronization, accounting synchronization, or a new synchronization dashboard. Those remain deferred.
