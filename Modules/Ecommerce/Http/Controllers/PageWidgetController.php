<?php

namespace Modules\Ecommerce\Http\Controllers;

use Modules\Ecommerce\Entities\Page;
use Modules\Ecommerce\Entities\PageWidgets;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\Request;
use Session;
use DB;
use Illuminate\Support\Facades\Validator;
use Modules\Ecommerce\Services\HomepageCacheService;

class PageWidgetController extends Controller
{
    use \App\Traits\TenantInfo;

    public function store(Request $request, HomepageCacheService $homepageCache)
    {
        // if(!config('app.user_verified')){
        //     return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        // }

        $this->validateWidgetRequest($request);

        $input = $request->except('image');

        $input['name'] = $input['widget_name'];

        if($input['name'] == 'category-slider-widget'){
            if(isset($request->category_slider_ids) && is_array($request->category_slider_ids)) {
                $input['category_slider_ids'] = implode(',',$request->category_slider_ids);
            }else{
                $input['category_slider_ids'] = is_string($request->category_slider_ids) ? $request->category_slider_ids : NULL;
            }
        }

        if($input['name'] == 'brand-slider-widget'){
            if(isset($request->brand_slider_ids) && is_array($request->brand_slider_ids)) {
                $input['brand_slider_ids'] = implode(',',$request->brand_slider_ids);
            }else{
                $input['brand_slider_ids'] = is_string($request->brand_slider_ids) ? $request->brand_slider_ids : NULL;
            }
        }

        if($input['name'] == 'tab-product-collection-widget'){
            if(isset($request->tab_product_collection_ids) && is_array($request->tab_product_collection_ids)) {
                $input['tab_product_collection_id'] = implode(',',$request->tab_product_collection_ids);
            }else{
                $input['tab_product_collection_id'] = is_string($request->tab_product_collection_ids) ? $request->tab_product_collection_ids : NULL;
            }
        }

        $widget = PageWidgets::create($input);

        $homepageCache->forgetPage((int) $widget->page_id, $request->getHost());

        return $widget->id;

    }

    public function update(Request $request, HomepageCacheService $homepageCache)
    {
        // if(!config('app.user_verified')){
        //     return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        // }

        $this->validateWidgetRequest($request, true);

        $data = $request->except('three_c_banner_image1','three_c_banner_image2','three_c_banner_image3','two_c_banner_image1','two_c_banner_image2','one_c_banner_image1');

        if($data['widget_name'] == 'category-slider-widget'){
            if(isset($request->category_slider_ids) && is_array($request->category_slider_ids)) {
                $data['category_slider_ids'] = implode(',',$request->category_slider_ids);
            } else if (isset($request->category_slider_ids)) {
                $data['category_slider_ids'] = $request->category_slider_ids;
            } else {
                $data['category_slider_ids'] = NULL;
            }
        }

        if($data['widget_name'] == 'brand-slider-widget'){
            if(isset($request->brand_slider_ids) && is_array($request->brand_slider_ids)) {
                $data['brand_slider_ids'] = implode(',',$request->brand_slider_ids);
            } else if (isset($request->brand_slider_ids)) {
                $data['brand_slider_ids'] = $request->brand_slider_ids;
            } else {
                $data['brand_slider_ids'] = NULL;
            }
        }

        if($data['widget_name'] == 'tab-product-collection-widget'){
            if(isset($request->tab_product_collection_ids) && is_array($request->tab_product_collection_ids)) {
                $data['tab_product_collection_id'] = implode(',',$request->tab_product_collection_ids);
            } else if (isset($request->tab_product_collection_ids)) {
                $data['tab_product_collection_id'] = $request->tab_product_collection_ids;
            } else {
                $data['tab_product_collection_id'] = NULL;
            }
        }

        $widget = PageWidgets::find($request->id);
        $widget->update($data);

        if($request->widget_name == 'image-slider-widget'){
            if (!file_exists(public_path('frontend/images/slider_widget'))) {
                mkdir(public_path('frontend/images/slider_widget'), 0777, true);
            }

            if (!file_exists(public_path('frontend/images/slider_widget/desktop'))) {
                mkdir(public_path('frontend/images/slider_widget/desktop'), 0777, true);
            }

            if (!file_exists(public_path('frontend/images/slider_widget/mobile'))) {
                mkdir(public_path('frontend/images/slider_widget/mobile'), 0777, true);
            }

            if(isset($request->slide_lg_prev)){
                $data['slider_images'] = $request->slide_lg_prev;
                $data['slider_links'] = $request->link_prev;
            }else{
                $data['slider_links'] = [];
                $data['slider_images'] = [];
            }

            if(isset($request->slide_lg)){
                foreach($request->slide_lg as $key=>$link) {

                    $image_name = date('Ymdhis');

                    if(isset($request->slide_lg[$key])) {
                        $slide_lg = $request->slide_lg[$key];
                        if(!is_string($slide_lg)){
                            $ext = pathinfo($slide_lg->getClientOriginalName(), PATHINFO_EXTENSION);
                            $imageName = $image_name . $key;

                            if(!config('database.connections.saleprosaas_landlord')) {
                                $imageName = $imageName . '.' . $ext;
                            }
                            else {
                                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                            }

                            $slide_lg->move(public_path('frontend/images/slider_widget/desktop'), $imageName);


                            $data['slider_images'][]= $imageName;
                        }

                    }

                    if(isset($request->slide_m[$key])) {
                        $slide_m = $request->slide_m[$key];

                        if(!is_string($slide_m)){
                            $ext = pathinfo($slide_m->getClientOriginalName(), PATHINFO_EXTENSION);
                            $imageName = $image_name . $key;

                            if(!config('database.connections.saleprosaas_landlord')) {
                                $imageName = $imageName . '.' . $ext;
                            }
                            else {
                                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
                            }

                            $slide_m->move(public_path('frontend/images/slider_widget/mobile'), $imageName);
                        }
                    }

                    $data['slider_links'][] = $request->link[$key];

                }
            }
            $data['slider_links'] = implode(',',$data['slider_links']);
            $data['slider_images'] = implode(',',$data['slider_images']);

            $widget->slider_images = $data['slider_images'];
            $widget->slider_links = $data['slider_links'];
            $widget->update($data);
            $homepageCache->forgetPage((int) $widget->page_id, $request->getHost());
            return $widget;
        }

        if(isset($request->three_c_banner_image1))
        {
            if (!file_exists(public_path('frontend/images/banners/'))) {
                mkdir(public_path('frontend/images/banners/'), 0777, true);
            }
            $three_c_banner_image1 = $request->three_c_banner_image1;
            $ext = pathinfo($three_c_banner_image1->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis") . 1;
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
            }

            $three_c_banner_image1->move(public_path('frontend/images/banners'), $imageName);

            $manager = new ImageManager(Driver::class);
            $image = $manager->read(public_path('frontend/images/banners/'). $imageName);
            $image->cover(500, 200)->save(public_path('frontend/images/banners/'). $imageName, 100);

            $widget->three_c_banner_image1 = $imageName;
            $widget->save();
        }

        if(isset($request->three_c_banner_image2))
        {
            if (!file_exists(public_path('frontend/images/banners/'))) {
                mkdir(public_path('frontend/images/banners/'), 0777, true);
            }
            $three_c_banner_image2 = $request->three_c_banner_image2;
            $ext = pathinfo($three_c_banner_image2->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis") . 2;
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
            }

            $three_c_banner_image2->move(public_path('frontend/images/banners'), $imageName);

            $manager = new ImageManager(Driver::class);
            $image = $manager->read(public_path('frontend/images/banners/'). $imageName);
            $image->cover(500, 200)->save(public_path('frontend/images/banners/'). $imageName, 100);

            $widget->three_c_banner_image2 = $imageName;
            $widget->save();
        }

        if(isset($request->three_c_banner_image3))
        {
            if (!file_exists(public_path('frontend/images/banners/'))) {
                mkdir(public_path('frontend/images/banners/'), 0777, true);
            }
            $three_c_banner_image3 = $request->three_c_banner_image3;
            $ext = pathinfo($three_c_banner_image3->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis") . 3;
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
            }

            $three_c_banner_image3->move(public_path('frontend/images/banners'), $imageName);

            $manager = new ImageManager(Driver::class);
            $image = $manager->read(public_path('frontend/images/banners/'). $imageName);
            $image->cover(500, 200)->save(public_path('frontend/images/banners/'). $imageName, 100);

            $widget->three_c_banner_image3 = $imageName;
            $widget->save();
        }

        if(isset($request->two_c_banner_image1))
        {
            if (!file_exists(public_path('frontend/images/banners/'))) {
                mkdir(public_path('frontend/images/banners/'), 0777, true);
            }
            $two_c_banner_image1 = $request->two_c_banner_image1;
            $ext = pathinfo($two_c_banner_image1->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis") . 1;
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
            }

            $two_c_banner_image1->move(public_path('frontend/images/banners'), $imageName);

            $manager = new ImageManager(Driver::class);
            $image = $manager->read(public_path('frontend/images/banners/'). $imageName);
            $image->cover(700, 150)->save(public_path('frontend/images/banners/'). $imageName, 100);

            $widget->two_c_banner_image1 = $imageName;
            $widget->save();
        }

        if(isset($request->two_c_banner_image2))
        {
            if (!file_exists(public_path('frontend/images/banners/'))) {
                mkdir(public_path('frontend/images/banners/'), 0777, true);
            }
            $two_c_banner_image2 = $request->two_c_banner_image2;
            $ext = pathinfo($two_c_banner_image2->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis") . 2;
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
            }

            $two_c_banner_image2->move(public_path('frontend/images/banners'), $imageName);

            $manager = new ImageManager(Driver::class);
            $image = $manager->read(public_path('frontend/images/banners/'). $imageName);
            $image->cover(700, 150)->save(public_path('frontend/images/banners/'). $imageName, 100);

            $widget->two_c_banner_image2 = $imageName;
            $widget->save();
        }

        if(isset($request->one_c_banner_image1))
        {
            if (!file_exists(public_path('frontend/images/banners/'))) {
                mkdir(public_path('frontend/images/banners/'), 0777, true);
            }
            if (!file_exists(public_path('frontend/images/banners/desktop/'))) {
                mkdir(public_path('frontend/images/banners/desktop/'), 0777, true);
            }
            if (!file_exists(public_path('frontend/images/banners/mobile/'))) {
                mkdir(public_path('frontend/images/banners/mobile/'), 0777, true);
            }
            $one_c_banner_image1 = $request->one_c_banner_image1;
            $ext = pathinfo($one_c_banner_image1->getClientOriginalName(), PATHINFO_EXTENSION);
            $imageName = date("Ymdhis") . rand(0,3);
            if(!config('database.connections.saleprosaas_landlord')) {
                $imageName = $imageName . '.' . $ext;
            }
            else {
                $imageName = $this->getTenantId() . '_' . $imageName . '.' . $ext;
            }

            $one_c_banner_image1->move(public_path('frontend/images/banners/desktop'), $imageName);

            $manager = new ImageManager(Driver::class);
            $image = $manager->read(public_path('frontend/images/banners/desktop/'). $imageName);
            $image->cover(1900, 900)->save(public_path('frontend/images/banners/desktop/'). $imageName, 100);

            if(isset($request->one_c_banner_image1_mobile))
            {
                $one_c_banner_image1_mobile = $request->one_c_banner_image1_mobile;
                $ext = pathinfo($one_c_banner_image1_mobile->getClientOriginalName(), PATHINFO_EXTENSION);

                $one_c_banner_image1_mobile->move(public_path('frontend/images/banners/mobile'), $imageName);

                $manager = new ImageManager(Driver::class);
                $image = $manager->read(public_path('frontend/images/banners/mobile/'). $imageName);
                $image->cover(600, 750)->save(public_path('frontend/images/banners/mobile/'). $imageName, 100);

            }

            $widget->one_c_banner_image1 = $imageName;
            $widget->save();
        }

        $homepageCache->forgetPage((int) $widget->page_id, $request->getHost());

        return response(['id' => $widget->id]);
    }

    public function activation(Request $request, PageWidgets $widget, HomepageCacheService $homepageCache)
    {
        $validated = $request->validate([
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        abort_unless((int) $widget->page_id === (int) $validated['page_id'], 422);

        $widget->is_active = (bool) $validated['is_active'];
        $widget->save();
        $homepageCache->forgetPage((int) $widget->page_id, $request->getHost());

        return response()->json([
            'id' => $widget->id,
            'is_active' => $widget->is_active,
            'message' => __('db.Widget status updated'),
        ]);
    }

    public function order(Request $request, HomepageCacheService $homepageCache)
    {
        $validated = $request->validate([
            'page_id'    => ['required', 'integer', 'exists:pages,id'],
            'widget_ids' => ['nullable', 'array'],
            'widget_ids.*' => ['integer', 'distinct'],
        ]);

        $pageId = (int) $validated['page_id'];

        // যদি কোনো widget না থাকে তাহলে কিছু করার নেই
        if (empty($validated['widget_ids'])) {
            return response()->json([
                'message' => __('db.Widget order updated'),
            ]);
        }

        $widgetIds = array_map('intval', $validated['widget_ids']);
        $storedIds = PageWidgets::where('page_id', $pageId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $expectedIds = $storedIds;
        $submittedIds = $widgetIds;
        sort($expectedIds);
        sort($submittedIds);
        abort_unless($expectedIds === $submittedIds, 422);

        DB::transaction(function () use ($pageId, $widgetIds) {
            PageWidgets::where('page_id', $pageId)->lockForUpdate()->get();
            foreach ($widgetIds as $position => $widgetId) {
                PageWidgets::where('page_id', $pageId)
                    ->where('id', $widgetId)
                    ->update(['order' => $position + 1]);
            }
        });

        $homepageCache->forgetPage($pageId, $request->getHost());

        return response()->json(['message' => __('db.Widget order updated')]);
    }

    public function destroy(Request $request, PageWidgets $widget, HomepageCacheService $homepageCache)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        $request->validate(['page_id' => ['required', 'integer', 'exists:pages,id']]);
        abort_unless((int) $widget->page_id === (int) $request->integer('page_id'), 422);
        $pageId = (int) $widget->page_id;
        $widget->delete();
        $homepageCache->forgetPage($pageId, $request->getHost());

        // if(isset($widget->icon))
        // {
        //     $this->fileDelete('frontend/images/features/', $widget->icon);
        // }

        return redirect()->back();
    }

    public function delete($id, HomepageCacheService $homepageCache)
    {
        if(!config('app.user_verified')){
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        }

        $widget = PageWidgets::findOrFail($id);
        $pageId = (int) $widget->page_id;
        $widget->delete();
        $homepageCache->forgetPage($pageId);

        return redirect()->back();
    }

    private function validateWidgetRequest(Request $request, bool $updating = false): void
    {
        $rules = [
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'widget_name' => ['required', 'string'],
            'order' => ['required', 'integer', 'min:0'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
        if ($updating) {
            $rules['id'] = ['required', 'integer', 'exists:page_widgets,id'];
        }
        $validated = Validator::make($request->all(), $rules)->validate();

        abort_unless(in_array($validated['widget_name'], $this->supportedWidgets(), true), 422);

        if ($updating) {
            $widget = PageWidgets::findOrFail($validated['id']);
            abort_unless((int) $widget->page_id === (int) $validated['page_id'], 422);
            abort_unless($widget->name === $validated['widget_name'], 422);
        }
    }

    private function supportedWidgets(): array
    {
        $theme = DB::table('ecommerce_settings')->value('theme') ?: 'default';
        $path = module_path('Ecommerce', "Resources/views/frontend/themes/{$theme}/theme.json");
        $data = is_file($path) ? json_decode(file_get_contents($path), true) : [];

        return is_array($data['supported_widgets'] ?? null) ? $data['supported_widgets'] : [];
    }
}
