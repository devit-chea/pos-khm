@php
    $preferLarge = $preferLarge ?? false;
    $defer = $defer ?? false;
    $imageClass = $imageClass ?? '';
    $fallbackIcon = $fallbackIcon ?? 'category';
    $demoMedia = app(\Modules\Ecommerce\Services\DemoMediaResolver::class);
    $categoryMediaUrl = $demoMedia->categoryImageUrl($category, $preferLarge);
    $fallbackIcon = $demoMedia->categoryIcon($category, $fallbackIcon);
@endphp

@if($categoryMediaUrl)
    <img loading="lazy" @if($imageClass) class="{{ $imageClass }}" @endif
         @if($defer) data-src="{{ $categoryMediaUrl }}" @else src="{{ $categoryMediaUrl }}" @endif alt="">
@else
    <span class="material-symbols-outlined category-image-fallback" aria-hidden="true">{{ $fallbackIcon }}</span>
@endif
