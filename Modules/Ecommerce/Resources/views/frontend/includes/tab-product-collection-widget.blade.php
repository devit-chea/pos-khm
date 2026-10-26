    @php
    $collections = $widget->prepared_collections ?? collect();
    @endphp
    <!--Product area starts-->
    <section class="product-tab-section">
        <div class="container-fluid">
            <div class="section-title mb-3">
                <ul class="nav nav-tabs" id="widget-tabs-{{ $widget->id }}" role="tablist">
                @foreach($collections as $key=>$collection)
                    @php($tabId = 'widget-' . $widget->id . '-collection-' . $collection->id)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if($key == 0) active @endif" id="{{$tabId}}-tab" data-toggle="tab" data-target="#{{$tabId}}" type="button" role="tab" aria-controls="{{$tabId}}" aria-selected="{{ $key == 0 ? 'true' : 'false' }}">{{$collection->name}}</button>
                    </li>
                @endforeach
                </ul>
                @if($widget->tab_product_collection_type == 'slider')
                <div class="product-navigation">
                    <div class="product-button-next v1"><span class="material-symbols-outlined">chevron_right</span></div>
                    <div class="product-button-prev v1"><span class="material-symbols-outlined">chevron_left</span></div>
                </div>
                @endif
            </div>
            <div class="tab-content" id="widget-tab-content-{{ $widget->id }}">
                @foreach($collections as $key=>$collection)
                @php($products = $collection->prepared_products ?? collect())
                @php($tabId = 'widget-' . $widget->id . '-collection-' . $collection->id)
                <div class="tab-pane fade @if($key == 0) show active @endif" id="{{$tabId}}" role="tabpanel" aria-labelledby="{{$tabId}}-tab">
                    @if($widget->tab_product_collection_type == 'slider')
                    <div class="product-slider-wrapper swiper-container" data-loop="{{$widget->tab_product_collection_slider_loop}}" data-autoplay="{{$widget->tab_product_collection_slider_autoplay}}">
                        <div class="swiper-wrapper">
                            @forelse ($products as $product)
                            <div class="swiper-slide">
                            @include('ecommerce::frontend.includes.product-template')
                            </div>
                            @empty
                            @endforelse
                        </div>
                    </div>
                    @else
                    <div class="product-grid">
                        @foreach($products as $product)
                        @include('ecommerce::frontend.includes.product-template')
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </section>
    <!--product area ends-->
