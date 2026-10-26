@extends('ecommerce::frontend.layout.main')
@section('title') {{ $ecommerce_setting->site_title ?? '' }} @endsection
@section('content')
@php($sections = collect($demo_home['sections'] ?? []))
@php($content = $demo_home['content'] ?? [])
@if($sections->contains('hero'))
<section class="fashion-demo-hero" style="background-image:url('{{ $content['hero']->image_url }}')"><div class="container-fluid"><div class="fashion-hero-copy"><span>The new season edit</span><h1>Style, thoughtfully selected</h1><p>Modern wardrobe signatures, beauty favourites and accessories for every day.</p><a href="{{ $content['hero']->link ?: url('shop') }}">Discover the collection</a></div></div></section>
@endif
@if($sections->contains('collections'))
<section class="fashion-edit demo-section"><div class="container-fluid"><div class="fashion-section-heading"><span class="fashion-kicker">Curated for you</span><h2>Shop the edit</h2><p>Discover collections selected for effortless everyday style.</p></div><div class="fashion-category-grid">@foreach($content['collections']->take(6) as $category)<a href="{{ url('shop/' . $category->slug) }}" class="fashion-category-card"><div class="fashion-category-image">@include('ecommerce::frontend.includes.category-media', ['category' => $category, 'preferLarge' => true, 'fallbackIcon' => 'apparel'])</div><div class="fashion-category-copy"><strong>{{ ucwords($category->name) }}</strong><span>Explore collection <i class="material-symbols-outlined">arrow_forward</i></span></div></a>@endforeach</div></div></section>
@endif
@if($sections->contains('new_arrivals'))
<section class="demo-section fashion-products"><div class="container-fluid"><div class="fashion-section-heading"><span class="fashion-kicker">Freshly selected</span><h2>New arrivals</h2></div>@include('ecommerce::frontend.includes.demo-product-grid', ['products' => $content['new_arrivals']])</div></section>
@endif
@if($sections->contains('promo'))
<section class="demo-section"><div class="container-fluid"><a class="fashion-promo" href="{{ $content['promo']->link ?: url('shop') }}" style="background-image:url('{{ $content['promo']->image_url }}')"><span>Finishing touches</span><strong>The accessories edit</strong><em>Shop accessories</em></a></div></section>
@endif
@if($sections->contains('shop_the_edit'))
<section class="demo-section fashion-products fashion-secondary"><div class="container-fluid"><div class="fashion-section-heading"><span class="fashion-kicker">Beauty & style</span><h2>Complete the look</h2></div>@include('ecommerce::frontend.includes.demo-product-grid', ['products' => $content['shop_the_edit']])</div></section>
@endif
@if($sections->contains('brands'))
<section class="demo-section demo-brand-section"><div class="container-fluid"><div class="fashion-section-heading"><span class="fashion-kicker">Names to know</span><h2>Featured labels</h2></div>@include('ecommerce::frontend.includes.demo-brand-row', ['brands' => $content['brands']])</div></section>
@endif
@endsection
@section('script')
@php($asset_prefix = !config('database.connections.saleprosaas_landlord') ? '' : '../../')
<script src="{{ asset($asset_prefix . 'modules/ecommerce/js/homepage-widgets.js') }}?v=20260928" defer></script>
@include('ecommerce::frontend.includes.demo-product-actions')
@endsection
