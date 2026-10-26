@extends('backend.layout.main')

@section('content')
@push('css')
<style>
    .theme-card {
        border: 1px solid #e3e7ed;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 30px;
        transition: all .25s ease;
        position: relative;
        background: #fff;
        height: calc(100% - 30px);
    }
    .theme-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(29, 43, 76, .10);
    }
    .theme-card.active {
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, .10), 0 10px 28px rgba(29, 43, 76, .10);
    }
    .theme-preview {
        height: 220px;
        padding: 22px;
        background: linear-gradient(145deg, #f6f8fb, #e9edf4);
        border-bottom: 1px solid #e7eaf0;
        display: flex;
        align-items: stretch;
    }
    .theme-preview-window {
        width: 100%;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 12px 28px rgba(31, 43, 65, .13);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .theme-preview-bar {
        height: 26px;
        background: #222b39;
        display: flex;
        align-items: center;
        padding: 0 9px;
        gap: 5px;
    }
    .theme-preview-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: rgba(255,255,255,.55);
    }
    .theme-preview-body {
        flex: 1;
        padding: 12px;
        background: #f8fafc;
    }
    .theme-preview-hero {
        height: 55px;
        border-radius: 5px;
        background: linear-gradient(120deg, #d9e0ea, #edf1f6);
        margin-bottom: 10px;
    }
    .theme-preview-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 7px;
    }
    .theme-preview-grid span {
        display: block;
        height: 49px;
        border-radius: 4px;
        border: 1px solid #e5e9ef;
        background: #fff;
    }
    .theme-preview-electronics {
        background: linear-gradient(145deg, #e9f2ff, #eef2f7);
    }
    .theme-preview-electronics .theme-preview-bar {
        background: #0f172a;
    }
    .theme-preview-electronics .theme-preview-hero {
        background: linear-gradient(120deg, #0f172a, #1d4ed8);
    }
    .theme-preview-fashion .theme-preview-bar {
        background: #171717;
    }
    .theme-preview-fashion .theme-preview-body {
        background: #fbf8f5;
    }
    .theme-preview-fashion .theme-preview-hero {
        background: linear-gradient(120deg, #2c2724, #b98d7f);
    }
    .theme-preview-grocery {
        background: linear-gradient(145deg, #edf7e8, #f7fbf4);
    }
    .theme-preview-grocery .theme-preview-bar {
        background: #173c2c;
    }
    .theme-preview-grocery .theme-preview-hero {
        background: linear-gradient(120deg, #2f855a, #b8df83);
    }
    .theme-preview-grocery .theme-preview-grid span {
        border-radius: 50%;
        background: #fff;
    }
    .theme-preview-minimal .theme-preview-bar {
        background: #111;
    }
    .theme-preview-minimal .theme-preview-hero {
        background: linear-gradient(120deg, #f3f3f3, #d9d9d9);
    }
    .theme-card .theme-info {
        padding: 18px;
    }
    .theme-card .theme-name-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 9px;
    }
    .theme-card .theme-name {
        margin: 0;
        font-size: 17px;
        font-weight: 600;
    }
    .theme-description {
        min-height: 42px;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.55;
        margin-bottom: 16px;
    }
    .theme-meta {
        color: #8a93a2;
        font-size: 11px;
        margin-top: 2px;
    }
    .active-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 2;
        background: #007bff;
        color: #fff;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        box-shadow: 0 3px 10px rgba(0, 123, 255, .25);
    }
</style>
@endpush

@if(session()->has('message'))
<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">{{__('db.Theme Selection')}}</h4>
                        <small class="text-muted">Choose a storefront style without changing products, cart, checkout or order data.</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($themes as $themeOption)
                            @php
                                $slug = $themeOption['slug'];
                                $isActive = ($settings->theme ?? 'default') === $slug;
                            @endphp
                            <div class="col-lg-4 col-md-6">
                                <div class="theme-card @if($isActive) active @endif">
                                    @if($isActive)
                                    <div class="active-badge">{{__('db.Active')}}</div>
                                    @endif

                                    <div class="theme-preview theme-preview-{{ $slug }}">
                                        <div class="theme-preview-window">
                                            <div class="theme-preview-bar">
                                                <span class="theme-preview-dot"></span>
                                                <span class="theme-preview-dot"></span>
                                                <span class="theme-preview-dot"></span>
                                            </div>
                                            <div class="theme-preview-body">
                                                <div class="theme-preview-hero"></div>
                                                <div class="theme-preview-grid">
                                                    <span></span><span></span><span></span><span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="theme-info">
                                        <div class="theme-name-row">
                                            <div>
                                                <h3 class="theme-name">{{ $themeOption['name'] }}</h3>
                                                <div class="theme-meta">
                                                    @if($themeOption['industry']){{ ucfirst($themeOption['industry']) }} &middot; @endif
                                                    v{{ $themeOption['version'] }}
                                                </div>
                                            </div>
                                        </div>

                                        <p class="theme-description">
                                            {{ $themeOption['description'] ?: 'Storefront theme using the shared SalePro eCommerce catalog, cart and checkout.' }}
                                        </p>

                                        @if(!$isActive)
                                        <form action="{{ route('setting.ecommerce.theme.update') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="theme" value="{{ $slug }}">
                                            <button type="submit" class="btn btn-primary btn-sm">{{__('db.Activate')}}</button>
                                        </form>
                                        @else
                                        <button type="button" class="btn btn-secondary btn-sm" disabled>{{__('db.Activated')}}</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#ecommerce").siblings('a').attr('aria-expanded','true');
    $("ul#ecommerce").addClass("show");
    $("ul#ecommerce #ecommerce-theme-menu").addClass("active");
</script>
@endpush
