<?php

namespace Modules\SocialCommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SocialCommerce\Entities\SocialCommerceSetting;
use Modules\SocialCommerce\Entities\SocialProductSetting;
use App\Models\Product;

class SocialCommerceController extends Controller
{
    public function index()
    {
        $setting = SocialCommerceSetting::firstOrCreate([]);
        $status = $setting->is_active ? 'Active' : 'Inactive';
        
        $totalProducts = Product::where('is_active', true)->count();
        $missingImages = Product::where('is_active', true)->where(function($q) {
            $q->whereNull('image')->orWhere('image', 'zummXD2dvAtI.png'); // default image name sometimes
        })->count();
        $outOfStock = Product::where('is_active', true)->where('qty', '<=', 0)->count();

        $publishedCount = SocialProductSetting::where('is_published', 1)
            ->whereHas('product', function($q){ $q->where('is_active', true); })->count();
        
        $unpublishedCount = $totalProducts - $publishedCount;

        return view('socialcommerce::dashboard', compact(
            'status', 'totalProducts', 'publishedCount', 'unpublishedCount', 'missingImages', 'outOfStock'
        ));
    }

    public function catalog(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $query = Product::where('is_active', true);
        $publishedIds = SocialProductSetting::where('is_published', 1)->pluck('product_id')->toArray();

        if ($filter == 'published') {
            $query->whereIn('id', $publishedIds);
        } elseif ($filter == 'unpublished') {
            $query->whereNotIn('id', $publishedIds);
        } elseif ($filter == 'missing_image') {
            $query->where(function($q) {
                $q->whereNull('image')->orWhere('image', 'zummXD2dvAtI.png');
            });
        } elseif ($filter == 'out_of_stock') {
            $query->where('qty', '<=', 0);
        }

        $products = $query->paginate(20);

        // Preload settings for the products on this page
        $productIds = $products->pluck('id')->toArray();
        $settings = SocialProductSetting::whereIn('product_id', $productIds)->get()->keyBy('product_id');

        $products->getCollection()->each(function (Product $product) {
            $product->setAttribute('social_commerce_url', app(\Modules\SocialCommerce\Services\ProductLinkResolver::class)->resolve($product));
        });

        return view('socialcommerce::catalog', compact('products', 'filter', 'settings', 'publishedIds'));
    }

    public function togglePublish(Request $request, $id)
    {
        $isAjax = $request->ajax() || $request->wantsJson();

        try {
            $product = Product::findOrFail($id);
            $unpublish = $request->has('unpublish') && ($request->get('unpublish') == '1' || $request->get('unpublish') === true || $request->get('unpublish') === 'true');
            
            // Validate before publishing
            if (!$unpublish) {
                $hasImage = !empty($product->image) && $product->image !== 'zummXD2dvAtI.png';
                if (!$hasImage || empty($product->name)) {
                    $errorMessage = __('db.Product missing required fields (Image or Name) for publishing.');
                    if ($isAjax) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMessage,
                            'product_id' => (int) $id,
                            'published' => false,
                        ], 422);
                    }
                    return redirect()->back()->with('not_permitted', $errorMessage);
                }
            }

            $setting = SocialProductSetting::firstOrNew(['product_id' => $id]);
            $setting->is_published = $unpublish ? 0 : 1;
            $setting->save();

            $msg = $unpublish ? __('db.Product unpublished successfully') : __('db.Product published to social commerce catalogue');

            if ($isAjax) {
                $publicUrl = null;
                $qrUrl = null;
                $whatsAppUrl = null;
                $facebookUrl = null;
                $telegramUrl = null;

                if (!$unpublish) {
                    $resolver = app(\Modules\SocialCommerce\Services\ProductLinkResolver::class);
                    $publicUrl = $resolver->resolve($product);
                    $qrUrl = route('socialcommerce.qr', $product->id);
                    $socialTitle = $setting->social_title ?: $product->name;
                    $socialDesc = $setting->social_description ?: strip_tags($product->product_details ?? '');
                    $shareText = trim($socialTitle . ($socialDesc ? ' - ' . $socialDesc : ''));
                    $whatsAppUrl = 'https://wa.me/?' . http_build_query(['text' => $shareText . ' ' . $publicUrl], '', '&', PHP_QUERY_RFC3986);
                    $facebookUrl = 'https://www.facebook.com/sharer/sharer.php?' . http_build_query(['u' => $publicUrl], '', '&', PHP_QUERY_RFC3986);
                    $telegramUrl = 'https://t.me/share/url?' . http_build_query(['url' => $publicUrl, 'text' => $shareText], '', '&', PHP_QUERY_RFC3986);
                }

                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'product_id' => (int) $id,
                    'published' => !$unpublish,
                    'public_url' => $publicUrl,
                    'qr_url' => $qrUrl,
                    'whatsapp_url' => $whatsAppUrl,
                    'facebook_url' => $facebookUrl,
                    'telegram_url' => $telegramUrl,
                ], 200);
            }

            return redirect()->back()->with('message', $msg);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('db.Product not found.'),
                    'product_id' => (int) $id,
                ], 404);
            }
            return redirect()->back()->with('not_permitted', __('db.Product not found.'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Social commerce togglePublish error: ' . $e->getMessage(), [
                'exception' => $e,
                'product_id' => $id,
                'request' => $request->all(),
            ]);

            $genericError = __('db.Something went wrong. Please try again.');
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $genericError,
                    'product_id' => (int) $id,
                ], 500);
            }
            return redirect()->back()->with('not_permitted', $genericError);
        }
    }

    public function settings()
    {
        $settings = SocialCommerceSetting::firstOrCreate([]);
        return view('socialcommerce::settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'is_active' => 'nullable|boolean',
            'link_target' => 'required|string|in:ecommerce,qr,fallback',
            'allow_fallback' => 'nullable|boolean',
            'whatsapp_number' => 'nullable|string',
            'share_template' => 'nullable|string',
            'wa_template_order_confirmation' => 'nullable|string',
            'wa_template_payment_request' => 'nullable|string',
            'wa_template_delivery_update' => 'nullable|string',
        ]);

        $settings = SocialCommerceSetting::firstOrCreate([]);
        $settings->update([
            'is_active' => $request->has('is_active'),
            'link_target' => $data['link_target'],
            'allow_fallback' => $request->has('allow_fallback'),
            'whatsapp_number' => $data['whatsapp_number'],
            'share_template' => $data['share_template'],
            'wa_template_order_confirmation' => $data['wa_template_order_confirmation'],
            'wa_template_payment_request' => $data['wa_template_payment_request'],
            'wa_template_delivery_update' => $data['wa_template_delivery_update'],
        ]);

        return redirect()->back()->with('message', __('db.Settings updated successfully'));
    }

    public function generateQr($id)
    {
        $product = Product::findOrFail($id);
        $resolver = new \Modules\SocialCommerce\Services\ProductLinkResolver();
        $url = $resolver->resolve($product);

        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($url);

        return response($qrCode)->header('Content-type', 'image/svg+xml');
    }
}
