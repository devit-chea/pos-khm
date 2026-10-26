@php
        $parent_categories = $widget->prepared_categories ?? collect();
    @endphp
    <section class="category-tab-section mb-3">
        <div class="container-fluid" style="overflow-x:hidden">
            <div class="section-title mb-3">
                <div class="d-flex align-items-center">
                    <h3>{{$widget->category_slider_title}}</h3>
                </div>
                @if(isset($ecommerce_setting->theme) && $ecommerce_setting->theme != 'fashion')
                <div class="category-navigation">
                    <div class="category-button-prev"><span class="material-symbols-outlined">chevron_left</span></div>
                    <div class="category-button-next"><span class="material-symbols-outlined">chevron_right</span></div>
                </div>
                @endif
            </div>

            <div class="category-slider-wrapper swiper-container" data-loop="{{$widget->category_slider_loop}}" data-autoplay="{{$widget->category_slider_autoplay}}">
                <div class="swiper-wrapper">
                    @forelse ($parent_categories as $category)
                        <div class="swiper-slide">
                            <a href="{{url('shop')}}/{{$category->slug}}">
                                <div class="category-container">
                                    @include('ecommerce::frontend.includes.category-media', [
                                        'category' => $category,
                                        'preferLarge' => isset($ecommerce_setting->theme) && $ecommerce_setting->theme == 'fashion',
                                        'defer' => true,
                                        'imageClass' => 'category-img',
                                    ])

                                    <div class="category-name">
                                    {{ ucwords($category->name) }}
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                    @endforelse
                </div>
                @if(isset($ecommerce_setting->theme) && $ecommerce_setting->theme == 'fashion')
                <div class="category-navigation">
                    <div class="category-button-prev"><span class="material-symbols-outlined">chevron_left</span></div>
                    <div class="category-button-next"><span class="material-symbols-outlined">chevron_right</span></div>
                </div>
                @endif
            </div>
        </div>
    </section>
