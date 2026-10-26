@extends('ecommerce::frontend.themes.minimal.layout.main')

@section('title') {{ $ecommerce_setting->site_title ?? 'Home' }} @endsection

@section('content')

@if(isset($sliders))
<!-- Minimal Hero Slider -->
<div class="relative bg-black overflow-hidden h-[60vh] min-h-[400px]">
    @foreach($sliders as $key => $slider)
    <div class="absolute inset-0 transition-opacity duration-500 @if($key == 0) opacity-100 @else opacity-0 hidden @endif">
        <img src="{{ url('frontend/images/slider/desktop', $slider->image1) }}" class="w-full h-full object-cover opacity-60" alt="Slider Image">
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center px-4">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-white tracking-tight mb-4">
                    Minimal Electronics
                </h1>
                <p class="mt-4 max-w-lg mx-auto text-xl text-gray-300">
                    Premium tech for your everyday life.
                </p>
                @if($slider->link)
                <div class="mt-8">
                    <a href="{{ $slider->link }}" class="inline-flex items-center justify-center px-8 py-3 border border-black text-base font-medium rounded-none text-gray-900 bg-white hover:bg-gray-100 md:py-4 md:text-lg md:px-10 transition-colors">
                        Shop Now
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if(isset($widgets))
        @foreach($widgets as $widget)

            @if($widget->name == 'text-widget')
                <div class="prose prose-lg max-w-none text-gray-600 my-12 text-center">
                    {!! $widget->text_content !!}
                </div>

            @elseif($widget->name == 'one-c-banner-widget')
                <div class="my-16 overflow-hidden rounded-none shadow-none relative group">
                    <img src="{{ url('frontend/images/banner_widget', $widget->one_c_banner_image1) }}" alt="Banner" class="w-full h-auto object-cover transform transition-transform duration-700 group-hover:scale-105">
                    @if($widget->one_c_banner_link1)
                    <a href="{{ $widget->one_c_banner_link1 }}" class="absolute inset-0">
                        <span class="sr-only">View Details</span>
                    </a>
                    @endif
                </div>

            @elseif($widget->name == 'product-collection-widget')
                @php
                    $collection = DB::table('collections')->where('id', $widget->product_collection_id)->first();
                    $products = [];
                    if($collection && !empty($collection->products)) {
                        $products = DB::table('products')->whereIn('id', explode(',', $collection->products))->take(4)->get();
                    }
                @endphp
                @if($collection && count($products) > 0)
                <div class="my-16">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ $collection->name }}</h2>
                        <a href="{{ url('products/collections/all') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Shop collection <span aria-hidden="true">&rarr;</span></a>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-10 gap-x-6 xl:gap-x-8">
                        @foreach($products as $product)
                        <a href="{{ url('product/' . $product->slug . '/' . $product->id) }}" class="group block">
                            <div class="w-full aspect-w-1 aspect-h-1 bg-gray-200 rounded-none overflow-hidden xl:aspect-w-7 xl:aspect-h-8">
                                <img src="{{ url('frontend/images/product', $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-center object-cover group-hover:opacity-75 transition-opacity">
                            </div>
                            <h3 class="mt-4 text-sm text-gray-700 font-medium">{{ $product->name }}</h3>
                            <p class="mt-1 text-lg font-bold text-gray-900">${{ number_format($product->price, 2) }}</p>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

            @endif

        @endforeach
    @endif
</div>
@endsection
