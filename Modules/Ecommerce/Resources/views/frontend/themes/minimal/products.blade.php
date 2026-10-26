@extends('ecommerce::frontend.themes.minimal.layout.main')

@section('title') {{ $category->name ?? 'Products' }} @endsection

@section('content')
<div class="bg-white">
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
        <div class="flex items-baseline justify-between border-b border-gray-200 pb-6 pt-24">
            <h1 class="text-4xl font-extrabold tracking-tight text-gray-900">{{ $category->name ?? 'Shop' }}</h1>
        </div>

        <section aria-labelledby="products-heading" class="pt-6 pb-24">
            <h2 id="products-heading" class="sr-only">Products</h2>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-x-8 gap-y-10">
                <!-- Filters (Placeholder for Minimal Theme) -->
                <form class="hidden lg:block">
                    <h3 class="sr-only">Categories</h3>
                    <!-- Filter options would go here -->
                    <div class="border-b border-gray-200 py-6">
                        <h3 class="-my-3 flow-root">
                            <span class="font-medium text-gray-900">Category</span>
                        </h3>
                        <div class="pt-6" id="filter-section-0">
                            <div class="space-y-4">
                                <p class="text-sm text-gray-500">{{ $category->short_description ?? 'Explore our collection.' }}</p>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Product grid -->
                <div class="lg:col-span-3">
                    <div class="grid grid-cols-1 gap-y-10 gap-x-6 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8">
                        @forelse($products as $product)
                        <a href="{{ url('product/' . $product->slug . '/' . $product->id) }}" class="group relative">
                            <div class="w-full min-h-80 bg-gray-200 aspect-w-1 aspect-h-1 rounded-none overflow-hidden group-hover:opacity-75 lg:h-80 lg:aspect-none">
                                <img src="{{ url('frontend/images/product', $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-center object-cover lg:w-full lg:h-full">
                            </div>
                            <div class="mt-4 flex justify-between">
                                <div>
                                    <h3 class="text-sm text-gray-700 font-medium">
                                        <span aria-hidden="true" class="absolute inset-0"></span>
                                        {{ $product->name }}
                                    </h3>
                                </div>
                                <p class="text-sm font-bold text-gray-900">${{ number_format($product->price, 2) }}</p>
                            </div>
                        </a>
                        @empty
                        <div class="col-span-full py-12 text-center">
                            <h3 class="text-lg font-medium text-gray-900">No products found</h3>
                            <p class="mt-2 text-sm text-gray-500">We couldn't find anything matching your criteria.</p>
                        </div>
                        @endforelse
                    </div>

                    @if(method_exists($products, 'links'))
                    <div class="mt-12">
                        {{ $products->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
