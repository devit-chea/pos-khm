@can('module.woocommerce.access')
<li class="{{ request()->routeIs('woocommerce.*') ? 'active' : '' }}">
    <a href="#woocommerce-menu" aria-expanded="{{ request()->routeIs('woocommerce.*') ? 'true' : 'false' }}" data-toggle="collapse">
        <i class="ti ti-brand-wordpress"></i><span>WooCommerce</span>
    </a>
    <ul id="woocommerce-menu" class="collapse list-unstyled {{ request()->routeIs('woocommerce.*') ? 'show' : '' }}">
        <li><a class="{{ request()->routeIs('woocommerce.index') ? 'active' : '' }}" href="{{ route('woocommerce.index') }}">WooCommerce</a></li>
        <li><a class="{{ request()->routeIs('woocommerce.sync-log') ? 'active' : '' }}" href="{{ route('woocommerce.sync-log') }}">{{ __('db.Sync Log') }}</a></li>
        <li><a class="{{ request()->routeIs('woocommerce.settings') ? 'active' : '' }}" href="{{ route('woocommerce.settings') }}">{{ __('db.settings') }}</a></li>
    </ul>
</li>
@endcan
