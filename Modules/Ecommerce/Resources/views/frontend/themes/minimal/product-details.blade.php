@extends('ecommerce::frontend.themes.minimal.layout.main')

@php
    $productImageUrl = app(\Modules\Ecommerce\Services\DemoMediaResolver::class)->productImageUrl($product);
    $genericCartSupported = app(\Modules\Ecommerce\Services\StorefrontProductService::class)->supportsGenericCart($product);
    $productAvailability = $product->storefront_availability
        ?? app(\Modules\Ecommerce\Services\StorefrontAvailabilityService::class)->decorate($product);
@endphp

@section('title') {{ $product->name }} @endsection

@section('content')
<div class="bg-white">
    <div class="max-w-2xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:max-w-7xl lg:px-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-x-8 lg:items-start">

            <!-- Image gallery -->
            <div class="flex flex-col-reverse">
                <div class="hidden mt-6 w-full max-w-2xl mx-auto sm:block lg:max-w-none">
                    <!-- Additional images could go here -->
                </div>
                <div class="w-full aspect-w-1 aspect-h-1 bg-gray-100 rounded-none overflow-hidden shadow-none">
                    @if($productImageUrl)
                    <img src="{{ $productImageUrl }}" alt="{{ $product->name }}" class="w-full h-full object-center object-contain">
                    @endif
                </div>
            </div>

            <!-- Product info -->
            <div class="mt-10 px-4 sm:px-0 sm:mt-16 lg:mt-0">
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900">{{ $product->name }}</h1>

                <div class="mt-3">
                    <h2 class="sr-only">Product information</h2>
                    <p class="text-3xl text-gray-900 font-bold">{{ $currency->symbol ?? $currency->code }} {{ number_format($product->selling_price, 2) }}</p>
                </div>

                <!-- Reviews -->
                @if(isset($average_rating) && $average_rating > 0)
                <div class="mt-3">
                    <h3 class="sr-only">Reviews</h3>
                    <div class="flex items-center">
                        <div class="flex items-center text-yellow-400">
                            <!-- Star icon -->
                            <svg class="h-5 w-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="ml-2 text-sm text-gray-600 font-medium">{{ number_format($average_rating, 1) }} out of 5</span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="mt-6">
                    <h3 class="sr-only">Description</h3>
                    <div class="text-base text-gray-700 space-y-6 prose">
                        {!! $product->product_details !!}
                    </div>
                </div>

                <div class="mt-10 flex sm:flex-col1">
                    @if($productAvailability['available'])
                    <form action="{{ route('addToCart') }}" method="POST" class="minimal-cart-form w-full flex flex-col gap-3" data-success-url="{{ route('cart') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="number" name="qty" value="1" min="1"
                            @if(!$productAvailability['requires_variant'] && $productAvailability['available_qty'] !== null) max="{{ $productAvailability['available_qty'] }}" @endif
                            class="w-24 border border-gray-300 py-3 px-3" aria-label="{{ __('db.Quantity') }}">
                        @if($product->is_variant == 1 && $variant->isNotEmpty())
                        <select name="variant_id" required class="w-full max-w-xs border border-gray-300 py-3 px-3" aria-label="{{ __('db.Variant') }}">
                            <option value="">{{ __('db.Please select a variant') }}</option>
                            @foreach($variant as $variantRow)
                            <option value="{{ $variantRow->id }}" data-max="{{ $variantRow->storefront_available_qty }}"
                                @disabled(!$variantRow->storefront_is_available)>{{ $variantRow->name }}</option>
                            @endforeach
                        </select>
                        @endif
                        <button type="submit" @disabled(!$genericCartSupported || ($productAvailability['requires_variant'] && $variant->isEmpty())) class="max-w-xs flex-1 bg-black border border-black rounded-none py-4 px-8 flex items-center justify-center text-sm font-semibold uppercase tracking-widest text-white hover:bg-white hover:text-black transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-50 focus:ring-gray-900 sm:w-full transition-colors disabled:opacity-50">
                            {{ __('db.Add to cart') }}
                        </button>
                    </form>
                    @else
                        <p class="storefront-unavailable text-sm text-red-600" data-unavailable-reason="{{ $productAvailability['reason'] }}">
                            {{ $productAvailability['reason'] === \Modules\Ecommerce\Services\StorefrontAvailabilityService::REASON_OUT_OF_STOCK
                                ? __('db.Out of stock')
                                : __('db.Ecommerce product unavailable') }}
                        </p>
                    @endif
                </div>
                <p id="minimal-cart-message" class="hidden mt-3 text-sm text-red-600" role="alert"></p>
            </div>
        </div>

        <!-- Related Products -->
        @if(isset($related_products) && count($related_products) > 0)
        <section aria-labelledby="related-heading" class="mt-24">
            <h2 id="related-heading" class="text-2xl font-extrabold tracking-tight text-gray-900">You may also like</h2>
            <div class="mt-6 grid grid-cols-1 gap-y-10 gap-x-6 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
                @foreach($related_products->take(4) as $related)
                @php($relatedImageUrl = app(\Modules\Ecommerce\Services\DemoMediaResolver::class)->productImageUrl($related))
                <a href="{{ route('ecommerce.product.show', ['product_name' => $related->slug, 'product_id' => $related->id]) }}" class="group relative">
                    <div class="w-full min-h-80 bg-gray-200 aspect-w-1 aspect-h-1 rounded-none overflow-hidden group-hover:opacity-75 lg:h-80 lg:aspect-none">
                        @if($relatedImageUrl)<img src="{{ $relatedImageUrl }}" alt="{{ $related->name }}" class="w-full h-full object-center object-contain lg:w-full lg:h-full">@endif
                    </div>
                    <div class="mt-4 flex justify-between">
                        <div>
                            <h3 class="text-sm text-gray-700 font-medium">
                                <span aria-hidden="true" class="absolute inset-0"></span>
                                {{ $related->name }}
                            </h3>
                        </div>
                        <p class="text-sm font-bold text-gray-900">{{ $currency->symbol ?? $currency->code }} {{ number_format($related->selling_price, 2) }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </section>
        @endif
    </div>
</div>
@endsection
