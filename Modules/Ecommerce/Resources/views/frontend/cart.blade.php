@extends('ecommerce::frontend.layout.main')

@section('title') {{__('db.Cart')}} {{ $ecommerce_setting->site_title ?? '' }} @endsection

@section('description')  @endsection

@section('content')
    <!--Breadcrumb Area start-->
    <div class="breadcrumb-section">
        <div class="container">
            <div class="row">
                <div class="col">
                    <h1 class="page-title">{{__('db.Shop Cart')}}</h1>
                    <ul>
                        <li><a href="{{ url('/') }}">{{__('db.Home')}}</a></li>
                        <li class="active">{{__('db.Shop Cart')}}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!--Breadcrumb Area ends-->
    <!--Shop cart starts-->
    <section class="shop-cart-section">
        <div class="container">
            <div class="row">
                <div class="col-md-12 cart-table-container">
                    @if(session('total_qty') > 0)
                    <div class="cart-table table-content table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="cart-product-name">{{__('db.product')}}</th>
                                    <th class="cart-product-quantity">{{__('db.Price')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $id => $cart_product)
                                @php
                                    $variant = $cart_product['variant'] ?: 0;
                                    $variantId = $cart_product['variant_id'] ?? null;
                                    $lineKey = $cart_product['key'];
                                @endphp
                                <tr data-line-key="{{ $lineKey }}">
                                    <td class="cart-product-name">
                                        <div class="d-flex align-self-center">
                                            <div class="remove">
                                                <a class="remove-from-cart cart" title="{{ __('db.Remove') }}" data-id="{{$cart_product['id']}}" data-variant-id="{{$variantId}}">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </a>
                                            </div>
                                            <div>
                                                @if($cart_product['image']!==null)
                                                    @php
                                                        $images = explode(',', $cart_product['image']);
                                                        $cart_product['image'] = $images[0];
                                                    @endphp
                                                @endif
                                                <img loading="lazy" src="{{ url('images/product/small') }}/{{ $cart_product['image'] }}" alt="{{ $cart_product['name'] }}">
                                            </div>
                                            <span class="align-self-center">{!! $cart_product['name'] !!} @if($variant !== 0)({{$variant}})@endif</span>
                                        </div>
                                    </td>
                                    <td class="cart-product-quantity">
                                        <div class="product-subtotal">
                                            @if($general_setting->currency_position == 'prefix')
                                            <span class="amount" data-price="{{ $cart_product['total_price'] }}">{{$currency->symbol ?? $currency->code}} {{ number_format($cart_product['total_price'] * ($currency->exchange_rate ?? 1), $general_setting->decimal ?? 2, '.', '') }}</span>
                                            @else
                                            <span class="amount" data-price="{{ $cart_product['total_price'] }}">{{ number_format($cart_product['total_price'] * ($currency->exchange_rate ?? 1), $general_setting->decimal ?? 2, '.', '') }} {{$currency->symbol ?? $currency->code}}</span>
                                            @endif
                                        </div>
                                        <div class="input-qty">
                                            <span class="input-group-btn">
                                                <button type="button" class="cart-quantity-left-minus" data-id="{{$cart_product['id']}}" data-variant-id="{{$variantId}}">
                                                    <i class="material-symbols-outlined">remove</i>
                                                </button>
                                            </span>
                                            <input type="text" class="input-number" value="{{ $cart_product['qty'] }}">
                                            <span class="input-group-btn">
                                                <button type="button" class="cart-quantity-right-plus" data-id="{{$cart_product['id']}}" data-variant-id="{{$variantId}}">
                                                    <i class="material-symbols-outlined">add</i>
                                                </button>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <hr>
                        <h4 class="text-right mt-3 mb-4">
                            {{__('db.Sub Total')}}:
                            @if($general_setting->currency_position == 'prefix')
                            <span class="currency_code">{{$currency->symbol ?? $currency->code}}</span><span class="sub_total" data-subtotal="{{  ($subTotal ?? 0.00 ) }}">{{ ($subTotal ?? 0.00) * $currency->exchange_rate }}</span>
                            @else
                            <span class="sub_total" data-subtotal="{{  ($subTotal ?? 0.00 ) }}">{{ ($subTotal ?? 0.00 ) * $currency->exchange_rate }}</span><span class="currency_code">{{$currency->symbol ?? $currency->code}}</span>
                            @endif
                        </h4>
                    </div>
                    <div class="row mb-5">
                        <div class="col-md-6 col-sm-6 col-12 mt-4 text-center">
                            <a href="{{ url('shop') }}" class="button style3">{{__('db.Continue Shopping')}}</a>
                        </div>
                        <div class="col-md-6 col-sm-6 col-12 mt-4 text-center">
                            <a href="{{ url('checkout') }}" class="button style1">{{__('db.Proceed to Checkout')}}</a>
                        </div>
                    </div>
                    @else
                    <div class="text-center">
                        <h3>{{__('db.No item in your cart')}}</h3>
                        <a href="{{ url('shop') }}" class="button style3">{{__('db.Continue Shopping')}}</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!--Shop cart ends-->
@endsection

@section('script')
    <script type="text/javascript">
        "use strict";

        $(document).on('click', '.remove-from-cart.cart', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            var variantId = $(this).data('variant-id') || null;

            $.ajax({
                url: "{{ route('removeFromCart') }}",
                type:"POST",
                data:{
                    product_id: id,
                    variant_id: variantId,
                },
                success:function(response){
                    if(response) {
                        $('.alert').addClass('alert-custom show');
                        $('.alert-custom .message').html(response.success);
                        $('[data-line-key="' + response.deleted_item + '"]').remove();
                        $('.sub_total').html(response.subTotal.toFixed({{$general_setting->decimal ?? 2}}));
                        $('.cart__menu .cart_qty').html(response.total_qty);
                        $('.cart__menu .total').html(formatCurrency(response.subTotal));

                        if(response.total_qty < 1) {
                            $('.sub_total').html('0.00');
                            $('.cart__menu .cart_qty').html('0');
                            $('.cart__menu .total').html(formatCurrency(0));
                            $('.cart-table-container').html('<div class="text-center"><h3>{{trans("db.No item in your cart")}}</h3><a href="{{ url("shop") }}" class="button style3">{{trans("db.Continue Shopping")}}</a></div>');
                        }
                    }
                },
                error: showCartError
            });
        });

        function updateCartLine(button, quantity) {
            var row = button.closest('tr');
            $.ajax({
                url: "{{ route('updateCart') }}",
                type:"POST",
                data:{
                    product_id: button.data('id'),
                    variant_id: button.data('variant-id') || null,
                    product_qty: quantity,
                },
                success:function(response){
                    row.find('input.input-number').val(response.line.qty);
                    row.find('.product-subtotal .amount')
                        .attr('data-price', response.line.total_price)
                        .html(formatCurrency(response.line.total_price));
                    $('.sub_total').html(response.subTotal.toFixed({{$general_setting->decimal ?? 2}}));
                    $('.cart__menu .cart_qty').html(response.total_qty);
                    $('.cart__menu .total').html(formatCurrency(response.subTotal));
                },
                error: showCartError
            });
        }

        function showCartError(xhr) {
            $('.alert').addClass('alert-custom show');
            $('.alert-custom .message').text(
                xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : @json(__('db.Ecommerce cart line unavailable'))
            );
        }

        $('.cart-quantity-right-plus').on("click", function(e) {
            e.preventDefault();
            var qty = parseFloat($(this).parent().siblings("input.input-number").val());
            updateCartLine($(this), qty + 1);
        });

        $('.cart-quantity-left-minus').on("click", function(e) {
            e.preventDefault();
            var qty = parseFloat($(this).parent().siblings("input.input-number").val());
            if (qty > 1) {
                updateCartLine($(this), qty - 1);
            }
        });
    </script>
@endsection
