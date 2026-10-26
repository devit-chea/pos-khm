@php
    $assetPrefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../';
    $ecommerceLogo = !empty($ecommerce_setting->logo) ? basename((string) $ecommerce_setting->logo) : null;
    $generalLogo = !empty($general_setting->site_logo) ? basename((string) $general_setting->site_logo) : null;
    $logoUrl = $ecommerceLogo && is_file(public_path('frontend/images/' . $ecommerceLogo))
        ? asset($assetPrefix . 'frontend/images/' . $ecommerceLogo)
        : ($generalLogo && is_file(public_path('logo/' . $generalLogo)) ? asset($assetPrefix . 'logo/' . $generalLogo) : null);
@endphp

@if($logoUrl)
    <img class="storefront-company-logo" src="{{ $logoUrl }}" alt="{{ $ecommerce_setting->site_title ?? $general_setting->site_title ?? config('app.name') }}">
@else
    <span class="storefront-company-name">{{ $ecommerce_setting->site_title ?? $general_setting->site_title ?? config('app.name') }}</span>
@endif
