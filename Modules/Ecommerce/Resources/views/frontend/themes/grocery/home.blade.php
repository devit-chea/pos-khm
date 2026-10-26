@extends('ecommerce::frontend.layout.main')
@section('title') {{ $ecommerce_setting->site_title ?? '' }} @endsection
@section('content')
@php($sections = collect($demo_home['sections'] ?? []))
@php($content = $demo_home['content'] ?? [])
@if($sections->contains('hero'))
<section class="grocery-demo-hero" style="background-image:url('{{ $content['hero']->image_url }}')"><div class="container-fluid"><div class="grocery-hero-copy"><span>Freshness for every day</span><h1>Your weekly shop, made easy</h1><p>Produce, pantry staples and household favourites gathered in one convenient place.</p><a href="{{ $content['hero']->link ?: url('shop') }}" class="button style1">Start shopping</a></div></div></section>
@endif
@if($sections->contains('services'))
<section class="grocery-service-strip"><div class="container-fluid"><div class="grocery-service-grid"><div class="grocery-service-item"><span class="material-symbols-outlined">local_shipping</span><div><strong>Fast delivery</strong><small>Fresh essentials to your door</small></div></div><div class="grocery-service-item"><span class="material-symbols-outlined">eco</span><div><strong>Fresh selection</strong><small>Daily grocery and produce</small></div></div><div class="grocery-service-item"><span class="material-symbols-outlined">sell</span><div><strong>Everyday value</strong><small>Deals across popular categories</small></div></div><div class="grocery-service-item"><span class="material-symbols-outlined">shopping_basket</span><div><strong>Easy ordering</strong><small>Quick cart and checkout</small></div></div></div></div></section>
@endif
@if($sections->contains('categories'))
<section class="grocery-categories demo-section"><div class="container-fluid"><div class="grocery-section-heading"><div><span class="grocery-eyebrow">Fresh picks</span><h2>Shop grocery categories</h2></div><a href="{{ url('shop') }}" class="grocery-heading-link">Browse all <span class="material-symbols-outlined">arrow_forward</span></a></div><div class="grocery-category-grid">@foreach($content['categories'] as $category)<a href="{{ url('shop/' . $category->slug) }}" class="grocery-category-card"><div class="grocery-category-image">@include('ecommerce::frontend.includes.category-media', ['category' => $category, 'fallbackIcon' => 'nutrition'])</div><span>{{ ucwords($category->name) }}</span></a>@endforeach</div></div></section>
@endif
@if($sections->contains('popular'))
<section class="demo-section grocery-products"><div class="container-fluid"><div class="grocery-section-heading"><div><span class="grocery-eyebrow">Customer favourites</span><h2>Popular products</h2></div></div>@include('ecommerce::frontend.includes.demo-product-grid', ['products' => $content['popular']])</div></section>
@endif
@if($sections->contains('promo'))
<section class="demo-section"><div class="container-fluid"><a class="grocery-promo" href="{{ $content['promo']->link ?: url('shop') }}" style="background-image:url('{{ $content['promo']->image_url }}')"><span>Breakfast to bedtime</span><strong>Everyday essentials for a well-stocked home</strong><em>Fill your basket</em></a></div></section>
@endif
@if($sections->contains('daily_essentials'))
<section class="demo-section grocery-products"><div class="container-fluid"><div class="grocery-section-heading"><div><span class="grocery-eyebrow">Stock up</span><h2>Daily essentials</h2></div></div>@include('ecommerce::frontend.includes.demo-product-grid', ['products' => $content['daily_essentials']])</div></section>
@endif
@if($sections->contains('brands'))
<section class="demo-section demo-brand-section"><div class="container-fluid"><div class="grocery-section-heading"><div><span class="grocery-eyebrow">Pantry favourites</span><h2>Brands in your basket</h2></div></div>@include('ecommerce::frontend.includes.demo-brand-row', ['brands' => $content['brands']])</div></section>
@endif
@endsection
@section('script')
@php($asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../')
<script src="{{ asset($asset_prefix . 'modules/ecommerce/js/homepage-widgets.js') }}?v=20260928" defer></script>
@include('ecommerce::frontend.includes.demo-product-actions')
@endsection
