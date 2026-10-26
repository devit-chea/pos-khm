@php
    $category_id = data_get($settings, 'category_id');
    $limit = (int) data_get($settings, 'limit', 6);

    $products = $widget->prepared_products ?? collect();
@endphp

@if($products->isNotEmpty())
<section class="masonry-product-section" style="padding: 60px 0; background: #fff;">
    <div class="container-fluid">
        <div class="section-title mb-4">
            <h3 style="font-weight: 700; font-size: 2rem; color: #222;">{{__('db.Trending Styles')}}</h3>
        </div>

        <style>
            .masonry-grid {
                column-count: 4;
                column-gap: 20px;
            }
            @media (max-width: 1199px) {
                .masonry-grid { column-count: 3; }
            }
            @media (max-width: 991px) {
                .masonry-grid { column-count: 2; }
            }
            @media (max-width: 575px) {
                .masonry-grid { column-count: 1; }
            }
            .masonry-item {
                break-inside: avoid;
                margin-bottom: 20px;
                position: relative;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                transition: all 0.3s ease;
            }
            .masonry-item:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            }
            .masonry-img {
                width: 100%;
                display: block;
                object-fit: cover;
            }
            .masonry-overlay {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
                padding: 30px 20px 20px;
                color: #fff;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            .masonry-item:hover .masonry-overlay {
                opacity: 1;
            }
            .masonry-overlay h5 {
                margin-bottom: 5px;
                font-weight: 600;
                color: #fff;
                font-size: 1.1rem;
            }
            .masonry-overlay span {
                font-weight: 700;
                color: #fff;
                font-size: 1.2rem;
            }
        </style>

        <div class="masonry-grid">
            @foreach($products as $product)
            <div class="masonry-item">
                <a href="{{url('product/'.$product->slug.'/'.$product->id)}}">
                    <img loading="lazy" src="{{url('images/product')}}/{{$product->image}}" alt="{{$product->name}}" class="masonry-img">
                    <div class="masonry-overlay">
                        <h5>{{$product->name}}</h5>
                        <span>${{number_format($product->price, 2)}}</span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
