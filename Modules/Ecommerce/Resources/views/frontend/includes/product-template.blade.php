@php
    $storefrontPrice = app(\Modules\Ecommerce\Services\StorefrontPricingService::class)->resolve($product);
    $productImageUrl = app(\Modules\Ecommerce\Services\DemoMediaResolver::class)->productImageUrl($product, 'large');
@endphp
<div class="single-product-wrapper">
    <div class="single-product-item">
        @if($storefrontPrice['promotion_active'] && $storefrontPrice['regular_price'] > 0)
        <div class="product-promo-text style1 bg-danger">
            <span>-{{ round(($storefrontPrice['regular_price'] - $storefrontPrice['selling_price']) / $storefrontPrice['regular_price'] * 100) }}%</span>
        </div>
        @endif
        <a href="{{url('product')}}/{{$product->slug}}/{{$product->id}}"  class="view-details">
            @if($productImageUrl)
            <img loading="lazy" class="product-img" src="{{ $productImageUrl }}" alt="{{ $product->name }}">
            @else
            <img loading="lazy" src="https://dummyimage.com/300x300/e5e8ec/e5e8ec&text={{ $product->name }}" alt="{{ $product->name }}">
            @endif
        </a>
        @if(isset($ecommerce_setting->online_order) && $ecommerce_setting->online_order != 0)
        <div class="product-overlay">
            @if(in_array($product->id,explode(',',$wishlist)))
            <a><span style="color: var(--theme-color);" class="material-symbols-outlined">favorite</span></a>
            @else
            <a data-id="{{$product->id}}" class="add-to-wishlist"><span class="material-symbols-outlined">favorite</span></a>
            @endif
            <a class="quick-view" data-id="{{$product->id}}" data-toggle="modal" data-target="#detailsModal" title="quick view"><span class="material-symbols-outlined">zoom_in</span></a>
        </div>
        @endif
    </div>

    <div class="product-details">
        <a class="product-name" href="{{url('product')}}/{{$product->slug}}/{{$product->id}}">
            {!! ucwords($product->name) !!}
            @if(isset($product->unit))
            <span class="product-quantity">({{ $product->unit->unit_name }})</span>
            @endif
        </a>
        <div class="product-price">
            @if($storefrontPrice['promotion_active'])
            <span class="price" data-price="{{ $storefrontPrice['selling_price'] }}">
                @if($general_setting->currency_position == 'prefix')
                {{$currency->symbol ?? $currency->code}} {{ $storefrontPrice['selling_price'] }}
                @else
                {{ $storefrontPrice['selling_price'] }} {{$currency->symbol ?? $currency->code}}
                @endif
            </span>
            <span class="old-price" data-old_price="{{ $storefrontPrice['regular_price'] }}">
                @if($general_setting->currency_position == 'prefix')
                {{$currency->symbol ?? $currency->code}} {{ $storefrontPrice['regular_price'] }}
                @else
                {{ $storefrontPrice['regular_price'] }} {{$currency->symbol ?? $currency->code}}
                @endif
            </span>
            @else
            <span class="price" data-price="{{ $storefrontPrice['selling_price'] }}">
                @if($general_setting->currency_position == 'prefix')
                {{$currency->symbol ?? $currency->code}} {{ $storefrontPrice['selling_price'] }}
                @else
                {{ $storefrontPrice['selling_price'] }} {{$currency->symbol ?? $currency->code}}
                @endif
            </span>
            @endif
        </div>
        @if(isset($ecommerce_setting->online_order) && $ecommerce_setting->online_order != 0)
            @include('ecommerce::frontend.includes.product-purchase-actions')
        @endif
    </div>
</div>
