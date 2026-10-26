<div class="grocery-header-shell">
<div class="grocery-utility-bar">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <div class="grocery-utility-message"><span class="material-symbols-outlined">local_shipping</span><span>Fresh groceries, simple ordering</span></div>
        <div class="grocery-utility-links">
            <a href="{{ url('shop') }}">Shop</a>
            <a href="{{ url('track-order') }}">{{ __('db.Track Order') }}</a>
            @guest
                <a href="{{ url('customer/login') }}">Login</a>
            @else
                @if(auth()->user()->role_id == 5)
                    <a href="{{ url('customer/account-details') }}">My Account</a>
                @endif
            @endguest
        </div>
    </div>
</div>
@include('ecommerce::frontend.layout.header.header', ['storefrontHeaderVariant' => 'grocery'])
</div>
