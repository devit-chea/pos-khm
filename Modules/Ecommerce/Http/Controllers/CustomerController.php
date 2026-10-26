<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\User;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\PaymentWithCreditCard;
use App\Models\PaymentWithGiftCard;
use App\Models\PaymentWithPaypal;
use App\Models\Product_Sale;
use App\Models\Product;
use Auth;
use Session;
use DB;
use Modules\Ecommerce\Services\StorefrontOrderService;
use Modules\Ecommerce\Services\StorefrontAvailabilityService;

class CustomerController extends Controller
{
    public function index()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        return view('ecommerce::frontend.dashboard', compact('customer'));
    }

	public function orders()
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if($customer){
            $sales = Sale::select('id','reference_no','grand_total','shipping_cost','coupon_discount','sale_status','created_at','currency_id','exchange_rate')->where('customer_id',$customer->id)->orderBy('created_at','DESC')->get();

            return view('ecommerce::frontend.orders', compact('customer', 'sales'));
        }

        return view('ecommerce::frontend.orders');
    }

    public function orderDetails($id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $sale = Sale::select('id','reference_no','grand_total','shipping_cost','coupon_discount','sale_status','created_at')->where('id',$id)->where('customer_id',$customer->id)->first();
        $products = Product_Sale::select('products.name', 'product_sales.qty', 'product_sales.net_unit_price')->join('products','products.id','=','product_sales.product_id')->where('Sale_id', $id)->get();
        return view('ecommerce::frontend.order-details', compact('products', 'sale'));
    }

    public function orderCancel($id, StorefrontOrderService $orders)
    {
        $customer = $this->checkoutCustomer();
        $orders->cancelForCustomer((int) $id, (int) $customer->id);

        return redirect()->back()->with('message', 'You have canceled your order.');
    }

    public function wishlist()
    {
        $customer = Customer::select('id','wishlist','user_id')->where('user_id', Auth::id())->first();
        if(isset($customer->wishlist)){
            $products = Product::whereIn('id', explode(',', $customer->wishlist))->get();
            app(StorefrontAvailabilityService::class)->decorateMany($products);
            return view('ecommerce::frontend.wishlist', compact('products'));
        }
        return view('ecommerce::frontend.wishlist');
    }

    public function addToWishlist($product_id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        if(isset($customer->wishlist)){
            if(in_array($product_id,explode(',',$customer->wishlist))){
                return response()->json('fail');
            }
            $customer->wishlist = $customer->wishlist.$product_id.',';
        } else {
            $customer->wishlist = $product_id.',';
        }
        $customer->save();

        return response()->json('success');
    }

    public function deleteFromWishlist($product_id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $customer->wishlist = str_replace($product_id.',', '', $customer->wishlist);
        $customer->save();

        return response()->json('success');
    }

    public function address()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $addresses = DB::table('customer_addresses')->where('customer_id',$customer->id)->get();
        return view('ecommerce::frontend.addresses', compact('customer','addresses'));
    }

    public function addressCreate(Request $request)
    {
        $customer = $this->checkoutCustomer();
        $data = $this->validatedAddress($request);
        $data['customer_id'] = $customer->id;

        DB::table('customer_addresses')->insert($data);

        Session::flash('message', 'Address inserted');
        Session::flash('type', 'success');

        return redirect()->back();

    }

    public function addressDefault($id)
    {
        $customer = $this->checkoutCustomer();
        $default = $this->ownedAddress($customer, (int) $id);

        DB::transaction(function () use ($customer, $default) {
            DB::table('customer_addresses')->where('customer_id', $customer->id)->update(['default' => 0]);
            DB::table('customer_addresses')->where('id', $default->id)->where('customer_id', $customer->id)->update(['default' => 1]);
            $customer->address = $default->address;
            $customer->city = $default->city;
            $customer->state = $default->state;
            $customer->country = $default->country;
            $customer->postal_code = $default->zip;
            $customer->save();
        });

        return redirect()->back();
    }

    public function addressEdit($id)
    {
        return response()->json($this->ownedAddress($this->checkoutCustomer(), (int) $id));
    }

    public function addressUpdate(Request $request)
    {
        $request->validate(['id' => ['required', 'integer', 'min:1']]);
        $customer = $this->checkoutCustomer();
        $address = $this->ownedAddress($customer, $request->integer('id'));
        $data = $this->validatedAddress($request);
        DB::table('customer_addresses')->where('id', $address->id)->where('customer_id', $customer->id)->update($data);
        $default = DB::table('customer_addresses')->where('id', $address->id)->where('customer_id', $customer->id)->where('default',1)->first();
        if(isset($default)){
            $customer->address = $default->address;
            $customer->city = $default->city;
            $customer->state = $default->state;
            $customer->country = $default->country;
            $customer->postal_code = $default->zip;
            $customer->save();
        }

        Session::flash('message', 'Address updated');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    public function addressDelete($id)
    {
        $customer = $this->checkoutCustomer();
        $address = $this->ownedAddress($customer, (int) $id);
        DB::table('customer_addresses')->where('id', $address->id)->where('customer_id', $customer->id)->delete();

        Session::flash('message', 'Address deleted');
        Session::flash('type', 'success');
        return redirect()->back();
    }

    private function checkoutCustomer(): Customer
    {
        abort_unless(Auth::check(), 403);
        $customer = Customer::where('user_id', Auth::id())->first();
        abort_unless($customer, 403);

        return $customer;
    }

    private function ownedAddress(Customer $customer, int $id): object
    {
        $address = DB::table('customer_addresses')
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();
        abort_unless($address, 404);

        return $address;
    }

    private function validatedAddress(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:50'],
        ]);

        return array_map(
            static fn ($value) => is_string($value) ? trim($value) : $value,
            $data
        );
    }

    public function accountDetails()
    {
        $customer = Customer::select('id')->where('user_id', Auth::id())->first();
        return view('ecommerce::frontend.account-details', compact('customer'));
    }

    public function updateAccountDetails(Request $request)
    {
        $this->validate($request, [
            'name'     => 'required',
            'email'    => 'required'
        ]);

        $id = auth()->user()->id;

        $user = User::find($id);
        $user->name = trim(htmlspecialchars($request->input('name')));
        $user->email = trim(htmlspecialchars($request->input('email')));
        $user->phone = trim(htmlspecialchars($request->input('phone')));
        $user->save();

        $customer = Customer::where('user_id',$id)->first();
        $customer->name = $user->name;
        $customer->email = $user->email;
        $customer->phone_number = $user->phone;
        $customer->save();

        Session::flash('message', 'Details updated');
        Session::flash('type', 'success');

        return redirect()->back();
    }
}
