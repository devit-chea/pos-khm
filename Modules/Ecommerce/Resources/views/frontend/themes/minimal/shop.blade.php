@extends('ecommerce::frontend.themes.minimal.layout.main')

@section('title') Shop @endsection

@section('content')
<div class="bg-white">
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-gray-900">Shop Categories</h1>
            <p class="mt-4 max-w-xl mx-auto text-base text-gray-500">Browse our minimal collections.</p>
        </div>

        <div class="mt-16 grid grid-cols-1 gap-y-10 sm:grid-cols-2 gap-x-6 lg:grid-cols-3 xl:grid-cols-4 xl:gap-x-8">
            @foreach($categories as $category)
            <a href="{{ url('shop', $category->slug) }}" class="group block">
                <div class="w-full aspect-w-1 aspect-h-1 bg-gray-200 rounded-none overflow-hidden xl:aspect-w-7 xl:aspect-h-8">
                    @if($category->image)
                        <img src="{{ url('frontend/images/category', $category->image) }}" alt="{{ $category->name }}" class="w-full h-full object-center object-cover group-hover:opacity-75 transition-opacity">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100 group-hover:bg-gray-200 transition-colors">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    @endif
                </div>
                <h3 class="mt-4 text-lg font-medium text-gray-900 text-center">{{ $category->name }}</h3>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
