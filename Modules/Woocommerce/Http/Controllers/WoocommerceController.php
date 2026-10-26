<?php

namespace Modules\Woocommerce\Http\Controllers;

use DB;
use Auth;
use Session;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Warehouse;
use App\Models\Biller;
use App\Models\Currency;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Product_Sale;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Product_Warehouse;
use App\Models\Account;
use App\Models\Payment;
use App\Services\AccountingService;
use Modules\Woocommerce\Contracts\WooCommerceClient;
use Modules\Woocommerce\Contracts\WooCommerceClientFactory;
use Modules\Woocommerce\Services\Legacy\SaleProUnitHierarchy;
use Carbon\Carbon;
use Modules\Woocommerce\Models\WoocommerceSetting;
use Modules\Woocommerce\Models\WoocommerceSyncLog;
use Modules\Woocommerce\Exceptions\WooCommerceError;
use Modules\Woocommerce\Services\WooCommerceEndpointPolicy;
use Modules\Woocommerce\Services\Webhooks\WebhookSecretLifecycle;

class WoocommerceController extends Controller
{
    private $woocommerce;
    private  $created_data;
    private  $updated_data;
    private array $pendingCategoryMappings = [];
    private array $pendingProductMappings = [];

    public function __construct(private readonly WooCommerceClientFactory $clientFactory, private readonly SaleProUnitHierarchy $units) {}

    public function index()
    {
        $notify = [];

        $notSyncedCatcount = Category::where('is_active', 1)->whereNull('is_sync_disable')
                            ->whereNull('woocommerce_category_id')
                            ->count();

        if (!empty($notSyncedCatcount)) {
            $notify['not_synced_cat'] = $notSyncedCatcount . ' ' . ($notSyncedCatcount == 1 ? __('db.Category not synced') : __('db.Categories not synced'));
        }

        $updatedCatCount = Category::where('is_active', 1)->whereNull('is_sync_disable')
                            ->whereNotNull('woocommerce_category_id')
                            ->count();

        if (!empty($updatedCatCount)) {
            $notify['updated_cat'] = $updatedCatCount . ' ' . ($updatedCatCount == 1 ? __('db.Category can be updated') : __('db.Categories can be updated'));
        }

        $not_synced_product_count = Product::leftJoin('categories', 'categories.id', '=', 'products.category_id')
                                    ->whereNull('categories.is_sync_disable')->where('products.type', 'standard')
                                    ->where('products.is_active', 1)->whereNull('products.is_sync_disable')
                                    ->whereNull('products.woocommerce_product_id')
                                    ->count();

        if (!empty($not_synced_product_count)) {
            $notify['not_synced_product'] = $not_synced_product_count . ' ' . ($not_synced_product_count == 1 ? __('db.Product not synced') : __('db.Products not synced'));
        }

        $updated_product_count = Product::leftJoin('categories', 'categories.id', '=', 'products.category_id')
                                ->whereNull('categories.is_sync_disable')->where('products.type', 'standard')
                                ->where('products.is_active', 1)->whereNull('products.is_sync_disable')
                                ->whereNotNull('products.woocommerce_product_id')
                                ->count();

        if (!empty($updated_product_count)) {
            $notify['not_updated_product'] = $updated_product_count . ' ' . ($updated_product_count == 1 ? __('db.Product can be updated') : __('db.Products can be updated'));
        }

        $tax_rates = Tax::where('is_active', 1)->get();

        $woocommerce_tax_rates = WoocommerceSetting::query()->where('sync_enabled', true)->exists()
            ? $this->getWoocommerceTaxRates()
            : ['' => '--Select--'];

        return view('woocommerce::index', compact(
            'notify',
            'tax_rates',
            'woocommerce_tax_rates'
        ));
    }

    public function getWoocommerceTaxRates()
    {
        $woocommerce_setting = WoocommerceSetting::latest()->first();
        $this->woocommerce = $this->woocommerceApi($woocommerce_setting);
        if ($this->woocommerce != null) {
            $woocommerce_tax_info = $this->getAllResponse('taxes');
        }

        $woocommerce_tax_rates = ['' => '--Select--'];
        if (!empty($woocommerce_tax_info)) {
            foreach ($woocommerce_tax_info as $w) {
                $woocommerce_tax_rates[$w->id] = $w->name;
            }
        }
        return $woocommerce_tax_rates;
    }

    public function syncLog()
    {
        $woocommerceSyncLog = WoocommerceSyncLog::leftjoin('users', 'users.id', '=', 'woocommerce_sync_logs.synced_by')
        ->select([
            'woocommerce_sync_logs.created_at',
            'sync_type',
            'operation',
            'records',
            'name'
        ])->get();
        return view('woocommerce::sync_log', compact('woocommerceSyncLog'));
    }

    public function settings()
    {
        $customer_group = ['' => '--Select--'];
        $customer_group_info = CustomerGroup::where('is_active', 1)->get();
        if (!empty($customer_group_info)) {
            foreach ($customer_group_info as $c) {
                $customer_group[$c->id] = $c->name;
            }
        }

        $warehouse = ['' => '--Select--'];
        $warehouse_info = Warehouse::where('is_active', 1)->get();
        if (!empty($warehouse_info)) {
            foreach ($warehouse_info as $wh) {
                $warehouse[$wh->id] = $wh->name;
            }
        }

        $biller = ['' => '--Select--'];
        $biller_info = Biller::where('is_active', 1)->get();
        if (!empty($biller_info)) {
            foreach ($biller_info as $b) {
                $biller[$b->id] = $b->name;
            }
        }

        $woocommerce_setting = WoocommerceSetting::latest()->first();

        return view('woocommerce::settings', compact('woocommerce_setting', 'customer_group', 'warehouse', 'biller'));
    }

    public function webhookSecret(Request $request, WebhookSecretLifecycle $lifecycle)
    {
        abort_unless(in_array((int) Auth::user()->role_id, [1, 2], true), 403);
        $validated = $request->validate(['action' => ['required', 'in:replace,revoke,disconnect'], 'webhook_secret' => ['nullable', 'string', 'min:16', 'max:512'], 'rotation_overlap_seconds' => ['nullable', 'integer', 'min:0', 'max:86400']]);
        $setting = WoocommerceSetting::latest()->firstOrFail();
        if ($validated['action'] === 'replace') {
            if (empty($validated['webhook_secret'])) { return back()->withErrors(['webhook_secret' => 'A new webhook secret is required for explicit replacement.']); }
            $lifecycle->replace($setting, $validated['webhook_secret'], (int) ($validated['rotation_overlap_seconds'] ?? 3600));
        } else {
            $lifecycle->revoke($setting);
            if ($validated['action'] === 'disconnect') {
                $setting->update(['sync_enabled' => false]);
                DB::table('woocommerce_webhook_security_events')->insert(['woocommerce_setting_id' => $setting->id, 'actor_id' => Auth::id(), 'event_type' => 'connection_disconnected', 'metadata' => json_encode(['key_version' => (int) $setting->webhook_key_version]), 'created_at' => now()]);
            }
        }
        return back()->with('message', 'Webhook security settings updated.');
    }

    public function store(Request $request)
    {
        $woocommerce_setting = WoocommerceSetting::latest()->first();

        if (is_null($woocommerce_setting) && empty($woocommerce_setting)) {
            $woocommerce_setting = new WoocommerceSetting();
        }

        if ($request->api_settings) {
            $validated = $request->validate([
                'woocomerce_app_url' => ['required', 'string', 'max:2048'],
                'woocomerce_consumer_key' => ['nullable', 'string', 'max:512'],
                'woocomerce_consumer_secret' => ['nullable', 'string', 'max:512'],
            ]);
            $woocommerce_setting->woocomerce_app_url = app(WooCommerceEndpointPolicy::class)
                ->validateAndNormalize($validated['woocomerce_app_url']);
            if (!empty($validated['woocomerce_consumer_key'])) {
                $woocommerce_setting->woocomerce_consumer_key = $validated['woocomerce_consumer_key'];
            }
            if (!empty($validated['woocomerce_consumer_secret'])) {
                $woocommerce_setting->woocomerce_consumer_secret = $validated['woocomerce_consumer_secret'];
            }
            $woocommerce_setting->sync_enabled = $request->boolean('sync_enabled');
            $woocommerce_setting->legacy_activation_review_required = false;
        }
        else if ($request->product_sync_settings) {
            $woocommerce_setting->default_tax_class = $request->default_tax_class;
            $woocommerce_setting->product_tax_type = $request->product_tax_type;
            $woocommerce_setting->manage_stock = $request->manage_stock;
            $woocommerce_setting->stock_status = $request->stock_status;
            $woocommerce_setting->product_status = $request->product_status;
        }
        else if ($request->order_sync_settings) {
            $woocommerce_setting->customer_group_id = $request->customer_group_id;
            $woocommerce_setting->warehouse_id = $request->warehouse_id;
            $woocommerce_setting->biller_id = $request->biller_id;
            $woocommerce_setting->order_status_pending = $request->sell_status[0];
            $woocommerce_setting->order_status_processing = $request->sell_status[1];
            $woocommerce_setting->order_status_on_hold = $request->sell_status[2];
            $woocommerce_setting->order_status_completed = $request->sell_status[3];
            $woocommerce_setting->order_status_draft = $request->sell_status[4];
        }

        else if ($request->webhook_settings) {
            $woocommerce_setting->webhook_secret_order_created = $request->webhook_secret_order_created;
            $woocommerce_setting->webhook_secret_order_updated = $request->webhook_secret_order_updated;
            $woocommerce_setting->webhook_secret_order_deleted = $request->webhook_secret_order_deleted;
            $woocommerce_setting->webhook_secret_order_restored = $request->webhook_secret_order_restored;
        }

        $woocommerce_setting->save();
        return redirect()->back();
    }

    public function woocommerceApi($woocommerce_setting): ?WooCommerceClient
    {
        if (!is_null($woocommerce_setting) && !empty($woocommerce_setting) &&  $woocommerce_setting->woocomerce_app_url && $woocommerce_setting->woocomerce_consumer_key && $woocommerce_setting->woocomerce_consumer_secret) {
            return $this->clientFactory->make($woocommerce_setting);
        }
        else {
            Session::flash('message', __('db.Please connect with WooCommerce'));
            Session::flash('type', 'danger');
        }
    }

    public function syncCategories()
    {
        if(!env('USER_VERIFIED')) {
            $output = [
                'success' => 0,
                'msg' => 'This feature is disable for demo!'
            ];
            return $output;
        }
        $woocommerce_setting = WoocommerceSetting::latest()->first();
        if (!$woocommerce_setting?->sync_enabled) return ['success'=>0, 'msg'=>'WooCommerce synchronization is disabled.'];
        $this->woocommerce = $this->woocommerceApi($woocommerce_setting);
        if ($this->woocommerce != null) {
            try {
                $categories = Category::where('is_active', 1)->whereNull('is_sync_disable')
                                ->get();

                $category_data = [];
                $new_categories = [];
                $this->created_data = [];
                $this->updated_data = [];
                $this->pendingCategoryMappings = [];

                foreach ($categories as $category) {
                    if (empty($category->woocommerce_category_id)) {
                        $category_data['create'][] = [
                            'name' => $category->name
                        ];
                        $new_categories[] = $category;
                        $this->created_data[] = $category->name;
                    } else {
                        $category_data['update'][] = [
                            'id' => $category->woocommerce_category_id,
                            'name' => $category->name
                        ];
                        $this->updated_data[] = $category->name;
                    }
                }

                if (!empty($category_data['create'])) {
                    $this->syncCat($category_data['create'], 'create', $new_categories);
                }
                if (!empty($category_data['update'])) {
                    $this->syncCat($category_data['update'], 'update', $new_categories);
                }

                //assign parent category for child categories
                $child_categories = Category::where('is_active', 1)->whereNull('is_sync_disable')
                                    ->where('parent_id', '!=', NULL)
                                    ->get();

                $this->assignParentForChildCategories($child_categories, $this->pendingCategoryMappings);

                DB::transaction(function (): void {
                    foreach ($this->pendingCategoryMappings as $categoryId => $remoteId) {
                        Category::query()->whereKey($categoryId)->update(['woocommerce_category_id' => $remoteId]);
                    }
                    if (!empty($this->created_data)) $this->createWoocommerceSyncLog('categories', 'created', $this->created_data);
                    if (!empty($this->updated_data)) $this->createWoocommerceSyncLog('categories', 'updated', $this->updated_data);
                });

                $output = [
                    'success' => 1,
                    'msg' => __('db.Synced category successfully')
                ];
            } catch (Exception $e) {
                if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                    $output = [
                        'success' => 0,
                        'msg' => $e->getMessage(),
                    ];
                } else {
                    $output = [
                        'success' => 0,
                        'msg' => __('db.Something went wrong')
                    ];
                }
            }
        }
        else {
            $output = [
                'success' => 0,
                'msg' => __('db.Please connect with WooCommerce')
            ];
        }
        return $output;
    }

    public function syncCat($data, $type, $new_categories = [])
    {
        $count = 0;
        foreach (array_chunk($data, 99) as $chunked_array) {
            $sync_data = [];
            $sync_data[$type] = $chunked_array;

            //Batch update categories
            $response = $this->woocommerce->post('products/categories/batch', $sync_data);

            if ($type === 'create') {
                if (!isset($response->create) || count($response->create) !== count($chunked_array)) {
                    throw new WooCommerceError('WooCommerce returned an incomplete category response.');
                }
                foreach ($response->create as $value) {
                    $remoteId = (int) ($value->id ?? $value->error->data->resource_id ?? 0);
                    if ($remoteId <= 0 || !isset($new_categories[$count])) {
                        throw new WooCommerceError('WooCommerce rejected a category export.');
                    }
                    $this->pendingCategoryMappings[$new_categories[$count]->id] = $remoteId;
                    $count++;
                }
            }
        }
    }

    public function assignParentForChildCategories($child_categories, array $pendingMappings = [])
    {
        $category_id_woocommerce_id = Category::where('is_active', 1)->whereNull('is_sync_disable')
            ->pluck('woocommerce_category_id', 'id')
            ->toArray();
        $category_id_woocommerce_id = $pendingMappings + $category_id_woocommerce_id;

        $category_data = [];
        $new_categories = [];
        foreach ($child_categories as $category) {
            if (empty($category_id_woocommerce_id[$category->parent_id])) {
                continue;
            }
            $category_data['update'][] = [
                'id' => $category->woocommerce_category_id,
                'parent' => $category_id_woocommerce_id[$category->parent_id]
            ];
        }

        if (!empty($category_data['update'])) {
            $this->syncCat($category_data['update'], 'update', $new_categories);
        }
    }

    public function resetSyncedCategory()
    {
        try {
            Category::where('is_active', 1)->whereNull('is_sync_disable')->update(['woocommerce_category_id' => null]);
            $this->createWoocommerceSyncLog('categories', 'reset', null);
            $output = ['success' => 1, 'msg' => __('db.Reset synced category successfully')];
        }
        catch (Exception $e) {
            $output = ['success' => 0, 'msg' => __('db.Something went wrong')];
        }
        return $output;
    }

    public function syncProducts()
    {
        $woocommerce_setting = WoocommerceSetting::latest()->first();
        if (!$woocommerce_setting?->sync_enabled) return ['success'=>0, 'msg'=>'WooCommerce synchronization is disabled.'];
        if (!$woocommerce_setting->warehouse_id) return ['success'=>0, 'msg'=>'Select a warehouse before synchronizing product stock.'];

        $this->woocommerce = $this->woocommerceApi($woocommerce_setting);

        if ($this->woocommerce != null) {
            try {
                $products = Product::leftjoin('taxes', 'taxes.id', '=', 'products.tax_id')
                        ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                        ->whereNull('categories.is_sync_disable')
                        ->where('products.type', 'standard')->where('products.is_active', 1)
                        ->whereNull('products.is_sync_disable')
                        ->select(['products.*', 'rate', 'woocommerce_category_id'])
                        ->get();

                $this->created_data = [];
                $this->updated_data = [];
                $this->pendingProductMappings = [];

                $product_data = [];
                $new_products = [];

                foreach ($products as $product) {
                    $prod_data_array = [];

                    if (empty($product->woocommerce_category_id)) {
                        throw new WooCommerceError('Product category is not mapped to WooCommerce.');
                    }

                    $prod_data_array['name'] = $product->name;

                    if ($product->is_variant == 1) {
                        $prod_data_array['type'] = 'variable';
                    }
                    else {
                        $prod_data_array['type'] = 'simple';
                        if ($woocommerce_setting->stock_status != null) {
                            $prod_data_array['stock_status'] = $woocommerce_setting->stock_status;
                        }
                    }

                    $prod_data_array['sku'] = $product->code;

                    $prod_data_array['description'] = $product->product_details;

                    $prod_data_array['regular_price'] = $this->priceForWoocoomerce($product->price, $product->rate, $woocommerce_setting->product_tax_type, $product->tax_method);

                    $prod_data_array['categories'] = [
                        [
                            'id' => $product->woocommerce_category_id
                        ]
                    ];

                    $warehouseStock = Product_Warehouse::query()
                        ->where('product_id', $product->id)
                        ->where('warehouse_id', $woocommerce_setting->warehouse_id)
                        ->whereNull('variant_id')
                        ->value('qty');
                    if ($warehouseStock === null) {
                        throw new WooCommerceError('Product '.$product->name.' has no stock row in the selected warehouse.');
                    }
                    if ($product->is_batch || $product->is_imei) {
                        throw new WooCommerceError('Batch and IMEI product stock cannot be exported as aggregate WooCommerce stock.');
                    }
                    $prod_data_array['stock_quantity'] = (string) $warehouseStock;

                    $prod_data_array['tax_class'] = ($woocommerce_setting->default_tax_class != null) ?
                    $woocommerce_setting->default_tax_class : 'standard';

                    if ($woocommerce_setting->manage_stock != null) {
                        $prod_data_array['manage_stock'] = $woocommerce_setting->manage_stock;
                    }
                    ///for update////
                    if ($woocommerce_setting->product_status != null) {
                        $prod_data_array['status'] = $woocommerce_setting->product_status;
                    }
                    ///for update////

                    if (!empty($product->image) && file_exists('public/images/product/'.$product->image)) {
                        $prod_data_array['images'] = !empty($product->woocommerce_media_id) ? [['id' => $product->woocommerce_media_id]] : [['src' => url('images/product', $product->image)]];
                    }

                    if (empty($product->woocommerce_product_id)) {
                         ///for client////
                        // if ($woocommerce_setting->product_status != null) {
                        //     $prod_data_array['status'] = $woocommerce_setting->product_status;
                        // }
                        ///for client////
                        $product_data['create'][] = $prod_data_array;
                        $new_products[] = $product;
                        $this->created_data[] = $product->name;
                    }
                    else {
                        $prod_data_array['id'] = $product->woocommerce_product_id;
                        $product_data['update'][] = $prod_data_array;
                        $this->updated_data[] = $product->name;
                    }
                }

                if (!empty($product_data['create'])) {
                    $this->syncProd($product_data['create'], 'create', $new_products);
                }
                if (!empty($product_data['update'])) {
                    $this->syncProd($product_data['update'], 'update', $new_products);
                }

                DB::transaction(function (): void {
                    foreach ($this->pendingProductMappings as $productId => $mapping) {
                        Product::query()->whereKey($productId)->update($mapping);
                    }
                    if (!empty($this->created_data)) $this->createWoocommerceSyncLog('products', 'created', $this->created_data);
                    if (!empty($this->updated_data)) $this->createWoocommerceSyncLog('products', 'updated', $this->updated_data);
                });

                $output = [
                    'success' => 1,
                    'msg' => __('db.Synced product successfully')
                ];
            } catch (Exception $e) {
                if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                    $output = [
                        'success' => 0,
                        'msg' => $e->getMessage(),
                    ];
                } else {
                    $output = [
                        'success' => 0,
                        'msg' => __('db.Something went wrong')
                    ];
                }
            }
        }
        else {
            $output = [
                'success' => 0,
                'msg' => __('db.Please connect with WooCommerce')
            ];
        }
        return $output;
    }

    public function syncProd($data, $type, $new_products = [])
    {
        $count = 0;
        foreach (array_chunk($data, 99) as $chunked_array) {
            $sync_data = [];
            $sync_data[$type] = $chunked_array;

            //Batch update products
            $response = $this->woocommerce->post('products/batch', $sync_data);

            if ($type === 'create') {
                if (!isset($response->create) || count($response->create) !== count($chunked_array)) {
                    throw new WooCommerceError('WooCommerce returned an incomplete product response.');
                }
                foreach ($response->create as $value) {
                    $remoteId = (int) ($value->id ?? $value->error->data->resource_id ?? 0);
                    if ($remoteId <= 0 || !isset($new_products[$count])) {
                        throw new WooCommerceError('WooCommerce rejected a product export.');
                    }
                    $this->pendingProductMappings[$new_products[$count]->id] = [
                        'woocommerce_product_id' => $remoteId,
                        'woocommerce_media_id' => !empty($value->images[0]->id) ? (int) $value->images[0]->id : null,
                    ];
                    $count++;
                }
            }
        }
    }

    public function priceForWoocoomerce($product_price, $tax_rate, $product_tax_type, $tax_method)
    {
        // SalePro tax_method Exclusive = 1
        // SalePro tax_method Inclusive = 2
        $woocommerce_price = $product_price;
        if ($tax_rate != null) {
            if($product_tax_type=='exc'){
                if($tax_method==1){
                    $woocommerce_price = $product_price;
                }
                else {
                    $woocommerce_price = (100 * $product_price) / ($tax_rate + 100);
                }
            }
            else {
                if($tax_method==1){
                    $woocommerce_price = $product_price + $product_price * ($tax_rate /100);
                }
                else {
                    $woocommerce_price = $product_price;
                }
            }
        }
        return $woocommerce_price;
    }

    public function createWoocommerceSyncLog($type, $operation = null, $records = [])
    {
        WoocommerceSyncLog::create([
            'sync_type' => $type,
            'operation' => $operation,
            'records' => !empty($records) ? json_encode($records) : null,
            'synced_by' => Auth::id()
        ]);
    }

    public function resetSyncedProduct()
    {
        try {
            Product::where('type', 'standard')->where('is_active', 1)
                    ->update(['woocommerce_product_id' => null, 'woocommerce_media_id' => null]);
            $this->createWoocommerceSyncLog('products', 'reset', null);
            $output = ['success' => 1, 'msg' => __('db.Reset synced product successfully')];
        }
        catch (Exception $e) {
            $output = ['success' => 0, 'msg' => __('db.Something went wrong')];
        }
        return $output;
        //if need variable need to Update variations table and Update variation templates
    }

    public function mapTaxRates(Request $request)
    {
        $transactionStarted = false;
        try {
            $setting = WoocommerceSetting::latest()->first();
            if (!$setting?->sync_enabled) return ['success'=>0,'msg'=>'WooCommerce synchronization is disabled.'];
            $this->woocommerce = $this->woocommerceApi($setting);
            $remoteTaxIds = collect($this->getAllResponse('taxes'))->map(fn ($tax) => (int)$tax->id)->all();
            DB::beginTransaction();
            $transactionStarted = true;
            $input = $request->validate(['taxes'=>['required','array'],'taxes.*'=>['nullable','integer','min:1']]);
            foreach ($input['taxes'] as $key => $value) {
                $value = !empty($value) ? $value : null;
                if (!Tax::query()->whereKey($key)->exists()) throw new WooCommerceError('A selected SalePro tax does not exist.');
                if ($value !== null && !in_array((int)$value, $remoteTaxIds, true)) throw new WooCommerceError('A selected WooCommerce tax does not exist.');
                Tax::where('id', $key)->update(['woocommerce_tax_id' => $value]);
            }
            DB::commit();
            $transactionStarted = false;
            $output = ['success' => 1, 'msg' => __('db.Tax maped successfully')];
        } catch (Exception $e) {
            if ($transactionStarted && DB::transactionLevel() > 0) DB::rollBack();
            $output = ['success' => 0, 'msg' => __('db.Something went wrong')];
        }
        return $output;
    }

    public function syncOrders()
    {
        // Import bypasses the reviewed Phase 2 pricing and invoice snapshot workflow.
        if (app(\App\Services\ZatcaIntegrationService::class)->isPhase2Configured()) {
            return ['success' => 0, 'msg' => 'WooCommerce order sync is not yet available in ZATCA Phase 2. Import orders only after a reviewed Phase 2 workflow is implemented.'];
        }
        $woocommerce_setting = WoocommerceSetting::latest()->first();
        if (!$woocommerce_setting?->sync_enabled) return ['success'=>0, 'msg'=>'WooCommerce synchronization is disabled.'];
        $this->woocommerce = $this->woocommerceApi($woocommerce_setting);
        if ($this->woocommerce != null) {
            if ($woocommerce_setting->customer_group_id && $woocommerce_setting->warehouse_id && $woocommerce_setting->biller_id && $woocommerce_setting->order_status_pending && $woocommerce_setting->order_status_on_hold && $woocommerce_setting->order_status_draft && $woocommerce_setting->order_status_processing && $woocommerce_setting->order_status_completed) {
                try {
                    $orders = $this->getAllResponse('orders');
                    DB::beginTransaction();
                    $taxes = Tax::whereNotNull('woocommerce_tax_id')->pluck('rate','woocommerce_tax_id')->toArray();

                    $woocommerce_sells = Sale::whereNotNull('woocommerce_order_id')->get();

                    $products_data = Product::whereNotNull('woocommerce_product_id')->get();

                    $products = $products_data->mapWithKeys(function ($item) {
                            return [$item->woocommerce_product_id => [$item->id, $item->sale_unit_id, $item->qty]];
                        })->toArray();

                    $products_in_warehouse = Product_Warehouse::where('warehouse_id', $woocommerce_setting->warehouse_id)->pluck('product_id')->toArray();

                    $this->created_data = [];
                    foreach ($orders as $order) {

                        if ($order->status == 'cancelled' || $order->status == 'refunded' || $order->status == 'failed') {
                            continue;
                        }

                        //Search if order already exists
                        $order_exist = $woocommerce_sells->filter(function ($item) use ($order) {
                            return $item->woocommerce_order_id == $order->id;
                        })->first();

                        if (empty($order_exist)) {
                            $orderCurrencyCode = strtoupper(trim((string) ($order->currency ?? '')));
                            $orderCurrency = Currency::query()
                                ->where('code', $orderCurrencyCode)
                                ->where('is_active', 1)
                                ->first();
                            if (!$orderCurrency || (float) $orderCurrency->exchange_rate <= 0) {
                                throw new WooCommerceError('WooCommerce order currency is not configured as an active SalePro currency.');
                            }

                            //create customer
                            $customer_data =[];
                            $customer_data['customer_group_id'] = $woocommerce_setting->customer_group_id;
                            $customer_data['name'] = $order->billing->first_name . ' '. $order->billing->last_name;
                            $customer_data['company_name'] = $order->billing->company;
                            $customer_data['email'] = $order->billing->email;
                            $customer_data['phone_number'] = $order->billing->phone ? $order->billing->phone : '000000';
                            $customer_data['address'] = $order->billing->address_1 . ' '. $order->billing->address_2;
                            $customer_data['city'] = $order->billing->city;
                            $customer_data['state'] = $order->billing->state;
                            $customer_data['postal_code'] = $order->billing->postcode;
                            $customer_data['country'] = $order->billing->country;
                            $customer_data['is_active'] = 1;

                            $customer = Customer::updateOrCreate(['name'=>$customer_data['name'], 'phone_number'=>$customer_data['phone_number']], $customer_data);
                            //create sale
                            $item = 0;
                            $total_qty = 0;
                            $total_price = 0;
                            foreach ($order->line_items as $line_item) {
                                if (!array_key_exists($line_item->product_id,$products)) {
                                    throw new WooCommerceError('This order contains a product that is not mapped in SalePro.');
                                }
                                if (!in_array($products[$line_item->product_id][0], $products_in_warehouse)) {
                                        throw new WooCommerceError('This order contains a product unavailable in the selected warehouse.');
                            }
                                $item++;
                                $total_qty += $line_item->quantity;
                                $total_price += $line_item->subtotal;
                            }

                            $sale_data =[];
                            $sale_data['reference_no'] = 'woo-' . $order->id;
                            $sale_data['user_id'] = Auth::id();
                            $sale_data['customer_id'] = $customer->id;
                            $sale_data['warehouse_id'] = $woocommerce_setting->warehouse_id;
                            $sale_data['biller_id'] = $woocommerce_setting->biller_id;
                            $sale_data['item'] = $item;
                            $sale_data['total_qty'] = $total_qty;
                            $sale_data['total_discount'] = 0;
                            $sale_data['order_discount_type'] = 'Flat';
                            $sale_data['order_discount_value'] = $order->discount_total;
                            $sale_data['order_discount'] = $order->discount_total;
                            $sale_data['total_tax'] = $order->cart_tax;
                            $sale_data['total_price'] = $total_price;
                            $sale_data['grand_total'] = $order->total;
                            $sale_data['currency_id'] = $orderCurrency->id;
                            $sale_data['exchange_rate'] = $orderCurrency->exchange_rate;
                            $sale_data['shipping_cost'] = $order->shipping_total + $order->shipping_tax;

                            if ($order->status == 'pending') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_pending;
                            }
                            else if ($order->status == 'on-hold') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_on_hold;
                            }
                            else if ($order->status == 'draft') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_draft;
                            }
                            else if ($order->status == 'processing') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_processing;
                            }
                            else if ($order->status == 'completed') {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_completed;
                            }
                            else {
                                $sale_data['sale_status'] = $woocommerce_setting->order_status_pending;
                            }

                            if ($order->payment_method == 'cod') {
                                $sale_data['payment_status'] = 2;
                            }
                            else {
                                $sale_data['payment_status'] = 4;
                            }

                            $sale_data['woocommerce_order_id'] = $order->id;

                            if ($sale_data['payment_status'] == 4) {
                                $sale_data['paid_amount'] = $order->total;
                            }

                            $sale = Sale::create($sale_data);

                            //create product_sales
                            foreach ($order->line_items as $line_item) {
                                $product_sales_data =[];
                                $product_sales_data['sale_id'] =  $sale->id;
                                $product_sales_data['product_id'] = $products[$line_item->product_id][0];
                                $product_sales_data['qty'] = $line_item->quantity;
                                $product_sales_data['sale_unit_id'] = $products[$line_item->product_id][1];
                                $product_sales_data['net_unit_price'] = ($line_item->subtotal)/($line_item->quantity);
                                $product_sales_data['discount'] = 0;
                                if (!empty($line_item->taxes)) {
                                    if (array_key_exists($line_item->taxes[0]->id,$taxes)) {
                                        $product_sales_data['tax_rate'] = $taxes[$line_item->taxes[0]->id];
                                        $product_sales_data['tax'] = $line_item->taxes[0]->total;
                                    } else {
                                        throw new WooCommerceError('This order contains an unmapped WooCommerce tax rate.');
                                    }
                                } else {
                                    $product_sales_data['tax_rate'] = 0;
                                    $product_sales_data['tax'] = 0;
                                }
                                $product_sales_data['total'] = $line_item->subtotal;
                                Product_Sale::create($product_sales_data);
                                if($sale_data['sale_status'] == 1) {
                                    $quantity = $this->units->toBaseQuantity(
                                        $products_data->find($products[$line_item->product_id][0]),
                                        $products[$line_item->product_id][1],
                                        $line_item->quantity
                                    );

                                    $product_warehouse_data = Product_Warehouse::FindProductWithoutVariant($products[$line_item->product_id][0], $sale_data['warehouse_id'])->whereNull('variant_id')->lockForUpdate()->firstOrFail();
                                    if ((float) $product_warehouse_data->qty < $quantity) {
                                        throw new WooCommerceError('This order exceeds available stock in the selected warehouse.');
                                    }

                                    // Preserve the legacy aggregate stock update only after
                                    // the authoritative warehouse row has been locked and checked.
                                    $qty = $products[$line_item->product_id][2] - $quantity;
                                    $products_data->find($products[$line_item->product_id][0])->update(['qty'=> $qty]);

                                    //deduct quantity from warehouse
                                    $product_warehouse_data->qty -= $quantity;
                                    $product_warehouse_data->save();
                                }
                            }

                            if ((int) $sale_data['sale_status'] !== 3) {
                                $saleAccounting = app(AccountingService::class)
                                    ->recordSale($sale, 'woocommerce_order_created');
                                if (!$saleAccounting->success) {
                                    throw new WooCommerceError($saleAccounting->error ?: 'WooCommerce Sale accounting failed.');
                                }
                            }

                            //create payments
                            if($sale_data['payment_status'] == 4) {
                                $payments_data =[];
                                $payments_data['payment_reference'] = 'woo-pay-'.$order->id;
                                $payments_data['user_id'] = Auth::id();
                                $payments_data['sale_id'] = $sale->id;
                                $payments_data['account_id'] = Account::where('is_default', true)->first()->id;
                                $payments_data['amount'] = $order->total;
                                $payments_data['currency_id'] = $orderCurrency->id;
                                $payments_data['exchange_rate'] = $orderCurrency->exchange_rate;
                                $payments_data['change'] = 0;
                                $payments_data['paying_method'] = trim((string) ($order->payment_method_title ?? $order->payment_method ?? 'WooCommerce')) ?: 'WooCommerce';
                                $payment = Payment::create($payments_data);
                                $paymentAccounting = app(AccountingService::class)
                                    ->recordPayment($payment, 'woocommerce_payment_created');
                                if (!$paymentAccounting->success) {
                                    throw new WooCommerceError($paymentAccounting->error ?: 'WooCommerce Payment accounting failed.');
                                }
                            }

                            $this->created_data[] = $sale_data['reference_no'];
                        }
                    }

                    //Create log
                    if (!empty($this->created_data)) {
                        $this->createWoocommerceSyncLog('orders', 'created',  $this->created_data);
                    }

                    DB::commit();

                    $output = ['success' => 1,
                                    'msg' => __('db.Synced order successfully')
                                ];
                } catch (Exception $e) {
                    if (DB::transactionLevel() > 0) DB::rollBack();

                    if (get_class($e) == 'Modules\Woocommerce\Exceptions\WooCommerceError') {
                        $output = ['success' => 0,
                                    'msg' => $e->getMessage(),
                                ];
                    }
                    else {
                        $output = ['success' => 0,
                                    'msg' => __('db.Something went wrong'),
                                ];
                    }
                }
            } else {
                $output = [
                    'success' => 0,
                    'msg' => __('db.Please select Default Customer Group/ Default Warehouse/ Default Biller/ Equivalent POS Sell Status in Order Sync Settings')
                ];
            }
        }
        else {
            $output = [
                'success' => 0,
                'msg' => __('db.Please connect with WooCommerce')
            ];
        }
        return $output;
    }

    public function getAllResponse($get_what, $params = [])
    {
        $all_list = [];
        $params['per_page'] = 100;
        $maxPages = 100;

        for ($page = 1; $page <= $maxPages; $page++) {
            $params['page'] = $page;
            try {
                $list = $this->woocommerce->get($get_what, $params);
            } catch (Exception $e) {
                throw new WooCommerceError('WooCommerce pagination failed before completion.');
            }
            if (!is_array($list)) {
                throw new WooCommerceError('WooCommerce returned malformed pagination data.');
            }
            if ($list === []) return $all_list;
            $all_list = array_merge($all_list, $list);
        }

        $params['page'] = $maxPages + 1;
        try {
            $overflow = $this->woocommerce->get($get_what, $params);
        } catch (Exception $e) {
            throw new WooCommerceError('WooCommerce pagination failed before completion.');
        }
        if (!is_array($overflow) || $overflow !== []) {
            throw new WooCommerceError('WooCommerce pagination exceeded the configured safety bound.');
        }

        return $all_list;
    }

}
