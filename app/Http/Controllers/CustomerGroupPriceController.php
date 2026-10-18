<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\CustomerGroupProductPrice;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

// Price list per customer group: view and edit the fixed price of many products at once.
// Uses the same customer_group_product_prices rows as the group price table on the product form.
class CustomerGroupPriceController extends Controller
{
    private function allowed()
    {
        return Role::find(Auth::user()->role_id)->hasPermissionTo('products-edit');
    }

    public function index()
    {
        if(!$this->allowed())
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));

        $lims_customer_group_list = CustomerGroup::where('is_active', true)->get();
        $lims_category_list = Category::where('is_active', true)->orderBy('name')->get();
        $lims_brand_list = Brand::where('is_active', true)->orderBy('title')->get();
        return view('backend.customer_group_price.index', compact('lims_customer_group_list', 'lims_category_list', 'lims_brand_list'));
    }

    public function data(Request $request)
    {
        if(!$this->allowed())
            return response()->json(['error' => __('db.Sorry! You are not allowed to access this module')], 403);

        $customer_group_id = (int) $request->input('customer_group_id');
        $per_page = 50;

        $q = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('customer_group_product_prices as gp', function ($join) use ($customer_group_id) {
                $join->on('gp.product_id', '=', 'products.id')
                     ->where('gp.customer_group_id', $customer_group_id);
            })
            ->where('products.is_active', true)
            ->select('products.id', 'products.name', 'products.code', 'products.cost', 'products.price',
                'categories.name as category', 'brands.title as brand', 'gp.price as group_price');

        if($search = trim((string) $request->input('search'))) {
            $q->where(function ($w) use ($search) {
                $w->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.code', 'like', "%{$search}%");
            });
        }
        if($request->input('category_id'))
            $q->where('products.category_id', $request->input('category_id'));
        if($request->input('brand_id'))
            $q->where('products.brand_id', $request->input('brand_id'));
        if($request->input('only_priced') == 1)
            $q->whereNotNull('gp.price');
        elseif($request->input('only_priced') == 2)
            $q->whereNull('gp.price');

        $products = $q->orderBy('products.name')->paginate($per_page);

        return response()->json([
            'rows' => $products->items(),
            'page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'total' => $products->total(),
            'priced_count' => CustomerGroupProductPrice::where('customer_group_id', $customer_group_id)->count(),
        ]);
    }

    // prices: {product_id: price}; an empty price removes the group's price for that product
    public function save(Request $request)
    {
        if(!$this->allowed())
            return response()->json(['error' => __('db.Sorry! You are not allowed to access this module')], 403);

        $this->validate($request, [
            'customer_group_id' => 'required|exists:customer_groups,id',
            'prices' => 'required|array',
            'prices.*' => 'nullable|numeric|min:0',
        ]);

        $customer_group_id = $request->input('customer_group_id');
        $product_ids = DB::table('products')->whereIn('id', array_keys($request->input('prices')))->pluck('id')->all();
        $saved = 0;
        $removed = 0;

        DB::transaction(function () use ($request, $customer_group_id, $product_ids, &$saved, &$removed) {
            foreach ($request->input('prices') as $product_id => $price) {
                if(!in_array($product_id, $product_ids))
                    continue;
                if($price === null || $price === '') {
                    $removed += CustomerGroupProductPrice::where(['customer_group_id' => $customer_group_id, 'product_id' => $product_id])->delete();
                }
                else {
                    CustomerGroupProductPrice::updateOrCreate(
                        ['customer_group_id' => $customer_group_id, 'product_id' => $product_id],
                        ['price' => $price]
                    );
                    $saved++;
                }
            }
        });

        return response()->json(['saved' => $saved, 'removed' => $removed]);
    }
}
