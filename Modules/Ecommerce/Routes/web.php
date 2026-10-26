<?php

use Modules\Ecommerce\Http\Controllers\AuthController;
use Modules\Ecommerce\Http\Controllers\CartController;
use Modules\Ecommerce\Http\Controllers\CheckoutController;
use Modules\Ecommerce\Http\Controllers\CustomerController;
use Modules\Ecommerce\Http\Controllers\CollectionController;
use Modules\Ecommerce\Http\Controllers\EcommerceSettingController;
use Modules\Ecommerce\Http\Controllers\FaqController;
use Modules\Ecommerce\Http\Controllers\FaqCategoriesController;
use Modules\Ecommerce\Http\Controllers\FrontController;
use Modules\Ecommerce\Http\Controllers\MenuController;
use Modules\Ecommerce\Http\Controllers\MenuItemsController;
use Modules\Ecommerce\Http\Controllers\OrdersController;
use Modules\Ecommerce\Http\Controllers\PageController;
use Modules\Ecommerce\Http\Controllers\PageWidgetController;
use Modules\Ecommerce\Http\Controllers\SliderController;
use Modules\Ecommerce\Http\Controllers\SocialLinksController;
use Modules\Ecommerce\Http\Controllers\BlogsController;
use Modules\Ecommerce\Http\Controllers\EcommerceController;
use Modules\Ecommerce\Http\Controllers\NewsletterController;
use Modules\Ecommerce\Http\Controllers\ProductReviewController;
use Modules\Ecommerce\Http\Controllers\WidgetController;
use Modules\Ecommerce\Http\Controllers\DeliveryAreaController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Ecommerce\Http\Controllers\EcommercePaymentController;
use Modules\Ecommerce\Http\Controllers\PaymentController;
use Modules\Ecommerce\Http\Controllers\DemoStorefrontController;

// 1. Determine if this is a SaaS Landlord environment
$isSaaS = (bool) config('database.connections.saleprosaas_landlord');

// 2. Build Tenancy middleware array dynamically
$tenancyMiddleware = $isSaaS ? [InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class, \App\Http\Middleware\EnsureCommissionSubscriptionAccess::class] : [];

// ==========================================
// ADMIN/COMMON ROUTES
// ==========================================
$adminMiddleware = array_merge(
    $tenancyMiddleware,
    ['common', 'auth', 'active', 'module.access:ecommerce']
);

Route::middleware($adminMiddleware)->group(function () use ($isSaaS) {

    // Sliders
    Route::prefix('sliders')->group(function () {
        Route::get('/', [SliderController::class, 'slidersShow'])->name('slider.index');
        Route::post('/create', [SliderController::class, 'slidersCreate'])->name('slidersCreate');
        Route::get('/delete/{id}', [SliderController::class, 'slidersDelete'])->name('slidersDelete');
    });

    // Reviews
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ProductReviewController::class, 'index'])->name('reviews.index');
        Route::get('/destroy/{id}', [ProductReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::post('/toggle-status', [ProductReviewController::class, 'toggleStatus'])->name('reviews.toggleStatus');
    });

    // Newsletter
    Route::prefix('newsletter')->name('newsletter.')->group(function () {
        Route::get('/', [NewsletterController::class, 'index'])->name('index');
        Route::get('/delete/{id}', [NewsletterController::class, 'destroy'])->name('delete');
    });

    // Page
    Route::prefix('pages')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('page.index');
        Route::get('/create', [PageController::class, 'create'])->name('page.create');
        Route::post('/store', [PageController::class, 'store'])->name('page.store');
        Route::get('/edit/{id}', [PageController::class, 'edit'])->name('page.edit');
        Route::post('/update', [PageController::class, 'update'])->name('page.update');
        Route::get('/delete/{id}', [PageController::class, 'destroy'])->name('page.destroy');
        Route::post('/massdelete', [PageController::class, 'delete_by_selection']);

        Route::post('/widget/store', [PageWidgetController::class, 'store'])->name('page.widget.store');
        Route::post('/widget/update', [PageWidgetController::class, 'update'])->name('page.widget.update');
        Route::post('/widget/order', [PageWidgetController::class, 'order'])->name('page.widget.order');
        Route::post('/widget/{widget}/activation', [PageWidgetController::class, 'activation'])->name('page.widget.activation');
        Route::delete('/widget/{widget}', [PageWidgetController::class, 'destroy'])->name('page.widget.destroy');
        Route::get('/widget/delete/{id}', [PageWidgetController::class, 'delete'])->name('page.widget.delete');

        // Dynamic wildcard routes at the bottom
        Route::get('/edit/{id}/{slug}', [PageController::class, 'generateUniqueSlugEdit'])->name('page.edit.slug');
        Route::get('/{status}/{id}', [PageController::class, 'status'])->name('page.status');
        Route::get('/{slug}', [PageController::class, 'generateUniqueSlug'])->name('page.slug');
    });

    // Social Links
    Route::prefix('social')->group(function () {
        Route::get('/', [SocialLinksController::class, 'index'])->name('social.links');
        Route::post('/store', [SocialLinksController::class, 'store'])->name('social.links.store');
        Route::get('/edit/{id}', [SocialLinksController::class, 'edit'])->name('social.links.edit');
        Route::post('/update', [SocialLinksController::class, 'update'])->name('social.links.update');
        Route::get('/delete/{id}', [SocialLinksController::class, 'destroy'])->name('social.links.destroy');
    });

    // Blog
    Route::prefix('blog')->group(function () {
        Route::get('/list', [BlogsController::class, 'index'])->name('blog.post');
        Route::get('/create', [BlogsController::class, 'create'])->name('blog.post.create');
        Route::post('/store', [BlogsController::class, 'store'])->name('blog.post.store');
        Route::get('/edit/{id}', [BlogsController::class, 'edit'])->name('blog.post.edit');
        Route::post('/update', [BlogsController::class, 'update'])->name('blog.post.update');
        Route::get('/delete/{id}', [BlogsController::class, 'destroy'])->name('blog.post.destroy');
        Route::get('/post/{slug}', [BlogsController::class, 'generateUniqueSlug'])->name('blog.post.slug');
        Route::get('/edit/{id}/{slug}', [BlogsController::class, 'generateUniqueSlugEdit'])->name('blog.post.edit.slug');
    });

    // Settings
    Route::prefix('setting')->group(function () use ($isSaaS) {
        Route::get('ecommerce-setting', [EcommerceSettingController::class, 'index'])->name('setting.ecommerce');
        Route::post('ecommerce-setting/update', [EcommerceSettingController::class, 'update'])->name('setting.ecommerce.update');

        Route::get('ecommerce-theme', [EcommerceSettingController::class, 'themeSettings'])->name('setting.ecommerce.theme');
        Route::post('ecommerce-theme/update', [EcommerceSettingController::class, 'updateTheme'])->name('setting.ecommerce.theme.update');

        // Standalone specific setting route
        if (!$isSaaS) {
            Route::get('payment-gateways', [EcommerceSettingController::class, 'gateway'])->name('setting.ecommerce.gateway');
        }
    });

    // Standalone specific routes (Delivery Area)
    Route::prefix('delivery-area')->name('delivery.area.')->group(function () {
        Route::get('/',               [DeliveryAreaController::class, 'index'])->name('index');
        Route::post('/store',         [DeliveryAreaController::class, 'store'])->name('store');
        Route::get('/edit/{id}',      [DeliveryAreaController::class, 'edit'])->name('edit');
        Route::post('/update/{id}',   [DeliveryAreaController::class, 'update'])->name('update');
        Route::post('/bulk-update',   [DeliveryAreaController::class, 'bulkUpdate'])->name('bulk.update');
        Route::get('/toggle/{id}',    [DeliveryAreaController::class, 'toggleStatus'])->name('toggle');
        Route::get('/delete/{id}',    [DeliveryAreaController::class, 'destroy'])->name('destroy');
    });
    Route::get('/delivery-areas/active', [DeliveryAreaController::class, 'activeAreas'])->name('delivery.areas.active');

    // Menu Management
    Route::prefix('menu')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('menu.index');
        Route::post('/store', [MenuController::class, 'store'])->name('menu.store');
        Route::get('/edit/{id}', [MenuController::class, 'edit']);
        Route::post('/update', [MenuController::class, 'updateMenu'])->name('menu.update');
        Route::get('/delete/{id}', [MenuController::class, 'destroy']);

        Route::get('/details/{id}', [MenuItemsController::class, 'index']);
        Route::get('/add-category-to-menu/{menuid}/{ids}', [MenuItemsController::class, 'addCatToMenu']);
        Route::get('/add-collection-to-menu/{menuid}/{ids}', [MenuItemsController::class, 'addCollectionToMenu']);
        Route::get('/add-brand-to-menu/{menuid}/{ids}', [MenuItemsController::class, 'addBrandToMenu']);
        Route::get('/add-page-to-menu/{menuid}/{ids}', [MenuItemsController::class, 'addPageToMenu']);
        Route::get('/add-blog-to-menu/{menuid}/{link}', [MenuItemsController::class, 'addBlogToMenu']);
        Route::get('/add-custom-link/{menuid}/{link}/{url}', [MenuItemsController::class, 'addCustomLink']);
        Route::post('/menuitem/update/{id}', [MenuItemsController::class, 'updateMenuItem']);
        Route::get('/menuitem/delete/{id}/{key}/{in}', [MenuItemsController::class, 'deleteMenuItem']);
    });

    // Collection Management
    Route::prefix('collection')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('collection.index');
        Route::get('/create', [CollectionController::class, 'create'])->name('collection.create');
        Route::post('/store', [CollectionController::class, 'store'])->name('collection.store');
        Route::get('/edit/{id}', [CollectionController::class, 'edit']);
        Route::post('/update', [CollectionController::class, 'update'])->name('collection.update');
        Route::get('/delete/{id}', [CollectionController::class, 'destroy']);
        Route::get('/{slug}', [CollectionController::class, 'generateUniqueSlug'])->name('collection.slug');
        Route::get('/edit/{id}/{slug}', [CollectionController::class, 'generateUniqueSlugEdit'])->name('collection.edit.slug');
    });

    // Ecom FAQ
    Route::prefix('ecom-faq')->group(function () {
        Route::get('/', [FaqController::class, 'index'])->name('faq.index');
        Route::post('/store', [FaqController::class, 'store'])->name('faq.store');
        Route::get('/edit/{id}', [FaqController::class, 'edit']);
        Route::post('/update', [FaqController::class, 'update'])->name('faq.update');
        Route::get('/delete/{id}', [FaqController::class, 'destroy']);
    });

    // FAQ Categories
    Route::prefix('faq/categories')->group(function () {
        Route::get('/', [FaqCategoriesController::class, 'index'])->name('faq.category.index');
        Route::post('/store', [FaqCategoriesController::class, 'store'])->name('faq.category.store');
        Route::get('/edit/{id}', [FaqCategoriesController::class, 'edit']);
        Route::post('/update', [FaqCategoriesController::class, 'update'])->name('faq.category.update');
        Route::get('/delete/{id}', [FaqCategoriesController::class, 'destroy']);
    });

    // Widgets
    Route::prefix('widget')->group(function () {
        Route::get('/', [WidgetController::class, 'index'])->name('widget.index');
        Route::post('/store', [WidgetController::class, 'store'])->name('widget.store');
        Route::post('/update', [WidgetController::class, 'update'])->name('widget.update');
        Route::post('/order', [WidgetController::class, 'order'])->name('widget.order');
        Route::get('/delete/{id}', [WidgetController::class, 'delete'])->name('widget.delete');
    });
});


// ==========================================
// ECOMMERCE FRONTEND ROUTES
// ==========================================
$frontendMiddleware = array_merge(
    $tenancyMiddleware,
    ['ecommerce']
);

Route::middleware($frontendMiddleware)->group(function () use ($isSaaS) {

    Route::get('demo/{profile}', [DemoStorefrontController::class, 'switch'])
        ->where('profile', '[a-z0-9_-]+')
        ->name('ecommerce.demo.switch');
    Route::get('/', [FrontController::class, 'index']);
    Route::get('search/{product}', [FrontController::class, 'search'])->name('live.search');
    Route::post('search-product/', [FrontController::class, 'searchProduct'])->name('products.search');
    Route::get('product/{product_name}/{product_id}', [FrontController::class, 'productDetails'])
        ->name('ecommerce.product.show');
    Route::get('product/{product_id}', [FrontController::class, 'productModal'])
        ->name('ecommerce.product.modal');

    // Customer Registration
    Route::get('/customer/register', [AuthController::class, 'register']);
    Route::post('register-customer', [AuthController::class, 'processRegisterCustomer'])->name('customerRegistration');

    // Customer Login & Auth
    Route::get('/customer/login/{verify?}', [AuthController::class, 'login'])->name('customer.login');
    Route::post('/login-customer', [AuthController::class, 'processLogin'])->name('customerLogin');
    Route::get('/customer/logout', [AuthController::class, 'logout'])->name('customer.logout');
    Route::get('verify/{id}', [AuthController::class, 'verify'])->name('verify');

    // Standalone specific customer mobile authentication routes
    if (!$isSaaS) {
        Route::post('/customer/mobileLogin', [AuthController::class, 'mobileLogin'])->name('customer.mobileLogin');
        Route::post('/mobileExistStatus', [AuthController::class, 'mobileExistStatus'])->name("login.mobileExistStatus");
        Route::post('/mobileSignUp', [AuthController::class, 'mobileSignUp'])->name('login.mobileSignUp');
    }

    // Forgot Password
    Route::get('/customer/forgot-password', [AuthController::class, 'getEmail'])->name('getEmail');
    Route::post('/customer/forgot-password', [AuthController::class, 'checkEmail'])->name('checkEmail');
    Route::post('/customer/reset-password', [AuthController::class, 'getPass'])->name('getPass');
    Route::post('/customer/save-password', [AuthController::class, 'changePass'])->name('changePass');

    // Navigation and Filtering
    Route::get('shop/', [FrontController::class, 'shop']);
    Route::get('shop/{category}', [FrontController::class, 'category']);
    Route::get('products/collections/all/', [FrontController::class, 'collections']);
    Route::get('brand/{brand}', [FrontController::class, 'brandProducts']);
    Route::get('products/{collection}', [FrontController::class, 'collectionProducts']);
    Route::get('all-products', [FrontController::class, 'allProducts']);

    // Order Tracking & Actions
    Route::get('track-order/{order_id?}/{email?}', [FrontController::class, 'trackOrder']);
    Route::post('/change-currency', [EcommerceController::class, 'changeCurrency'])->name('change.currency');
    Route::post('/product/review', [ProductReviewController::class, 'store'])->name('products.review');

    // Cart
    Route::get('cart', [CartController::class, 'index'])->name('cart');
    Route::post('add-to-cart', [CartController::class, 'addToCart'])->name('addToCart');
    Route::post('update-cart', [CartController::class, 'updateCart'])->name('updateCart');
    Route::post('remove-from-cart', [CartController::class, 'removeFromCart'])->name('removeFromCart');

    // Checkout & Payment
    // payment gatway
    // Checkout & Payment
    Route::post('ecommerce/mpesa/push', [EcommercePaymentController::class, 'mpesaPush']);
    Route::post('ecommerce/mpesa/qr', [EcommercePaymentController::class, 'mpesaQr']);
    Route::post('ecommerce/mpesa/status', [EcommercePaymentController::class, 'mpesaStatus']);

    Route::post('ecommerce/momo/push', [EcommercePaymentController::class, 'momoPush']);
    Route::post('ecommerce/momo/qr', [EcommercePaymentController::class, 'momoQr']);
    Route::post('ecommerce/momo/status', [EcommercePaymentController::class, 'momoStatus']);

    Route::post('ecommerce/payhere/push', [EcommercePaymentController::class, 'payherePush']);
    Route::post('ecommerce/payhere/status', [EcommercePaymentController::class, 'payhereStatus']);

    Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout');

    Route::post('checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
    Route::post('/place-order', [OrdersController::class, 'create']);
    Route::get('checkout/payment', [OrdersController::class, 'onlinePayment'])->name('online.payment');
    Route::post('checkout/payment/{provider}/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate');
    Route::post('checkout/payment/{provider}/complete', [PaymentController::class, 'complete'])->name('payment.complete');
    Route::post('stripe-payment', [OrdersController::class, 'paymentProviderUnavailable']);
    Route::post('razorpay-payment', [OrdersController::class, 'paymentProviderUnavailable'])->name('razorpay.payment');
    Route::get('paystack-payment', [OrdersController::class, 'paymentProviderUnavailable'])->name('paystack.payment');
    Route::post('xendit-payment', [OrdersController::class, 'paymentProviderUnavailable'])->name('xendit.payment');
    Route::get('xendit-success', [OrdersController::class, 'paymentProviderUnavailable'])->name('xendit.success');

    Route::get('/order/success/{sale_reference}', [OrdersController::class, 'success'])->name('order.success');
    Route::get('/order/cancel', [OrdersController::class, 'cancel'])->name('order.cancel');

    // Marketing & Content
    Route::post('/apply-coupon', [CheckoutController::class, 'applyCoupon'])->name('applyCoupon');
    Route::post('/apply-gift-card', [CheckoutController::class, 'applyGiftCard'])->name('applyGiftCard');
    Route::post('/newsletter/subscribe', [FrontController::class, 'newsletter']);
    Route::post('/send-email', [FrontController::class, 'contactMail']);
    Route::get('/blog', [FrontController::class, 'blog']);
    Route::get('/blog/{slug}', [FrontController::class, 'blogPost']);

    // Catch-all structural pages
    Route::get('/{slug}', [FrontController::class, 'page'])
        ->where('slug', '(?!(?:empty-database|paypal-payment|mollie-payment|mollie-success)$)[^/]+');
});


// ==========================================
// CUSTOMER PORTAL ROUTES
// ==========================================
$customerMiddleware = array_merge(
    $tenancyMiddleware,
    ['ecommerce']
);

Route::middleware($customerMiddleware)->prefix('customer')->group(function () {
    Route::get('/profile', [CustomerController::class, 'index'])->name('customer.profile');
    Route::get('/orders', [CustomerController::class, 'orders']);
    Route::get('/order-details/{id}', [CustomerController::class, 'orderDetails']);
    Route::post('/order-cancel/{id}', [CustomerController::class, 'orderCancel'])->name('customer.order.cancel');
    Route::get('/address', [CustomerController::class, 'address']);
    Route::post('/address/create', [CustomerController::class, 'addressCreate']);
    Route::get('/address/default/{id}', [CustomerController::class, 'addressDefault']);
    Route::get('/address/edit/{id}', [CustomerController::class, 'addressEdit']);
    Route::post('/address/update', [CustomerController::class, 'addressUpdate']);
    Route::get('/address/delete/{id}', [CustomerController::class, 'AddressDelete']);
    Route::get('/account-details', [CustomerController::class, 'accountDetails']);
    Route::post('/account-details/update', [CustomerController::class, 'updateAccountDetails'])->name('updateAccountDetails');
    Route::get('/wishlist', [CustomerController::class, 'wishlist']);
    Route::get('/wishlist/{product_id}', [CustomerController::class, 'addToWishlist']);
    Route::get('/wishlist/delete/{product_id}', [CustomerController::class, 'deleteFromWishlist']);
});
