@php($selectedDemoProfile = $demo_storefront_profile['slug'] ?? 'default')
<aside class="demo-profile-switcher" data-demo-switcher>
    <button class="demo-profile-toggle" type="button" aria-expanded="false" aria-controls="demo-profile-panel">
        <span class="material-symbols-outlined" aria-hidden="true">palette</span>
        <span>Explore Demo Stores</span>
    </button>
    <div class="demo-profile-panel" id="demo-profile-panel" hidden>
        <div class="demo-profile-heading">
            <strong>Explore SalePro Stores</strong>
            <small>See how SalePro adapts to different businesses.</small>
        </div>
        <nav aria-label="Storefront demo profiles">
            @foreach($demo_storefront_profiles as $profileSlug => $profile)
                <a href="{{ route('ecommerce.demo.switch', ['profile' => $profileSlug]) }}"
                   class="demo-profile-option @if($selectedDemoProfile === $profileSlug) active @endif"
                   @if($selectedDemoProfile === $profileSlug) aria-current="page" @endif>
                    <span class="material-symbols-outlined" aria-hidden="true">{{ $profile['icon'] }}</span>
                    <span>{{ $profile['label'] }}</span>
                    @if($selectedDemoProfile === $profileSlug)
                        <span class="material-symbols-outlined demo-profile-check" aria-hidden="true">check</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>
</aside>
