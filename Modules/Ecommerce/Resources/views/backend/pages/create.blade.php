@extends('backend.layout.main') @section('content')
@if(session()->has('not_permitted'))
<div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

@push('css')
<style>
    .widgets {
        list-style: none;
        padding-left: 0;
    }

    .widgets li {
        background-color: #fff;
        border: 1px solid #ddd;
        cursor: pointer;
        display: inline-block;
        padding: 10px 15px;
        margin-bottom: 20px;
    }

    .widgets li:last-child {
        margin-bottom: 0;
    }

    .toggle-collapse {
        display: block;
    }

    .widgets li.placeholder {
        position: relative;
    }

    .ajax-message {
        bottom: 10px;
        position: fixed;
        right: 10px;
        z-index: 999;
    }

    #page-template>div {
        border: 1px solid #ddd;
        padding: 20px
    }

    #page-template div>ul {
        min-height: 300px;
    }

    #layout.widgets li {
        display: block;
        width: 100%;
    }

    .collapse>form>.bootstrap-select>.dropdown-toggle {
        display: none;
    }

    .sticky-widget-sidebar {
        position: sticky;
        top: 80px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
    .sticky-widget-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    .sticky-widget-sidebar::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 4px;
    }
</style>
@endpush
<section>
    <div class="container-fluid">
        <form id="add-page-form" method="post" action="{{route('page.store')}}" class="form-horizontal" enctype='multipart/form-data'>

            @csrf
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            {{__('db.Add Page')}}
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>{{__('db.Page Name')}} *</label>
                                <input type="text" name="page_name" required class="form-control" placeholder="{{__('db.Page Name')}}">
                            </div>
                            <div class="form-group">
                                <label>{{__('db.Permalink')}} *</label>
                                <div class="input-group mb-2">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">{{url('/')}}/</div>
                                    </div>
                                    <input type="text" name="slug" required class="form-control">
                                </div>
                            </div>
                            <div id="text-block" class="form-group">
                                <label>{{__('db.Description')}} *</label>
                                <textarea id="description" name="description" class="form-control"></textarea>
                            </div>
                            <div id="page-template" class="form-group">
                                <label>{{__('db.Page Layout')}} *</label>
                                <span class="d-block mt-2 mb-2">({{__('db.click on the widgets below to add it to your layout')}})</span>
                                <style>
                                .widget-category-title {
                                    font-size: 13px;
                                    font-weight: 700;
                                    text-transform: uppercase;
                                    margin-top: 15px;
                                    margin-bottom: 10px;
                                    color: #555;
                                }
                                .widget-grid {
                                    display: grid;
                                    grid-template-columns: repeat(2, 1fr);
                                    gap: 10px;
                                    margin-bottom: 20px;
                                }
                                .widget-add-card {
                                    border: 1px dashed #ccc;
                                    border-radius: 5px;
                                    text-align: center;
                                    padding: 15px 5px;
                                    cursor: pointer;
                                    background: #fafafa;
                                    transition: all 0.2s ease;
                                }
                                .widget-add-card:hover {
                                    border-color: #007bff;
                                    background: #f0f8ff;
                                    color: #007bff;
                                    transform: translateY(-2px);
                                }
                                .widget-add-card i {
                                    font-size: 24px;
                                    display: block;
                                    margin-bottom: 8px;
                                    color: #6c757d;
                                }
                                .widget-add-card:hover i {
                                    color: #007bff;
                                }
                                .widget-add-card span {
                                    font-size: 12px;
                                    font-weight: 500;
                                    display: block;
                                    line-height: 1.2;
                                }

    .sticky-widget-sidebar {
        position: sticky;
        top: 80px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
    .sticky-widget-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    .sticky-widget-sidebar::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 4px;
    }
</style>

                            <div>
                                    <ul id="layout" class="widgets"></ul>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{__('db.Meta Title')}}</label>
                                <input type="text" name="meta_title" class="form-control" placeholder="{{__('db.Meta Title')}}">
                            </div>
                            <div class="form-group">
                                <label>{{__('db.Meta Description')}}</label>
                                <input type="text" name="meta_description" class="form-control" placeholder="{{__('db.Meta Description')}}">
                            </div>
                            <div class="form-group">
                                <label>{{__('db.Og_Title')}}</label>
                                <input type="text" name="og_title" class="form-control" placeholder="{{__('db.Og Title')}}">
                            </div>
                            <div class="form-group">
                                <label>{{__('db.Og_description')}}</label>
                                <input type="text" name="og_description" class="form-control" placeholder="{{__('db.Og Description')}}">
                            </div>
                            <div class="form-group">
                                <label for="image">{{__('db.Og_Image')}}</label>
                                <input type="file" class="form-control" name="og_image">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-md-4">


                    <div class="card">
                        <div class="card-body">
                            <input type="hidden" name="status" value="1" />
                            <button id="draft" type="submit" class="btn btn-warning" disabled><i class="ti ti-save"></i> {{__('db.Save as Draft')}}</button>
                            <button id="publish" type="submit" class="btn btn-primary" disabled><i class="ti ti-check"></i> {{__('db.Publish')}}</button>
                            <input id="page_id" name="page_id" type="hidden">
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            {{__('db.Templates')}}
                        </div>
                        <div class="card-body">
                            <select id="template" class="selectpicker form-control" name="template">
                                <option value="default">Default</option>
                                <option value="home">Home</option>
                                <option value="contact">Contact</option>
                                <option value="faq">FAQ</option>
                            </select>
                            <div class="badge badge-light mt-3">More templates are coming soon...</div>
                        </div>
                    </div>

<div class="card sticky-widget-sidebar">
            <div class="card-header">
                {{__('db.Available Widgets')}}
            </div>
            <div class="card-body pt-2 pb-2">
                <div id="widget-list">
                                <h6 class="widget-category-title">{{__('db.Content')}}</h6>
                                <div class="widget-grid">
                                    <div class="widget-add-card" data-class="text-widget">
                                        <i class="ti ti-align-left"></i>
                                        <span>{{__('db.Text')}}</span>
                                    </div>
                                </div>

                                <h6 class="widget-category-title">{{__('db.Banners & Sliders')}}</h6>
                                <div class="widget-grid">
                                    <div class="widget-add-card" data-class="one-c-banner-widget">
                                        <i class="ti ti-photo"></i>
                                        <span>{{__('db.One Column Banner')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="two-c-banner-widget">
                                        <i class="ti ti-layout-columns"></i>
                                        <span>{{__('db.Two Column Banner')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="three-c-banner-widget">
                                        <i class="ti ti-layout-distribute-horizontal"></i>
                                        <span>{{__('db.Three Column Banner')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="image-slider-widget">
                                        <i class="ti ti-slideshow"></i>
                                        <span>{{__('db.Image Slider Widget')}}</span>
                                    </div>
                                </div>

                                <h6 class="widget-category-title">{{__('db.Products & Brands')}}</h6>
                                <div class="widget-grid">
                                    <div class="widget-add-card" data-class="product-category-widget">
                                        <i class="ti ti-category"></i>
                                        <span>{{__('db.Product Category')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="category-slider-widget">
                                        <i class="ti ti-carousel"></i>
                                        <span>{{__('db.Category Slider')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="product-collection-widget">
                                        <i class="ti ti-shopping-bag"></i>
                                        <span>{{__('db.Product Collection')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="tab-product-collection-widget">
                                        <i class="ti ti-layout-board"></i>
                                        <span>{{__('db.Tabbed Product Collections')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="brand-slider-widget">
                                        <i class="ti ti-tags"></i>
                                        <span>{{__('db.Brand Slider')}}</span>
                                    </div>
                                </div>

                                <h6 class="widget-category-title">{{__('db.Advanced Widgets')}}</h6>
                                <div class="widget-grid">
                                    <div class="widget-add-card" data-class="hero-video-widget">
                                        <i class="ti ti-video-camera"></i>
                                        <span>{{__('db.Hero Video Banner')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="masonry-product-widget">
                                        <i class="ti ti-layout-grid3"></i>
                                        <span>{{__('db.Masonry Product Grid')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="deal-of-the-day-widget">
                                        <i class="ti ti-alarm-clock"></i>
                                        <span>{{__('db.Deal of the Day')}}</span>
                                    </div>
                                    <div class="widget-add-card" data-class="recent-blog-widget">
                                        <i class="ti ti-write"></i>
                                        <span>{{__('db.Recent Blog Posts')}}</span>
                                    </div>
                                </div></div>


            </div>
        </div>
</div>
            </div>

        </form>

        <ul class="widgets d-none">
            <div class="text-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Text')}}</span>
        </div>
        <div class="widget-actions">
            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
        </div>
    </div>
    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Title')}}</label>
                            <input type="text" name="text_title" class="form-control">
                            <label>{{__('db.Text')}}</label>
                            <textarea name="text_content" class="form-control"></textarea>
                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="text-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="product-category-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Product Category')}}</span>
        </div>
        <div class="widget-actions">
            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
        </div>
    </div>
    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Title')}}</label>
                            <input type="text" name="product_category_title" class="form-control">

                            <label>{{__('db.Choose Category')}}</label>
                            <select name="product_category_id" class="selectpicker form-control">
                                @foreach($categories as $cat)
                                <option value="{{$cat->id}}">{{$cat->name}}</option>
                                @endforeach
                            </select>

                            <label>{{__('db.Layout Type')}}</label>
                            <select name="product_category_type" class="selectpicker form-control">
                                <option value="gallery">Gallery</option>
                                <option value="slider">Slider</option>
                            </select>

                            <label>{{__('db.Slider Loop')}}</label>
                            <select name="product_category_slider_loop" class="selectpicker form-control">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>

                            <label>{{__('db.Number of products to show')}}</label>
                            <input type="text" name="product_category_limit" class="form-control" value="10">

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="product-category-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="category-slider-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Category Slider')}}</span>
        </div>
        <div class="widget-actions">
            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
        </div>
    </div>
    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Title')}}</label>
                            <input type="text" name="category_slider_title" class="form-control">

                            <label>{{__('db.Choose Categories')}}</label>
                            <select name="category_slider_ids[]" class="selectpicker form-control" multiple>
                                @foreach($categories as $cat)
                                <option value="{{$cat->id}}">{{$cat->name}}</option>
                                @endforeach
                            </select>

                            <label>{{__('db.Slider Loop')}}</label>
                            <select name="category_slider_loop" class="selectpicker form-control">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="category-slider-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="product-collection-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Product Collection')}}</span>
        </div>
        <div class="widget-actions">
            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
        </div>
    </div>
    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Title')}}</label>
                            <input type="text" name="product_collection_title" class="form-control">

                            <label>{{__('db.Choose Collection')}}</label>
                            <select name="product_collection_id" class="selectpicker form-control">
                                @foreach($collections as $collection)
                                <option value="{{$collection->id}}">{{$collection->name}}</option>
                                @endforeach
                            </select>

                            <label>{{__('db.Layout Type')}}</label>
                            <select name="product_collection_type" class="selectpicker form-control">
                                <option value="gallery">Gallery</option>
                                <option value="slider">Slider</option>
                            </select>

                            <label>{{__('db.Slider Loop')}}</label>
                            <select name="product_collection_slider_loop" class="selectpicker form-control">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>

                            <label>{{__('db.Number of products to show')}}</label>
                            <input type="text" name="product_collection_limit" class="form-control" value="10">

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="product-collection-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="tab-product-collection-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Tabbed Product Collections')}}</span>
        </div>
        <div class="widget-actions">
            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
        </div>
    </div>
    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Choose collections')}}</label>
                            <select name="tab_product_collection_ids[]" class="selectpicker form-control" multiple>
                                @foreach($collections as $collection)
                                <option value="{{$collection->id}}">{{$collection->name}}</option>
                                @endforeach
                            </select>

                            <label>{{__('db.Layout Type')}}</label>
                            <select name="tab_product_collection_type" class="selectpicker form-control">
                                <option value="gallery">Gallery</option>
                                <option value="slider">Slider</option>
                            </select>

                            <label>{{__('db.Slider Loop')}}</label>
                            <select name="tab_product_collection_slider_loop" class="selectpicker form-control">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>

                            <label>{{__('db.Number of products to show')}}</label>
                            <input type="text" name="tab_product_collection_limit" class="form-control" value="10">

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="tab-product-collection-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="brand-slider-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Brand Slider')}}</span>
        </div>
        <div class="widget-actions">
            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
        </div>
    </div>
    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Title')}}</label>
                            <input type="text" name="brand_slider_title" class="form-control">

                            <label>{{__('db.Choose Brands')}}</label>
                            <select name="brand_slider_ids[]" class="selectpicker form-control" multiple>
                                @foreach($brands as $brand)
                                <option value="{{$brand->id}}">{{$brand->title}}</option>
                                @endforeach
                            </select>

                            <label>{{__('db.Slider Loop')}}</label>
                            <select name="brand_slider_loop" class="selectpicker form-control">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="brand-slider-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="hero-video-widget" style="display:none">
                <li class="sortable-item">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
                            <span class="widget-label font-weight-bold">{{__('db.Hero Video Banner')}}</span>
                        </div>
                        <div class="widget-actions">
                            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
                            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
                        </div>
                    </div>
                    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Video URL')}}</label>
                            <input type="text" name="settings[video_url]" class="form-control" placeholder="https://www.youtube.com/...">
                            <label>{{__('db.Title')}}</label>
                            <input type="text" name="settings[title]" class="form-control">
                            <label>{{__('db.Subtitle')}}</label>
                            <input type="text" name="settings[subtitle]" class="form-control">
                            <label>{{__('db.Button Text')}}</label>
                            <input type="text" name="settings[button_text]" class="form-control">
                            <label>{{__('db.Button Link')}}</label>
                            <input type="text" name="settings[button_link]" class="form-control">

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                <input type="hidden" name="widget_name" value="hero-video-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="masonry-product-widget" style="display:none">
                <li class="sortable-item">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
                            <span class="widget-label font-weight-bold">{{__('db.Masonry Product Grid')}}</span>
                        </div>
                        <div class="widget-actions">
                            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
                            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
                        </div>
                    </div>
                    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Choose Category')}}</label>
                            <select name="settings[category_id]" class="form-control">
                                @foreach($categories as $cat)
                                <option value="{{$cat->id}}">{{$cat->name}}</option>
                                @endforeach
                            </select>
                            <label>{{__('db.Limit')}}</label>
                            <input type="number" name="settings[limit]" class="form-control" value="6">

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                <input type="hidden" name="widget_name" value="masonry-product-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="deal-of-the-day-widget" style="display:none">
                <li class="sortable-item">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
                            <span class="widget-label font-weight-bold">{{__('db.Deal of the Day')}}</span>
                        </div>
                        <div class="widget-actions">
                            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
                            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
                        </div>
                    </div>
                    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Title')}}</label>
                            <input type="text" name="settings[title]" class="form-control" value="Deal of the Day">
                            <label>{{__('db.Product ID')}}</label>
                            <input type="number" name="settings[product_id]" class="form-control" placeholder="1">
                            <label>{{__('db.End Date/Time')}}</label>
                            <input type="datetime-local" name="settings[end_date]" class="form-control">

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                <input type="hidden" name="widget_name" value="deal-of-the-day-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="recent-blog-widget" style="display:none">
                <li class="sortable-item">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
                            <span class="widget-label font-weight-bold">{{__('db.Recent Blog Posts')}}</span>
                        </div>
                        <div class="widget-actions">
                            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
                            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
                            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
                        </div>
                    </div>
                    <div class="widget-form-container" style="display:none;">
                        <hr>
                        <form>
                            <label>{{__('db.Title')}}</label>
                            <input type="text" name="settings[title]" class="form-control" value="Latest from the Blog">
                            <label>{{__('db.Limit')}}</label>
                            <input type="number" name="settings[limit]" class="form-control" value="3">

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                <input type="hidden" name="widget_name" value="recent-blog-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div></ul>
    </div>
</section>

<!-- Widget Configuration Modal -->
<div id="widgetConfigModal" tabindex="-1" role="dialog" aria-labelledby="widgetConfigModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="widgetConfigModalLabel" class="modal-title">{{__('db.Configure Widget')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="ti ti-x"></i></span></button>
            </div>
            <div class="modal-body" id="widgetConfigModalBody">
                <!-- Form will be dynamically moved here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('db.Close')}}</button>
                <button type="button" class="btn btn-primary btn-save-modal">{{__('db.Save changes')}}</button>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
{!! file_get_contents(Module::find('Ecommerce')->getPath(). "/assets/js/sortable2.js") !!}
</script>
<script type="text/javascript">
    "use strict";

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var container = document.getElementById('layout');

        new Sortable(container, {
        group: {
            name: 'shared',
            pull: false
        },
        animation: 100,
        onUpdate: function(e) {
            var itemEl = e.item;
            widgetOrder(e.item.parentNode.id);
        },
        onAdd: function(e) {
            var item = e.item;
            if (item.classList.contains('widget-add-card')) {
                var widgetClass = item.getAttribute('data-class');
                var templateLi = $('.' + widgetClass).find('li').clone()[0];
                $(templateLi).find('input[name="widget_name"]').val(widgetClass);
                $(templateLi).addClass('new');

                item.parentNode.replaceChild(templateLi, item);
                widgetOrder('layout');
                if(typeof widgetAdd === 'function') {
                    widgetAdd();
                }
            }
        }
    });

    document.querySelectorAll('.widget-grid').forEach(function(grid) {
        new Sortable(grid, {
            group: {
                name: 'shared',
                pull: 'clone',
                put: false
            },
            sort: false,
            animation: 100
        });
    });

    function text_editor(id) {
        tinymce.init({
            selector: id,
            height: 130,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor textcolor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table contextmenu paste code wordcount'
            ],
            toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
            branding: false,
        });
    }

    function widgetAdd() {
        $('#layout li').each(function(){
            var item = $(this);
            var order = item.index();
            item.find('input[name="order"]').val(order);
            var data = item.find('form').serializeArray();
            var page_id = $('#page_id').val();

            data.push({name: "page_id", value: page_id});

            $.ajax({
                type: "post",
                data: $.param(data),
                url: "{{url('pages/widget/store')}}/",
                success: function(data) {
                    var message = '<div class="ajax-message"><div class="alert alert-success alert-dismissible text-center mar-bot-30"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{trans("db.Widget saved")}}</div></div>';
                    $('body section').append(message);
                    $("div.alert").delay(3000).slideUp(800);
                }
            })
        })
    }


        var currentWidgetFormLi = null;

    $(document).on('click', '.btn-edit-widget', function(e) {
        e.preventDefault();
        var li = $(this).closest('li');
        var formContainer = li.find('.widget-form-container');

        // Move the form out of the hidden container and into the modal
        var form = formContainer.find('form');
        $('#widgetConfigModalBody').empty().append(form);

        currentWidgetFormLi = li;
        $('#widgetConfigModal').modal('show');
    });

    $('#widgetConfigModal').on('hidden.bs.modal', function () {
        // Move the form back to its original li when modal closes
        if(currentWidgetFormLi) {
            var form = $('#widgetConfigModalBody').find('form');
            currentWidgetFormLi.find('.widget-form-container').append(form);
            currentWidgetFormLi = null;
        }
    });

    $(document).on('click', '.btn-save-modal', function(e) {
        e.preventDefault();
        $('#widgetConfigModal').modal('hide');
    });

    $(document).on('click', '.btn-delete-widget', function(e) {
        e.preventDefault();
        var li = $(this).closest('li');
        var id = li.find('input[name="id"]').val();
        li.remove();
        if(id) {
            $.ajax({
                type: "get",
                url: "{{url('pages/widget/delete')}}/" + id,
                success: function(data) {
                }
            })
        }
    });

    $(document).on('click', '.btn-duplicate-widget', function(e) {
        e.preventDefault();
        var li = $(this).closest('li');
        var clone = li.clone();
        clone.find('input[name="id"]').val('');
        clone.addClass('new');
        li.after(clone);
        widgetAdd();
    });

    $('input[name="page_name"]').on('input', function() {
        var slug = $(this).val().toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
        $('input[name="slug"]').val(slug);
    });

    $('input[name="page_name"], input[name="slug"]').focusout(function(){
        var slug = $('input[name="slug"]').val();
        $.ajax({
            type: "get",
            url: "{{url('pages')}}/" + slug,
            success: function(data) {
                $('input[name="slug"]').val(data);
            }
        })

        if($('input[name="page_name"]').val().length > 1){
            $('#draft').prop('disabled',false);
            $('#publish').prop('disabled',false);
        }else{
            $('#draft').prop('disabled',true);
            $('#publish').prop('disabled',true);
        }
    })

        $(document).on('click', '.widget-add-card', function() {
        var widget = $(this).attr('data-class');
        var clone = $('.'+widget).find('li').clone();
        clone.find('input[name="widget_name"]').val(widget);
        clone.addClass('new');
        $('#layout').append(clone);
        if(typeof widgetAdd === 'function') {
            widgetAdd();
        }
    });

    function updateDisplay(temp) {
        if (temp === 'home') {
            $('#text-block').css('display', 'none');
            $('#text-block textarea').attr('name', 'description').attr('id', 'description');
            $('#page-template').css('display', 'block');
        } else if (temp === 'contact') {
            $('#text-block').css('display', 'block');
            $('#page-template').css('display', 'none');
        } else if (temp === 'faq') {
            $('#text-block').css('display', 'block');
            $('#page-template').css('display', 'none');
        } else {
            $('#text-block').css('display', 'block');
            $('#text-block textarea').attr('name', 'description').attr('id', 'description');
            $('#page-template').css('display', 'none');
            text_editor('#description');
        }
    }

    $(document).ready(function() {
        var temp = $('#template').val();
        updateDisplay(temp);
    });

    $('#template').change(function() {
        var temp = $(this).val();
        updateDisplay(temp);
    });

    text_editor('#description');

</script>
@endpush