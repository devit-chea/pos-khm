<div class="electronics-header-shell">
<div class="electronics-utility-bar">
    <div class="container-fluid d-flex align-items-center justify-content-between">
        <div class="electronics-utility-title">
            <span class="material-symbols-outlined">devices</span>
            <span>{{ $ecommerce_setting->site_title ?? 'Electronics Store' }}</span>
        </div>
        <div class="electronics-utility-links">
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

@include('ecommerce::frontend.layout.header.header', ['storefrontHeaderVariant' => 'electronics'])
</div>
