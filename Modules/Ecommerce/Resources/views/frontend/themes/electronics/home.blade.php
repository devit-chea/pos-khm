@extends('ecommerce::frontend.layout.main')
@section('title') {{ $ecommerce_setting->site_title ?? '' }} @endsection
@section('content')
@php($sections = collect($demo_home['sections'] ?? []))
@php($content = $demo_home['content'] ?? [])
@if($sections->contains('hero'))
<section class="electronics-demo-hero" style="background-image:url('{{ $content['hero']->image_url }}')"><div class="container-fluid"><div class="electronics-hero-copy"><span>Smarter technology. Better everyday.</span><h1>Upgrade your setup</h1><p>Explore phones, laptops, audio and connected essentials selected for modern life.</p><a href="{{ $content['hero']->link ?: url('shop') }}" class="button style1">Shop electronics</a></div></div></section>
@endif
@if($sections->contains('departments'))
<section class="electronics-departments demo-section"><div class="container-fluid"><div class="electronics-section-heading"><div><span class="electronics-eyebrow">Tech departments</span><h2>Find your next upgrade</h2></div><a href="{{ url('shop') }}" class="electronics-heading-link">View all <span class="material-symbols-outlined">arrow_forward</span></a></div><div class="electronics-department-grid">@foreach($content['departments'] as $category)<a href="{{ url('shop/' . $category->slug) }}" class="electronics-department-card"><div class="electronics-department-icon">@include('ecommerce::frontend.includes.category-media', ['category' => $category, 'fallbackIcon' => 'devices_other'])</div><span>{{ ucwords($category->name) }}</span></a>@endforeach</div></div></section>
@endif
@if($sections->contains('featured'))
<section class="demo-section electronics-products"><div class="container-fluid"><div class="electronics-section-heading"><div><span class="electronics-eyebrow">Top picks</span><h2>Featured electronics</h2></div></div>@include('ecommerce::frontend.includes.demo-product-grid', ['products' => $content['featured']])</div></section>
@endif
@if($sections->contains('promo'))
<section class="demo-section"><div class="container-fluid"><a class="electronics-promo" href="{{ $content['promo']->link ?: url('shop') }}" style="background-image:url('{{ $content['promo']->image_url }}')"><span>Designed for your desk</span><strong>Accessories that make every setup work harder</strong><em>Explore accessories</em></a></div></section>
@endif
@if($sections->contains('new_arrivals'))
<section class="demo-section electronics-products"><div class="container-fluid"><div class="electronics-section-heading"><div><span class="electronics-eyebrow">Just landed</span><h2>New tech arrivals</h2></div></div>@include('ecommerce::frontend.includes.demo-product-grid', ['products' => $content['new_arrivals']])</div></section>
@endif
@if($sections->contains('brands'))
<section class="demo-section demo-brand-section"><div class="container-fluid"><div class="electronics-section-heading"><div><span class="electronics-eyebrow">Trusted makers</span><h2>Technology brands</h2></div></div>@include('ecommerce::frontend.includes.demo-brand-row', ['brands' => $content['brands']])</div></section>
@endif
@endsection
@section('script')
@php($asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../')
<script src="{{ asset($asset_prefix . 'modules/ecommerce/js/homepage-widgets.js') }}?v=20260928" defer></script>
@include('ecommerce::frontend.includes.demo-product-actions')
@endsection
