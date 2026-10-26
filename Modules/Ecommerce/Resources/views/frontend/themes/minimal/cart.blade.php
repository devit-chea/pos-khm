@extends('ecommerce::frontend.themes.minimal.layout.main')

@section('title') Your Cart @endsection

@section('content')
<div class="bg-white">
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">Shopping Cart</h1>

        <div class="mt-12 lg:grid lg:grid-cols-12 lg:gap-x-12 lg:items-start">
            <section aria-labelledby="cart-heading" class="lg:col-span-7">
                <h2 id="cart-heading" class="sr-only">Items in your shopping cart</h2>

                <ul role="list" class="border-t border-b border-gray-200 divide-y divide-gray-200">
                    @forelse($cart as $key => $item)
                    <li class="flex py-6 sm:py-10">
                        <div class="flex-shrink-0">
                            @if(!empty($item['storefront_image_url']))
                            <img src="{{ $item['storefront_image_url'] }}" alt="{{ $item['name'] }}" class="w-24 h-24 rounded-none object-center object-contain sm:w-32 sm:h-32">
                            @else
                            <div class="w-24 h-24 rounded-none bg-gray-200 flex items-center justify-center sm:w-32 sm:h-32">
                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            @endif
                        </div>

                        <div class="ml-4 flex-1 flex flex-col justify-between sm:ml-6">
                            <div class="relative pr-9 sm:grid sm:grid-cols-2 sm:gap-x-6 sm:pr-0">
                                <div>
                                    <div class="flex justify-between">
                                        <h3 class="text-sm">
                                            <a href="{{ url('product/' . ($item['slug'] ?? '') . '/' . ($item['id'] ?? '')) }}" class="font-medium text-gray-700 hover:text-gray-800">{{ $item['name'] }}</a>
                                        </h3>
                                    </div>
                                    <p class="mt-1 text-sm font-medium text-gray-900">{{ $currency->symbol ?? $currency->code }} {{ number_format($item['total_price'], 2) }}</p>
                                </div>

                                <div class="mt-4 sm:mt-0 sm:pr-9">
                                    <form action="{{ route('updateCart') }}" method="POST" class="minimal-cart-form flex items-center gap-2" data-success-url="{{ route('cart') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                        @if(!empty($item['variant_id']))<input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">@endif
                                        <label for="quantity-{{ $key }}" class="sr-only">Quantity, {{ $item['name'] }}</label>
                                        <input type="number" name="product_qty" id="quantity-{{ $key }}" min="1" max="{{ $item['stock_unlimited'] ? \Modules\Ecommerce\Services\StorefrontProductService::MAX_CART_QUANTITY : $item['available_qty'] }}" value="{{ $item['qty'] }}" class="max-w-[100px] border border-gray-300 rounded-none py-1.5 px-3 text-base font-medium text-gray-700 text-left shadow-none focus:outline-none focus:ring-1 focus:ring-gray-500 focus:border-gray-500 sm:text-sm">
                                        <button type="submit" class="text-sm font-medium text-blue-600 hover:text-blue-500">Update</button>
                                    </form>

                                    <div class="absolute top-0 right-0">
                                        <form action="{{ route('removeFromCart') }}" method="POST" class="minimal-cart-form" data-success-url="{{ route('cart') }}">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                            @if(!empty($item['variant_id']))<input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">@endif
                                        <button type="submit" class="-m-2 p-2 inline-flex text-gray-400 hover:text-gray-500">
                                            <span class="sr-only">Remove</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="py-12 text-center">
                        <p class="text-gray-500 mb-6">Your cart is empty.</p>
                        <a href="{{ url('/shop') }}" class="inline-flex items-center px-4 py-2 border border-black shadow-none text-sm font-medium rounded-none text-white bg-black hover:bg-white hover:text-black transition-colors duration-300">
                            Continue Shopping
                        </a>
                    </li>
                    @endforelse
                </ul>

                <p id="minimal-cart-message" class="hidden mt-4 text-sm text-red-600" role="alert"></p>
            </section>

            <!-- Order summary -->
            <section aria-labelledby="summary-heading" class="mt-16 bg-gray-50 rounded-none px-4 py-6 sm:p-6 lg:p-8 lg:mt-0 lg:col-span-5">
                <h2 id="summary-heading" class="text-lg font-medium text-gray-900">Order summary</h2>

                <dl class="mt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600">Subtotal</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $currency->symbol ?? $currency->code }} {{ number_format($subTotal, 2) }}</dd>
                    </div>
                    <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                        <dt class="text-base font-medium text-gray-900">Order total</dt>
                        <dd class="text-base font-medium text-gray-900">{{ $currency->symbol ?? $currency->code }} {{ number_format($subTotal, 2) }}</dd>
                    </div>
                </dl>

                <div class="mt-6">
                    @if(count($cart) > 0)
                    <a href="{{ url('checkout') }}" class="w-full bg-black border border-black rounded-none shadow-none py-3 px-4 text-base font-medium text-white hover:bg-white hover:text-black transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-50 focus:ring-gray-900 text-center block transition-colors">Checkout</a>
                    @else
                    <button type="button" disabled class="w-full bg-gray-300 border border-black rounded-none shadow-none py-3 px-4 text-base font-medium text-white cursor-not-allowed text-center block">Checkout</button>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
