@php
    $iconFile = !empty($category->icon) ? basename((string) $category->icon) : null;
    $iconPath = $iconFile ? public_path('images/category/icons/' . $iconFile) : null;
    $hasIcon = $iconPath && is_file($iconPath);
@endphp
<span class="category-nav-icon" aria-hidden="true">
    @if($hasIcon)
        <img src="{{ url('images/category/icons/' . $iconFile) }}" alt="">
    @else
        <span class="material-symbols-outlined">category</span>
    @endif
</span>
