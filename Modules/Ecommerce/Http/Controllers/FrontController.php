<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Ecommerce\Entities\Sliders;
use App\Models\Product;
use App\Models\Category;
use Modules\Ecommerce\Entities\Page;
use Modules\Ecommerce\Mail\ContactUs;
use App\Models\MailSetting;
use DB;
use Mail;
use Auth;
use Cache;
use Intervention\Image\Facades\Image;
use File;
use Modules\Ecommerce\Entities\ProductReview;
use App\Models\Product_Sale;
use Modules\Ecommerce\Services\HomePageDataService;
use Modules\Ecommerce\Entities\PageWidgets;
use Modules\Ecommerce\Services\HomepageCacheService;
use Modules\Ecommerce\Services\StorefrontProductService;
use Modules\Ecommerce\Services\StorefrontPricingService;
use Modules\Ecommerce\Services\StorefrontVariantService;
use Modules\Ecommerce\Services\StorefrontAvailabilityService;
use Modules\Ecommerce\Services\DemoStorefrontProfile;
use Modules\Ecommerce\Services\DemoHomepageComposer;
use Modules\Ecommerce\Services\DemoMediaResolver;
use Modules\Ecommerce\Services\StorefrontCacheKey;

class FrontController extends Controller
{
    use \App\Traits\MailInfo;

    public function index(
        HomePageDataService $homePageData,
        HomepageCacheService $homepageCache,
        DemoStorefrontProfile $demoProfile,
        DemoHomepageComposer $demoHomepage
    )
    {
        $demo_home = $demoHomepage->compose();
        if ($demo_home) {
            return theme_view('home', compact('demo_home'));
        }

        $sliders = Cache::remember(StorefrontCacheKey::for('home.sliders'), 3600, function () {
            return DB::table('sliders')->orderBy('order', 'asc')->get();
        });
        $sliders = $demoProfile->filterSliders(collect($sliders));

        $ecommerce_setting = Cache::get(StorefrontCacheKey::for('setting'));
        $home = $ecommerce_setting->home_page ?? null;

        $widgets = null;
        $recently_viewed = session()->get('recently_viewed', []);

        if($home){
            $page = Cache::remember(StorefrontCacheKey::for('home.page.' . $home), 3600, function () use ($home) {
                return DB::table('pages')->where('id', $home)->first();
            });

            if($page && $page->template == 'home'){
                $widgets = Cache::remember($homepageCache->widgetListKey((int) $home), 3600, function () use ($home) {
                    return PageWidgets::query()
                        ->where('page_id', $home)
                        ->where('is_active', true)
                        ->orderByRaw('CAST(`order` AS UNSIGNED) ASC')
                        ->orderBy('id')
                        ->get();
                });
                $widgets = $homePageData->hydrate($widgets, (int) $home);
            }
        }

        return theme_view('home', compact('sliders', 'widgets', 'recently_viewed'));
    }

    public function page($slug)
    {
        $page = DB::table('pages')->where('slug', $slug)->where('status', 1)->first();

        if(isset($page)){
            if($page->template == 'faq'){
                $categories = DB::table('faq_categories')->orderBy('order','ASC')->get();
                $faqs = DB::table('faqs')->orderBy('order','ASC')->get();
                return theme_view('faq', compact('page','faqs','categories'));
            }

            if($page->template == 'contact'){
                return theme_view('contact', compact('page'));
            }
        }

        return theme_view('page-show', compact('page'));
    }

    public function blog()
    {
        $blogs = DB::table('blogs')->get();

        return theme_view('blog', compact('blogs'));
    }

    public function blogPost($slug)
    {
        $post = DB::table('blogs')->where('slug', $slug)->first();

        return theme_view('blog-details', compact('post'));
    }

    public function trackOrder($order_id='', $email='')
    {
        if(($order_id != '') && ($email != '')){
            $sale = DB::table('sales')
                        ->join('customers', 'sales.customer_id', '=', 'customers.id')
                        ->select('sales.*', 'customers.name as customer_name', 'customers.email as customer_email', 'customers.phone as customer_phone', 'customers.address as customer_address', 'customers.city as customer_city')
                        ->where('customers.email', $email)
                        ->where('sales.reference_no', $order_id)
                        ->first();

            if (!$sale) {
                return theme_view('track-order')->with('error', 'Order not found');
            }

            // Customer object for view compatibility
            $customer = (object)[
                'id' => $sale->customer_id,
                'name' => $sale->customer_name,
                'email' => $sale->customer_email,
                'phone' => $sale->customer_phone,
                'address' => $sale->customer_address,
                'city' => $sale->customer_city
            ];

            $delivery = DB::table('deliveries')->where('sale_id', $sale->id)->first() ?? 0;

            $product_sales = DB::table('product_sales')
                             ->join('products','product_sales.product_id','=','products.id')
                             ->select('products.name','products.image','products.is_variant','products.variant_option','product_sales.*')
                             ->where('sale_id', $sale->id)
                             ->get();

            return theme_view('track-order', compact('sale','customer','delivery','product_sales'));
        }

        return theme_view('track-order');
    }

    public function search(
        $product,
        StorefrontProductService $storefrontProducts,
        StorefrontPricingService $storefrontPricing,
        DemoMediaResolver $demoMedia
    )
    {
        $search = trim((string) $product);
        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $data = $storefrontProducts->search($search)
            ->select('id', 'image', 'name', 'slug', 'in_stock', 'qty', 'price', 'promotion_price', 'promotion', 'starting_date', 'last_date', 'is_variant', 'is_imei', 'type', 'sale_unit_id', 'product_list', 'qty_list', 'variant_list', 'combo_unit_id')
            ->orderByDesc('id')
            ->limit(StorefrontProductService::LIVE_SEARCH_LIMIT)
            ->get();
        $this->decorateProducts($data, $storefrontPricing);
        $data->each(fn ($item) => $item->storefront_image_url = $demoMedia->productImageUrl($item, 'small'));

        return response()->json($data);
    }

    public function searchProduct(
        Request $request,
        StorefrontProductService $storefrontProducts,
        StorefrontPricingService $storefrontPricing
    )
    {
        $search = trim((string) $request->input('search'));
        $query = mb_strlen($search) >= 2
            ? $storefrontProducts->search($search)
            : $storefrontProducts->query()->whereRaw('1 = 0');
        $products = $query->orderByDesc('id')
            ->paginate(StorefrontProductService::FULL_SEARCH_PER_PAGE)
            ->withQueryString();
        $this->decorateProducts($products, $storefrontPricing);

        return theme_view('products-search', compact('products', 'search'));
    }

    private function formatProductVariants($product) {
        if($product && $product->is_variant == 1 && $product->variant_option) {
            $product->variant_option = is_string($product->variant_option) ? json_decode($product->variant_option) : $product->variant_option;
            $product->variant_value = is_string($product->variant_value) ? json_decode($product->variant_value) : $product->variant_value;
        }
        return $product;
    }

    public function productDetails(
        $product_name,
        $product_id,
        StorefrontProductService $storefrontProducts,
        StorefrontPricingService $storefrontPricing,
        StorefrontVariantService $storefrontVariants,
        StorefrontAvailabilityService $storefrontAvailability
    )
    {
        $product = $storefrontProducts->find((int) $product_id);
        if (!$product) {
            abort(404);
        }

        if (!hash_equals((string) $product->slug, (string) $product_name)) {
            return redirect(url('product/' . $product->slug . '/' . $product->id), 301);
        }

        $recently_viewed = session()->get('recently_viewed', []);
        if(!in_array($product_id, $recently_viewed)) {
            $recently_viewed[] = $product_id;
            session(['recently_viewed' => $recently_viewed]);
        }

        $product = $this->formatProductVariants($product);
        $storefrontPricing->decorate($product);
        $storefrontAvailability->decorate($product);

        $brand = DB::table('brands')->where('id', $product->brand_id)->first();

        $categories = Cache::get(StorefrontCacheKey::for('categories'));
        $category = $categories ? $categories->where('id', $product->category_id)->first() : null;

        $related_products = [];
        if (!empty($product->related_products)) {
            $product_arr = array_values(array_filter(array_map('intval', explode(',', $product->related_products))));
            $relatedById = $storefrontProducts->query()
                ->whereIn('id', $product_arr)
                ->where('id', '!=', $product->id)
                ->get()
                ->keyBy('id');
            $related_products = collect($product_arr)
                ->reject(fn ($id) => $id === (int) $product->id)
                ->map(fn ($id) => $relatedById->get($id))
                ->filter()
                ->values();
            $this->decorateProducts($related_products, $storefrontPricing);
        }

        $reviews = ProductReview::where('product_id', $product_id)->where('approved', 1)->get();
        $auth_id = auth()->id();

        $review_permission = false;
        $already_review_exists = false;
        if ($auth_id) {
            $review_permission = Product_Sale::whereHas('sale', function ($query) use ($auth_id) {
                $query->where('user_id', $auth_id);
            })->where('product_id', $product_id)->exists();

            $already_review_exists = ProductReview::where('customer_id', $auth_id)->where('product_id', $product_id)->exists();
        }

        $total_rating_count = $reviews->count();
        $average_rating = $total_rating_count > 0 ? ($reviews->sum('rating') / $total_rating_count) : false;

        $variant = collect();
        if($product->is_variant == 1){
            $variant = $storefrontVariants->forProduct($product->id)
                ->map(function ($row) use ($product, $storefrontPricing) {
                    $price = $storefrontPricing->resolve($product, $row);
                    $row->regular_price = $price['regular_price'];
                    $row->selling_price = $price['selling_price'];
                    $row->promotion_active = $price['promotion_active'];
                    return $row;
                });
            $storefrontAvailability->decorateVariants($product, $variant);
        }

        return theme_view('product-details', compact(
            'product', 'brand', 'category', 'related_products', 'recently_viewed',
            'reviews', 'review_permission', 'already_review_exists', 'average_rating', 'variant'
        ));
    }

    public function productModal(
        $product_id,
        StorefrontProductService $storefrontProducts,
        StorefrontPricingService $storefrontPricing,
        DemoMediaResolver $demoMedia,
        StorefrontAvailabilityService $storefrontAvailability
    )
    {
        $product = $storefrontProducts->find((int) $product_id);
        abort_unless($product, 404);

        $product = $storefrontPricing->decorate($this->formatProductVariants($product));
        $storefrontAvailability->decorate($product);
        $product->storefront_image_url = $demoMedia->productImageUrl($product);

        return $product;
    }

    public function allProducts(
        StorefrontProductService $storefrontProducts,
        StorefrontPricingService $storefrontPricing
    )
    {
        $data = $storefrontProducts->query()
            ->select('id', 'image', 'name', 'price', 'promotion_price', 'promotion', 'starting_date', 'last_date')
            ->orderByDesc('id')
            ->take(10)
            ->get();
        $this->decorateProducts($data, $storefrontPricing);

        return response()->json($data);
    }

    public function category(
        $slug,
        Request $request,
        StorefrontProductService $storefrontProducts,
        StorefrontPricingService $storefrontPricing
    )
    {
        $category = DB::table('categories')
                        ->select('id','name','slug','page_title','short_description', 'parent_id')
                        ->where('slug', $slug)
                        ->where('is_active', 1)->first();

        if (!$category) abort(404);

        $sub_categories = DB::table('categories')
                            ->select('id','name','slug','page_title','short_description')
                            ->where('parent_id', $category->id)
                            ->where('is_active', 1)->get();

        $sub_cats = $sub_categories->pluck('id')->toArray();

        // 1. Build the products query
        $productsQuery = $storefrontProducts->query();

        if (count($sub_cats) > 0) {
            $productsQuery->where(function ($query) use ($category, $sub_cats) {
                $query->where('category_id', $category->id)
                      ->orWhereIn('category_id', $sub_cats);
            });
        } else {
            $productsQuery->where('category_id', $category->id);
        }

        $products = $productsQuery->orderByDesc('id')->paginate(10)->withQueryString();
        $this->decorateProducts($products, $storefrontPricing);

        // 2. Handle AJAX requests for load more
        if ($request->ajax()) {
            $view = theme_view('products-load-more', compact('products'))->render();
            return response()->json(['html' => $view]);
        }

        return theme_view('products', compact('products', 'category'));
    }

    public function shop()
    {
        $categoryList = collect(cache(StorefrontCacheKey::for('categories'), collect()))->where('is_active', 1);
        $categories = $categoryList->whereNull('parent_id')->values();
        $children = $categoryList->whereNotNull('parent_id')->groupBy('parent_id');
        foreach ($categories as $category) {
            $category->storefront_children = $children->get($category->id, collect())->values();
        }

        return theme_view('shop', compact('categories'));
    }

    public function collections(
        StorefrontPricingService $storefrontPricing,
        StorefrontProductService $storefrontProducts
    )
    {
        $collections = DB::table('collections')->where('status', 1)->get();

        // Avoid exploding massive strings in memory; let the DB handle it if possible, but since it's a comma-sep string in DB:
        $product_ids = [];
        foreach ($collections as $collection) {
            if (!empty($collection->products)) {
                $ids = explode(',', $collection->products);
                foreach ($ids as $id) {
                    $product_ids[$id] = true; // Use associative array keys for fast uniqueness
                }
            }
        }
        $product_ids = array_keys($product_ids);

        $products = [];
        if (!empty($product_ids)) {
            $products = $storefrontProducts->query()
                        ->select('id', 'image', 'name', 'slug','in_stock','qty','price','promotion_price','promotion','starting_date','last_date','is_variant','is_imei','type','sale_unit_id','product_list','qty_list','variant_list','combo_unit_id')
                        ->whereIn('id', $product_ids)
                        ->get();
            $this->decorateProducts($products, $storefrontPricing);
        }

        return theme_view('collections', compact('collections','products'));
    }

    public function collectionProducts(
        $collection,
        Request $request,
        StorefrontPricingService $storefrontPricing,
        StorefrontProductService $storefrontProducts
    )
    {
        $collections = DB::table('collections')->where('status', 1)->get();

        $collection = $collections->where('slug', $collection)->where('status', 1)->first();

        $products = [];
        if ($collection && !empty($collection->products)) {
            $product_arr = explode(',', $collection->products);

            $products = $storefrontProducts->query()
                        ->select('id', 'image', 'name', 'slug','in_stock','qty','price','promotion_price','promotion','starting_date','last_date','is_variant','is_imei','type','sale_unit_id','product_list','qty_list','variant_list','combo_unit_id')
                        ->whereIn('id', $product_arr)
                        ->get();
            $this->decorateProducts($products, $storefrontPricing);
        }

        return theme_view('collection-products', compact('products', 'collection', 'collections'));
    }

    public function brandProducts(
        $brand,
        Request $request,
        StorefrontProductService $storefrontProducts,
        StorefrontPricingService $storefrontPricing
    )
    {
        $brand = DB::table('brands')->where('slug', $brand)->where('is_active', 1)->first();
        if (!$brand) {
            abort(404);
        }
        $products = $storefrontProducts->query()
            ->where('brand_id', $brand->id)
            ->orderByDesc('id')
            ->paginate(5)
            ->withQueryString();
        $this->decorateProducts($products, $storefrontPricing);

        if ($request->ajax()) {
            $view = theme_view('brand-products-load-more', compact('products'))->render();
            return response()->json(['html' => $view]);
        }

        return theme_view('brand-products', compact('products', 'brand'));
    }

    public function contactMail(Request $request)
    {
        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'message' => $request->input('message'),
        ];

        $email_to = Cache::get(StorefrontCacheKey::for('setting'))->contact_form_email;

        $mail_setting = MailSetting::latest()->first();
        $this->setMailInfo($mail_setting);
        Mail::to($email_to)->send(new ContactUs($data));

        return response()->json('success');
    }

    public function newsletter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'  => 'required|unique:newsletter,email',
        ]);

        if($validator->fails()) {
            return $validator->errors();
        } else {

            $data = $request->except('_token');
            DB::table('newsletter')->insert($data);

            return response()->json('success');
        }
    }

    public function sessionRenew(Request $request)
    {
        return response()->json('success');
    }

    public function reviewStore(Request $request){
        return redirect()->back();
    }

    private function decorateProducts(iterable $products, StorefrontPricingService $pricing): void
    {
        $availability = app(StorefrontAvailabilityService::class);
        foreach ($products as $product) {
            $pricing->decorate($product);
        }
        $availability->decorateMany($products);
    }

}
