@if(isset($products) && $products->isNotEmpty())
<div class="demo-product-grid">
    @foreach($products as $product)
    <div class="demo-product-card">
        @include('ecommerce::frontend.includes.product-template', ['product' => $product])
    </div>
    @endforeach
</div>
@endif
