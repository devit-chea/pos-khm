# WooCommerce legacy action authorization matrix

| Route/action | HTTP method | Module access | Granular permission | Operational state |
| --- | --- | --- | --- | --- |
| `woocommerce.index` | GET | `module.access:woocommerce` | `woocommerce.sync` | Configuration remains visible; sync state is displayed |
| `woocommerce.sync-log` | GET | `module.access:woocommerce` | `woocommerce.sync` | Available while sync is disabled |
| `woocommerce.settings` | GET | `module.access:woocommerce` | `woocommerce.manage` | Available while sync is disabled |
| `woocommerce.store` | POST | `module.access:woocommerce` | `woocommerce.manage` | May configure or change sync state |
| `woocommerce.webhook-secret` | POST | `module.access:woocommerce` | `woocommerce.manage`, plus Owner/Admin | May configure while sync is disabled |
| `woocommerce.syncCategories` | POST | `module.access:woocommerce` | `woocommerce.sync` | Requires `sync_enabled` |
| `woocommerce.resetSyncedCategory` | POST | `module.access:woocommerce` | `woocommerce.manage` | High-risk administrative maintenance |
| `woocommerce.syncProducts` | POST | `module.access:woocommerce` | `woocommerce.sync` | Requires `sync_enabled` |
| `woocommerce.resetSyncedProduct` | POST | `module.access:woocommerce` | `woocommerce.manage` | High-risk administrative maintenance |
| `woocommerce.mapTaxRates` | POST | `module.access:woocommerce` | `woocommerce.manage` | Requires `sync_enabled` and validated remote IDs |
| `woocommerce.syncOrders` | POST | `module.access:woocommerce` | `woocommerce.sync` | Requires `sync_enabled` |
| Public signed webhook | POST | None | HMAC/replay trust boundary | Route exists only while module provider is enabled |

All browser mutations are declared as POST routes in the `web` middleware group and therefore use Laravel's CSRF middleware. Unknown module identifiers are rejected by `ModuleAccessService`; `general_settings.modules` is not consulted.
