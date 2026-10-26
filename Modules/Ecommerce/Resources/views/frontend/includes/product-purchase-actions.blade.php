@php
    $availability = $product->storefront_availability
        ?? app(\Modules\Ecommerce\Services\StorefrontAvailabilityService::class)->decorate($product);
    $unavailableMessage = match ($availability['reason']) {
        \Modules\Ecommerce\Services\StorefrontAvailabilityService::REASON_OUT_OF_STOCK => __('db.Out of stock'),
        \Modules\Ecommerce\Services\StorefrontAvailabilityService::REASON_CONFIGURATION_REQUIRED => __('db.Ecommerce configuration required'),
        default => __('db.Ecommerce product unavailable'),
    };
@endphp

@if($availability['available'])
    @if($availability['requires_variant'])
        <div class="text-center">
            <a href="{{ route('ecommerce.product.show', ['product_name' => $product->slug, 'product_id' => $product->id]) }}"
                class="button style1">{{ __('db.Add to cart') }}</a>
        </div>
    @else
        <form class="d-flex justify-content-between storefront-cart-form" method="post"
            action="{{ route('addToCart') }}" id="add_to_cart_{{ $product->id }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <div class="d-flex align-items-center">
                <div class="input-qty">
                    <button type="button" class="quantity-left-minus">
                        <i class="material-symbols-outlined">remove</i>
                    </button>
                    <input type="number" name="qty" class="input-number" value="1" min="1"
                        @if($availability['available_qty'] !== null) max="{{ $availability['available_qty'] }}" @endif>
                    <button type="button" class="quantity-right-plus">
                        <i class="material-symbols-outlined">add</i>
                    </button>
                </div>
            </div>
            <button data-id="{{ $product->id }}" type="submit" class="button style1 add-to-cart">
                <span class="material-symbols-outlined">shopping_bag</span>
            </button>
        </form>
    @endif
@else
    <span class="storefront-unavailable"
        data-unavailable-reason="{{ $availability['reason'] }}">{{ $unavailableMessage }}</span>
@endif
