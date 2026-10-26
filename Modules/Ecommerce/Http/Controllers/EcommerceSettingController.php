<?php

namespace Modules\Ecommerce\Http\Controllers;

use Modules\Ecommerce\Services\HomepageCacheService;

use App\Models\Currency;
use Illuminate\Http\Request;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Modules\Ecommerce\Entities\Page;
use App\Traits\CacheForget;
use Session;
use Cache;
use DB;

class EcommerceSettingController extends Controller
{
    use CacheForget;

    public function index()
    {
        $settings = DB::table('ecommerce_settings')->first();
        $pages = DB::table('pages')->where('status',1)->get();
        //return dd($pages);
        $warehouse_list = DB::table('warehouses')->where('is_active',1)->get();
        $biller_list = DB::table('billers')->where('is_active',1)->get();
        $currencies = Currency::query()->where('is_active',1)->get();
        $themes = $this->availableThemes();

        return view('ecommerce::backend.settings.index', compact('settings','pages','warehouse_list','biller_list','currencies','themes'));
    }

    public function update(Request $request, HomepageCacheService $homepageCache)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        $availableThemeSlugs = collect($this->availableThemes())->pluck('slug')->all();
        $this->validate($request, [
            'theme' => ['required', 'string', 'in:' . implode(',', $availableThemeSlugs)],
        ]);

        $data = [
            'storefront_enabled'  => $request->boolean('storefront_enabled'),
            'site_title'          => $request->site_title,
            'theme'               => $request->theme,
            'theme_font'          => $request->theme_font,
            'theme_color'         => $request->theme_color,
            'is_rtl'              => $request->is_rtl,
            'online_order'        => $request->online_order,
            'search'              => $request->search,
            'store_phone'         => $request->store_phone,
            'store_email'         => $request->store_email,
            'store_address'       => $request->store_address,
            'home_page'           => $request->home_page,
            'warehouse_id'        => $request->warehouse_id,
            'biller_id'           => $request->biller_id,
            'contact_form_email'  => $request->contact_form_email,
            'free_shipping_from'  => $request->free_shipping_from,
            'flat_rate_shipping'  => $request->flat_rate_shipping,
            'custom_css'          => $request->custom_css,
            'custom_js'           => $request->custom_js,
            'chat_code'           => $request->chat_code,
            'analytics_code'      => $request->analytics_code,
            'fb_pixel_code'       => $request->fb_pixel_code,
            'tktk_pixel_code'     => $request->tktk_pixel_code,
            'gift_card'           => $request->gift_card,
            'active_place_order'     => $request->has('active_place_order') ? 1 : 0,
            'active_whatsapp_order'  => $request->has('active_whatsapp_order') ? 1 : 0,
            'legacy_activation_review_required' => 0,
        ];

        if(!isset($request->gift_card)){
            $data['gift_card'] = 0;
        }

        if(isset($request->logo)){
            $this->validate($request, [
                'logo' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);
        }

        if(isset($request->favicon)){
            $this->validate($request, [
                'favicon' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
            ]);
        }

        if(isset($request->logo)) {
            $logo = $request->logo;
            if ($logo) {
                $ext = pathinfo($logo->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis") . '.' . $ext;

                // Save original file first
                $logo->move(public_path('frontend/images/'), $imageName);

                $manager = new ImageManager(new GdDriver());
                $image = $manager->read(public_path('frontend/images/') . $imageName);

                // Get original dimensions
                $originalWidth = $image->width();
                $originalHeight = $image->height();

                // Only resize if wider than 300px
                if ($originalWidth > 300) {
                    $newWidth = 300;
                    $newHeight = intval(($originalHeight / $originalWidth) * $newWidth);

                    // Resize explicitly
                    $image->resize($newWidth, $newHeight);
                }

                // Save resized image
                $image->save(public_path('frontend/images/') . $imageName, quality: 100);

                $data['logo'] = $imageName;
            }
        }
        if ($request->hasFile('qr_code')) {
            $qrCodes = $request->file('qr_code');  // সব ফাইল array আকারে আসবে

            // Get existing QR codes JSON
                $existingSetting = DB::table('ecommerce_settings')->first(); // get the row
                $existingQrCodes = json_decode($existingSetting->qr_code ?? '[]', true); // decode JSON

                foreach ($request->currency_id as $index => $currencyId) {
                    $qr_code = $qrCodes[$index] ?? null;

                    if ($qr_code) {
                        // Upload and resize new QR code
                        $ext = pathinfo($qr_code->getClientOriginalName(), PATHINFO_EXTENSION);
                        $imageName = date("YmdHis") . '_' . $index . '.' . $ext;
                        $qr_code->move(public_path('frontend/images/'), $imageName);

                        $manager = new ImageManager(new GdDriver());
                        $image = $manager->read(public_path('frontend/images/') . $imageName);

                        $originalWidth = $image->width();
                        $originalHeight = $image->height();

                        if ($originalWidth > 200) {
                            $newWidth = 200;
                            $newHeight = intval(($originalHeight / $originalWidth) * $newWidth);
                            $image->resize($newWidth, $newHeight);
                        }

                        $image->save(public_path('frontend/images/') . $imageName, quality: 100);
                    } else {
                        // No new QR code uploaded, use old QR code from JSON
                        $oldQr = collect($existingQrCodes)->firstWhere('id', $currencyId);
                        $imageName = $oldQr['qr_code'] ?? null;
                    }

                    $currencyData[] = [
                        'id'       => $currencyId,
                        'name'     => $request->currency_name[$index],
                        'code'     => $request->currency_code[$index],
                        'qr_code'  => $imageName,
                    ];
                }

                // Save $currencyData back as JSON
                DB::table('ecommerce_settings')->update([
                    'qr_code' => json_encode($currencyData),
                ]);


            // JSON কলামে সেভ
            $data['qr_code'] = json_encode($currencyData);
        }


        if(isset($request->favicon)) {
            $favicon = $request->favicon;
            if ($favicon) {
                $ext = pathinfo($favicon->getClientOriginalName(), PATHINFO_EXTENSION);
                $imageName = date("Ymdhis") . '.' . $ext;
                //return $imageName;
                $favicon->move(public_path('frontend/images/'), $imageName);

                $manager = new ImageManager(new GdDriver());
                $image = $manager->read(public_path('frontend/images/'). $imageName);
                $image->cover(50, 50)->save(public_path('frontend/images/'). $imageName, 100);

                $data['favicon'] = $imageName;
            }
        }

        if(isset($request->checkout_pages))
            $data['checkout_pages'] = json_encode($request->checkout_pages);
        else
            $data['checkout_pages'] = json_encode([]);

        $setting = DB::table('ecommerce_settings')->first();
        $oldHomePage = (int) ($setting->home_page ?? 0);
        if(isset($setting->id)){
            DB::table('ecommerce_settings')->where('id', 1)->update($data);
        }else{
            DB::table('ecommerce_settings')->insert($data);
        }

        Session::flash('message', 'Settings updated successfully.');
        Session::flash('type', 'success');

        $homepageCache->forgetSettings();
        if ($oldHomePage) {
            $homepageCache->forgetPage($oldHomePage);
        }
        $homepageCache->forgetConfiguredHomepage((int) ($data['home_page'] ?? 0));

        return redirect()->back();
    }

    public function gateway()
    {
        $payment_gateways = DB::table('external_services')->where('type','payment')->get();

        return view ('ecommerce::backend.settings.payment-gateways', compact('payment_gateways'));
    }

    public function gatewayUpdate(Request $request, HomepageCacheService $homepageCache)
    {
        $gateways = DB::table('external_services')->where('type','payment')->get();

        $pgs = $request->input('pg_name');
        $actives = $request->input('active');

        foreach($pgs as $index=>$pg){
            $gateway = $gateways->where('name',$pg)->first();
            $lines = explode(';',$gateway->details);
            $keys = explode(',', $lines[0]);

            $vals = [];
            foreach($keys as $key){
                $para = $pg.'_'.str_replace(' ','_',$key);
                $val = $request->$para;
                array_push($vals,$val);
            }
            $lines[1] = implode(',',$vals);
            $details = $lines[0].';'.$lines[1];

            DB::table('external_services')->where('name',$pg)->update(['details'=>$details,'active'=>$actives[$index]]);
        }

        Session::flash('message', 'Payment gateways updated successfully.');
        Session::flash('type', 'success');

        $homepageCache->forgetSettings();

        return redirect()->back();
    }

    public function themeSettings()
    {
        $settings = DB::table('ecommerce_settings')->first();
        $themes = $this->availableThemes();

        return view('ecommerce::backend.settings.theme', compact('settings', 'themes'));
    }

    public function updateTheme(Request $request, HomepageCacheService $homepageCache)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        $availableThemeSlugs = collect($this->availableThemes())->pluck('slug')->all();

        $this->validate($request, [
            'theme' => ['required', 'string', 'in:' . implode(',', $availableThemeSlugs)]
        ]);

        $setting = DB::table('ecommerce_settings')->first();
        if(isset($setting->id)){
            DB::table('ecommerce_settings')->where('id', 1)->update([
                'theme' => $request->theme
            ]);
        }else{
            DB::table('ecommerce_settings')->insert([
                'theme' => $request->theme
            ]);
        }

        Session::flash('message', 'Theme activated successfully.');
        Session::flash('type', 'success');

        $homepageCache->forgetSettings();
        $homepageCache->forgetConfiguredHomepage();

        return redirect()->back();
    }

    private function availableThemes(): array
    {
        $themesPath = module_path('Ecommerce', 'Resources/views/frontend/themes');
        $directories = is_dir($themesPath) ? array_filter(glob($themesPath . '/*') ?: [], 'is_dir') : [];

        $themes = [];
        foreach ($directories as $dir) {
            $slug = basename($dir);
            $manifestPath = $dir . DIRECTORY_SEPARATOR . 'theme.json';
            $manifest = [];

            if (is_file($manifestPath)) {
                $decoded = json_decode((string) file_get_contents($manifestPath), true);
                $manifest = is_array($decoded) ? $decoded : [];
            }

            $themes[] = [
                'slug' => $slug,
                'name' => $manifest['name'] ?? ucfirst(str_replace(['-', '_'], ' ', $slug)),
                'description' => $manifest['description'] ?? '',
                'version' => $manifest['version'] ?? '1.0',
                'industry' => $manifest['industry'] ?? null,
                'preview' => is_array($manifest['preview'] ?? null) ? $manifest['preview'] : [],
            ];
        }

        if (empty($themes)) {
            $themes[] = [
                'slug' => 'default',
                'name' => 'Default Theme',
                'description' => 'Default SalePro eCommerce storefront theme.',
                'version' => '1.0',
                'industry' => null,
                'preview' => [],
            ];
        }

        usort($themes, function (array $a, array $b): int {
            if ($a['slug'] === 'default') {
                return -1;
            }
            if ($b['slug'] === 'default') {
                return 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $themes;
    }
}
