@if(isset($brands) && $brands->isNotEmpty())
<div class="demo-brand-row">
    @foreach($brands as $brand)
    @php($brandImageUrl = app(\Modules\Ecommerce\Services\DemoMediaResolver::class)->brandImageUrl($brand))
    <div class="demo-brand-pill">
        @if($brandImageUrl)
        <img loading="lazy" src="{{ $brandImageUrl }}" alt="{{ $brand->title }}">
        @else
        <span class="demo-brand-monogram" aria-hidden="true">{{ mb_substr(trim($brand->title), 0, 1) }}</span>
        <strong>{{ $brand->title }}</strong>
        @endif
    </div>
    @endforeach
</div>
@endif
