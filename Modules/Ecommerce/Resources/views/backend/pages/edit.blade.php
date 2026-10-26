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
        <form id="add-page-form" method="post" action="{{route('page.update')}}" class="form-horizontal" enctype='multipart/form-data'>

            @csrf
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            {{__('db.Edit Page')}}
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
                                    <ul id="layout" class="widgets">
                                        @if(isset($page_widgets))
                                            @foreach($page_widgets as $widget)
                                                @if($widget->name == 'text-widget')
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
                                                            <input type="text" name="text_title" class="form-control" value="{{$widget->text_title}}">
                                                            <label>{{__('db.Text')}}</label>
                                                            <textarea name="text_content" class="form-control">{{$widget->text_content}}</textarea>
                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="text-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'three-c-banner-widget')
                                                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Three Column Banner')}}</span>
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
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Banner Image')}} 1</label>
                                                                    <input type="file" name="three_c_banner_image1" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Link')}} 1</label>
                                                                    <input type="text" name="three_c_banner_link1" class="form-control" value="{{$widget->three_c_banner_link1}}">
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Banner Image')}} 2</label>
                                                                    <input type="file" name="three_c_banner_image2" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Link')}} 2</label>
                                                                    <input type="text" name="three_c_banner_link2" class="form-control" value="{{$widget->three_c_banner_link2}}">
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Banner Image')}} 3</label>
                                                                    <input type="file" name="three_c_banner_image3" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Link')}} 3</label>
                                                                    <input type="text" name="three_c_banner_link3" class="form-control" value="{{$widget->three_c_banner_link3}}">
                                                                </div>
                                                            </div>
                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                                                <input type="hidden" name="widget_name" value="three-c-banner-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'two-c-banner-widget')
                                                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Two Column Banner')}}</span>
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
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Banner Image')}} 1</label>
                                                                    <input type="file" name="two_c_banner_image1" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Link')}} 1</label>
                                                                    <input type="text" name="two_c_banner_link1" class="form-control" value="{{$widget->two_c_banner_link1}}">
                                                                </div>
                                                            </div>
                                                            <hr>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Banner Image')}} 2</label>
                                                                    <input type="file" name="two_c_banner_image2" class="form-control">
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label>{{__('db.Link')}} 2</label>
                                                                    <input type="text" name="two_c_banner_link2" class="form-control" value="{{$widget->two_c_banner_link2}}">
                                                                </div>
                                                            </div>
                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                                                <input type="hidden" name="widget_name" value="two-c-banner-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'one-c-banner-widget')
                                                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.One Column Banner')}}</span>
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
                                                            <div class="row">
                                                                <div class="col-md-4">








                                                                    <label>{{__('db.Banner Image')}}</label>
                                                                    <input type="file" name="one_c_banner_image1" class="form-control">


</div>
                                                                <div class="col-md-4">
                                                                    <label>{{__('db.Mobile Version')}}</label>
                                                                    <input type="file" name="one_c_banner_image1_mobile" class="form-control">
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <label>{{__('db.Link')}}</label>
                                                                    <input type="text" name="one_c_banner_link1" class="form-control" value="{{$widget->one_c_banner_link1}}">
                                                                </div>
                                                            </div>
                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                                                <input type="hidden" name="widget_name" value="one-c-banner-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'product-category-widget')
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
                                                            <input type="text" name="product_category_title" class="form-control" value="{{$widget->product_category_title}}">

                                                            <label>{{__('db.Choose Category')}}</label>
                                                            <select name="product_category_id" class="selectpicker form-control">
                                                                @foreach($categories as $cat)
                                                                <option @if($widget->product_category_id == $cat->id) selected @endif value="{{$cat->id}}">{{$cat->name}}</option>
                                                                @endforeach
                                                            </select>

                                                            <label>{{__('db.Layout Type')}}</label>
                                                            <select name="product_category_type" class="selectpicker form-control">
                                                                <option @if($widget->product_category_type == 'gallery') selected @endif value="gallery">Gallery</option>
                                                                <option @if($widget->product_category_type == 'slider') selected @endif value="slider">Slider</option>
                                                            </select>

                                                            <label>{{__('db.Slider Loop')}}</label>
                                                            <select name="product_category_slider_loop" class="selectpicker form-control">
                                                                <option @if($widget->product_category_slider_loop == 'true') selected @endif value="true">Yes</option>
                                                                <option @if($widget->product_category_slider_loop == 'false') selected @endif value="false">No</option>
                                                            </select>

                                                            <label>{{__('db.Number of products to show')}}</label>
                                                            <input type="text" name="product_category_limit" class="form-control" value="{{$widget->product_category_limit}}">

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="product-category-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'category-slider-widget')
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
                                                            <input type="text" name="category_slider_title" class="form-control" value="{{$widget->category_slider_title}}">

                                                            <label>{{__('db.Choose Categories')}}</label>
                                                            <select name="category_slider_ids[]" class="selectpicker form-control" multiple>
                                                                @foreach($categories as $cat)
                                                                <option @if(in_array($cat->id,explode(',',$widget->category_slider_ids))) selected @endif value="{{$cat->id}}">{{$cat->name}}</option>
                                                                @endforeach
                                                            </select>

                                                            <label>{{__('db.Slider Loop')}}</label>
                                                            <select name="category_slider_loop" class="selectpicker form-control">
                                                                <option @if($widget->category_slider_loop == 'yes') selected @endif value="yes">Yes</option>
                                                                <option @if($widget->category_slider_loop == 'no') selected @endif value="no">No</option>
                                                            </select>

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="category-slider-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'product-collection-widget')
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
                                                            <input type="text" name="product_collection_title" class="form-control" value="{{$widget->product_collection_title}}">

                                                            <label>{{__('db.Choose Collection')}}</label>
                                                            <select name="product_collection_id" class="selectpicker form-control">
                                                                @foreach($collections as $collection)
                                                                <option @if($widget->product_collection_id == $collection->id) selected @endif value="{{$collection->id}}">{{$collection->name}}</option>
                                                                @endforeach
                                                            </select>

                                                            <label>{{__('db.Layout Type')}}</label>
                                                            <select name="product_collection_type" class="selectpicker form-control">
                                                                <option @if($widget->product_collection_type == 'gallery') selected @endif value="gallery">Gallery</option>
                                                                <option @if($widget->product_collection_type == 'slider') selected @endif value="slider">Slider</option>
                                                            </select>

                                                            <label>{{__('db.Slider Loop')}}</label>
                                                            <select name="product_collection_slider_loop" class="selectpicker form-control">
                                                                <option @if($widget->product_collection_slider_loop == 'true') selected @endif value="true">Yes</option>
                                                                <option @if($widget->product_collection_slider_loop == 'false') selected @endif value="false">No</option>
                                                            </select>

                                                            <label>{{__('db.Number of products to show')}}</label>
                                                            <input type="text" name="product_collection_limit" class="form-control" value="{{$widget->product_collection_limit}}">

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="product-collection-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'tab-product-collection-widget')
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
                                                            <label>{{__('db.Choose Collections')}}</label>
                                                            <select name="tab_product_collection_ids[]" class="selectpicker form-control" multiple>
                                                                @foreach($collections as $collection)
                                                                <option @if(in_array($collection->id,explode(',',$widget->tab_product_collection_id))) selected @endif value="{{$collection->id}}">{{$collection->name}}</option>
                                                                @endforeach
                                                            </select>

                                                            <label>{{__('db.Layout Type')}}</label>
                                                            <select name="tab_product_collection_type" class="selectpicker form-control">
                                                                <option @if($widget->tab_product_collection_type == 'gallery') selected @endif value="gallery">Gallery</option>
                                                                <!-- <option @if($widget->tab_product_collection_type == 'slider') selected @endif value="slider">Slider</option> -->
                                                            </select>

                                                            <!-- <label>{{__('db.Slider Loop')}}</label>
                                                            <select name="tab_product_collection_slider_loop" class="selectpicker form-control">
                                                                <option @if($widget->tab_product_collection_slider_loop == 'yes') selected @endif value="yes">Yes</option>
                                                                <option @if($widget->tab_product_collection_slider_loop == 'no') selected @endif value="no">No</option>
                                                            </select> -->

                                                            <label>{{__('db.Number of products to show')}}</label>
                                                            <input type="text" name="tab_product_collection_limit" class="form-control" value="{{$widget->tab_product_collection_limit}}">

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="tab-product-collection-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'brand-slider-widget')
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
                                                            <input type="text" name="brand_slider_title" class="form-control" value="{{$widget->brand_slider_title}}">

                                                            <label>{{__('db.Choose Brands')}}</label>
                                                            <select name="brand_slider_ids[]" class="selectpicker form-control" multiple>
                                                                @foreach($brands as $brand)
                                                                <option @if(in_array($brand->id,explode(',',$widget->brand_slider_ids))) selected @endif value="{{$brand->id}}">{{$brand->title}}</option>
                                                                @endforeach
                                                            </select>

                                                            <label>{{__('db.Slider Loop')}}</label>
                                                            <select name="brand_slider_loop" class="selectpicker form-control">
                                                                <option @if($widget->brand_slider_loop == 'yes') selected @endif value="yes">Yes</option>
                                                                <option @if($widget->brand_slider_loop == 'no') selected @endif value="no">No</option>
                                                            </select>

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="brand-slider-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'image-slider-widget')
                                                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Image Slider')}}</span>
        </div>
        <div class="widget-actions">
            <button class="btn btn-sm btn-info btn-edit-widget" title="{{__('db.Edit')}}"><i class="ti ti-settings"></i></button>
            <button class="btn btn-sm btn-secondary btn-duplicate-widget" title="{{__('db.Duplicate')}}"><i class="ti ti-copy"></i></button>
            <button class="btn btn-sm btn-danger btn-delete-widget" title="{{__('db.Delete')}}"><i class="ti ti-trash"></i></button>
        </div>
    </div>
    <div class="widget-form-container" style="display:none;">
                                                        <hr>
                                                        <form enctype="multipart/form-data">
                                                            @php
                                                                $links = explode(',',$widget->slider_links);
                                                                $images = explode(',',$widget->slider_images);
                                                            @endphp

                                                            @foreach($images as $key=>$image)
                                                            @if(strlen($image)>0)
                                                                <div class="row ">
                                                                    <div class="col-sm-4 mt-2">
                                                                        <input class="form-control" type="hidden" name="slide_lg_prev[]" required value="{{$image}}">
                                                                        <img src="{{url('frontend/images/slider_widget/desktop')}}/{{$image}}" style="width:50px" />
                                                                    </div>
                                                                    <div class="col-sm-4 mt-2">
                                                                        @php
                                                                            $path = url('frontend/images/slider_widget/mobile')."/".$image;
                                                                        @endphp
                                                                        @if(file_exists($path))
                                                                        <input class="form-control" type="hidden" name="slide_m_prev[]" value="{{$image}}">
                                                                        <img src="{{url('frontend/images/slider_widget/mobile')}}/{{$image}}" style="width:50px" />
                                                                        @else
                                                                        <input class="form-control" type="hidden" name="slide_m_prev[]" value="">
                                                                        @endif
                                                                    </div>
                                                                    <div class="col-sm-3 mt-2">
                                                                        <input class="form-control" type="text" name="link_prev[]" value="@if(isset($links[$key])){{$links[$key]}}@endif">
                                                                    </div>
                                                                    <div class="col-sm-1 mt-2">
                                                                        <a class="btn btn-danger btn-sm remove"><i class="ti ti-x"></i></a>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            @endforeach

                                                            <div class="add-img-slider">
                                                                <div class="row ">
                                                                    <div class="col-sm-4 mt-2">
                                                                        <label>{{ __('db.Image') }} ({{ __('db.Desktop') }})<span class="required">*</span></label><br>
                                                                        <input class="form-control" type="file" name="slide_lg[]" required>
                                                                    </div>
                                                                    <div class="col-sm-4 mt-2">
                                                                        <label>{{ __('db.Image') }} ({{ __('db.Mobile') }})</label><br>
                                                                        <input class="form-control" type="file" name="slide_m[]">
                                                                    </div>
                                                                    <div class="col-sm-3 mt-2">
                                                                        <label>{{ __('db.Link') }}</label><br>
                                                                        <input class="form-control" type="text" name="link[]">
                                                                    </div>
                                                                    <div class="col-sm-1 mt-2">
                                                                        <label>{{ __('Add') }}</label>
                                                                        <a class="btn btn-success btn-sm add-more"><i class="ti ti-plus"></i></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="image-slider-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif
                                                @if($widget->name == 'hero-video-widget')
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
                                                            @php $settings = is_string($widget->settings) ? json_decode($widget->settings, true) : $widget->settings; @endphp
                                                            <input type="text" name="settings[video_url]" class="form-control" placeholder="https://www.youtube.com/..." value="{{$settings['video_url'] ?? ''}}">
                                                            <label>{{__('db.Title')}}</label>
                                                            <input type="text" name="settings[title]" class="form-control" value="{{$settings['title'] ?? ''}}">
                                                            <label>{{__('db.Subtitle')}}</label>
                                                            <input type="text" name="settings[subtitle]" class="form-control" value="{{$settings['subtitle'] ?? ''}}">
                                                            <label>{{__('db.Button Text')}}</label>
                                                            <input type="text" name="settings[button_text]" class="form-control" value="{{$settings['button_text'] ?? ''}}">
                                                            <label>{{__('db.Button Link')}}</label>
                                                            <input type="text" name="settings[button_link]" class="form-control" value="{{$settings['button_link'] ?? ''}}">

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="hero-video-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'masonry-product-widget')
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
                                                            @php $settings = is_string($widget->settings) ? json_decode($widget->settings, true) : $widget->settings; @endphp
                                                            <label>{{__('db.Choose Category')}}</label>
                                                            <select name="settings[category_id]" class="form-control">
                                                                @foreach($categories as $cat)
                                                                <option value="{{$cat->id}}" {{isset($settings['category_id']) && $settings['category_id'] == $cat->id ? 'selected' : ''}}>{{$cat->name}}</option>
                                                                @endforeach
                                                            </select>
                                                            <label>{{__('db.Limit')}}</label>
                                                            <input type="number" name="settings[limit]" class="form-control" value="{{$settings['limit'] ?? 6}}">

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="masonry-product-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'deal-of-the-day-widget')
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
                                                            @php $settings = is_string($widget->settings) ? json_decode($widget->settings, true) : $widget->settings; @endphp
                                                            <label>{{__('db.Title')}}</label>
                                                            <input type="text" name="settings[title]" class="form-control" value="{{$settings['title'] ?? 'Deal of the Day'}}">
                                                            <label>{{__('db.Product ID')}}</label>
                                                            <input type="number" name="settings[product_id]" class="form-control" placeholder="1" value="{{$settings['product_id'] ?? ''}}">
                                                            <label>{{__('db.End Date/Time')}}</label>
                                                            <input type="datetime-local" name="settings[end_date]" class="form-control" value="{{$settings['end_date'] ?? ''}}">

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="deal-of-the-day-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif

                                                @if($widget->name == 'recent-blog-widget')
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
                                                            @php $settings = is_string($widget->settings) ? json_decode($widget->settings, true) : $widget->settings; @endphp
                                                            <label>{{__('db.Title')}}</label>
                                                            <input type="text" name="settings[title]" class="form-control" value="{{$settings['title'] ?? 'Latest from the Blog'}}">
                                                            <label>{{__('db.Limit')}}</label>
                                                            <input type="number" name="settings[limit]" class="form-control" value="{{$settings['limit'] ?? 3}}">

                                                            <div class="form-group mt-2" style="display:none;">
                                                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                                                <input type="hidden" name="widget_name" value="recent-blog-widget">
                                                                <input type="hidden" name="order" value="{{$widget->order}}">
                                                                <input type="hidden" name="id" value="{{$widget->id}}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </li>
                                                @endif
                                            @endforeach
                                        @endif
                                    </ul>
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
                            <button id="draft" type="submit" class="btn btn-warning"><i class="ti ti-save"></i> {{__('db.Save as Draft')}}</button>
                            <button id="publish" type="submit" class="btn btn-primary"><i class="ti ti-check"></i> {{__('db.Publish')}}</button>
                            <input id="page_id" name="page_id" type="hidden" value="{{$id}}">
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
                                    @if(empty($supported_widgets) || in_array('text-widget', $supported_widgets))
<div class="widget-add-card" data-class="text-widget">
                                        <i class="ti ti-align-left"></i>
                                        <span>{{__('db.Text')}}</span>
                                    </div>
@endif
                                </div>

                                <h6 class="widget-category-title">{{__('db.Banners & Sliders')}}</h6>
                                <div class="widget-grid">
                                    @if(empty($supported_widgets) || in_array('one-c-banner-widget', $supported_widgets))
<div class="widget-add-card" data-class="one-c-banner-widget">
                                        <i class="ti ti-photo"></i>
                                        <span>{{__('db.One Column Banner')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('two-c-banner-widget', $supported_widgets))
<div class="widget-add-card" data-class="two-c-banner-widget">
                                        <i class="ti ti-layout-columns"></i>
                                        <span>{{__('db.Two Column Banner')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('three-c-banner-widget', $supported_widgets))
<div class="widget-add-card" data-class="three-c-banner-widget">
                                        <i class="ti ti-layout-distribute-horizontal"></i>
                                        <span>{{__('db.Three Column Banner')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('image-slider-widget', $supported_widgets))
<div class="widget-add-card" data-class="image-slider-widget">
                                        <i class="ti ti-slideshow"></i>
                                        <span>{{__('db.Image Slider Widget')}}</span>
                                    </div>
@endif
                                </div>

                                <h6 class="widget-category-title">{{__('db.Products & Brands')}}</h6>
                                <div class="widget-grid">
                                    @if(empty($supported_widgets) || in_array('product-category-widget', $supported_widgets))
<div class="widget-add-card" data-class="product-category-widget">
                                        <i class="ti ti-category"></i>
                                        <span>{{__('db.Product Category')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('category-slider-widget', $supported_widgets))
<div class="widget-add-card" data-class="category-slider-widget">
                                        <i class="ti ti-carousel"></i>
                                        <span>{{__('db.Category Slider')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('product-collection-widget', $supported_widgets))
<div class="widget-add-card" data-class="product-collection-widget">
                                        <i class="ti ti-shopping-bag"></i>
                                        <span>{{__('db.Product Collection')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('tab-product-collection-widget', $supported_widgets))
<div class="widget-add-card" data-class="tab-product-collection-widget">
                                        <i class="ti ti-layout-board"></i>
                                        <span>{{__('db.Tabbed Product Collections')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('brand-slider-widget', $supported_widgets))
<div class="widget-add-card" data-class="brand-slider-widget">
                                        <i class="ti ti-tags"></i>
                                        <span>{{__('db.Brand Slider')}}</span>
                                    </div>
@endif
                                </div>

                                <h6 class="widget-category-title">{{__('db.Advanced Widgets')}}</h6>
                                <div class="widget-grid">
                                    @if(empty($supported_widgets) || in_array('hero-video-widget', $supported_widgets))
<div class="widget-add-card" data-class="hero-video-widget">
                                        <i class="ti ti-video-camera"></i>
                                        <span>{{__('db.Hero Video Banner')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('masonry-product-widget', $supported_widgets))
<div class="widget-add-card" data-class="masonry-product-widget">
                                        <i class="ti ti-layout-grid3"></i>
                                        <span>{{__('db.Masonry Product Grid')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('deal-of-the-day-widget', $supported_widgets))
<div class="widget-add-card" data-class="deal-of-the-day-widget">
                                        <i class="ti ti-alarm-clock"></i>
                                        <span>{{__('db.Deal of the Day')}}</span>
                                    </div>
@endif
                                    @if(empty($supported_widgets) || in_array('recent-blog-widget', $supported_widgets))
                                    <div class="widget-add-card" data-class="recent-blog-widget">
                                        <i class="ti ti-write"></i>
                                        <span>{{__('db.Recent Blog Posts')}}</span>
                                    </div>
                                    @endif
                                </div>
                </div>
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
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                <input type="hidden" name="widget_name" value="text-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="three-c-banner-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Three Column Banner')}}</span>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <label>{{__('db.Banner Image')}} 1</label>
                                    <input type="file" name="three_c_banner_image1" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label>{{__('db.Link')}} 1</label>
                                    <input type="text" name="three_c_banner_link1" class="form-control">
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <label>{{__('db.Banner Image')}} 2</label>
                                    <input type="file" name="three_c_banner_image2" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label>{{__('db.Link')}} 2</label>
                                    <input type="text" name="three_c_banner_link2" class="form-control">
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <label>{{__('db.Banner Image')}} 3</label>
                                    <input type="file" name="three_c_banner_image3" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label>{{__('db.Link')}} 3</label>
                                    <input type="text" name="three_c_banner_link3" class="form-control">
                                </div>
                            </div>
                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="three-c-banner-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="two-c-banner-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Two Column Banner')}}</span>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <label>{{__('db.Banner Image')}} 1</label>
                                    <input type="file" name="two_c_banner_image1" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label>{{__('db.Link')}} 1</label>
                                    <input type="text" name="two_c_banner_link1" class="form-control">
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <label>{{__('db.Banner Image')}} 2</label>
                                    <input type="file" name="two_c_banner_image2" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label>{{__('db.Link')}} 2</label>
                                    <input type="text" name="two_c_banner_link2" class="form-control">
                                </div>
                            </div>
                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="two-c-banner-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="one-c-banner-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.One Column Banner')}}</span>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <label>{{__('db.Banner Image')}}</label>
                                    <input type="file" name="one_c_banner_image1" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label>{{__('db.Link')}}</label>
                                    <input type="text" name="one_c_banner_link1" class="form-control">
                                </div>
                            </div>
                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <a href="" class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></a>
                                <input type="hidden" name="widget_name" value="one-c-banner-widget">
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
                                <option value="yes">Yes</option>
                                <option value="false">No</option>
                            </select>

                            <label>{{__('db.Number of products to show')}}</label>
                            <input type="text" name="product_category_limit" class="form-control" value="10">

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
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
                                <option value="yes">Yes</option>
                                <option value="false">No</option>
                            </select>

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
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
                                <option value="yes">Yes</option>
                                <option value="false">No</option>
                            </select>

                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                <input type="hidden" name="widget_name" value="brand-slider-widget">
                                <input type="hidden" name="order">
                                <input type="hidden" name="id">
                            </div>
                        </form>
                    </div>
                </li>
            </div>

            <div class="image-slider-widget">
                <li class="sortable-item">
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <i class="ti ti-menu mr-2 handle text-muted" style="cursor: grab;"></i>
            <span class="widget-label font-weight-bold">{{__('db.Image Slider')}}</span>
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
                            <div class="add-img-slider">
                                <div class="row ">
                                    <div class="col-sm-4 mt-2">
                                        <label>{{ __('db.Image') }} ({{ __('db.Desktop') }})<span class="required">*</span></label><br>
                                        <input class="form-control" type="file" name="slide_lg[]" required>
                                    </div>
                                    <div class="col-sm-4 mt-2">
                                        <label>{{ __('db.Image') }} ({{ __('db.Mobile') }})</label><br>
                                        <input class="form-control" type="file" name="slide_m[]">
                                    </div>
                                    <div class="col-sm-3 mt-2">
                                        <label>{{ __('db.Link') }}</label><br>
                                        <input class="form-control" type="text" name="link[]">
                                    </div>
                                    <div class="col-sm-1 mt-2">
                                        <label>{{ __('Add') }}</label>
                                        <a class="btn btn-success btn-sm add-more"><i class="ti ti-plus"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-2" style="display:none;">
                                <button class="btn btn-sm btn-primary btn-save"><i class="ti ti-check"></i></button>
                                <button class="btn btn-sm btn-danger btn-delete"><i class="ti ti-trash"></i></button>
                                <input type="hidden" name="widget_name" value="image-slider-widget">
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

    $(document).ready(function(){
        ensureActivationButtons();
        $.ajax({
            type: "get",
            url: "{{url('pages/edit')}}/{{$id}}",
            success: function(data) {
                $('input[name="page_name"]').val(data.page_name);
                $('input[name="slug"]').val(data.slug);
                if(data.description){
                    tinymce.get('description').setContent(data.description);
                }
                $('input[name="meta_title"]').val(data.meta_title);
                $('input[name="meta_description"]').val(data.meta_description);
                $('input[name="og_title"]').val(data.og_title);
                $('input[name="og_description"]').val(data.og_description);
                $('select[name=template]').val(data.template);
                $('select[name=template]').selectpicker('refresh');
                updateDisplay(data.template);
            }
        })

        text_editor('textarea');
    })

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
            persistWidgetOrder();
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
                ensureActivationButtons();
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

    function widgetOrder(parent){
        var itemArray = [];
        $('#' + parent + ' li').each(function(index) {
            var order = $(this).index();
            $(this).find('input[name="order"]').val(order);
        })
    }

    var widgetActivation = @json($page_widgets->pluck('is_active', 'id'));

    function ensureActivationButtons() {
        $('#layout li').each(function() {
            var item = $(this);
            var actions = item.find('.widget-actions');
            if (!actions.length || actions.find('.btn-toggle-widget').length) {
                return;
            }

            var id = item.find('input[name="id"]').val();
            var active = id && Object.prototype.hasOwnProperty.call(widgetActivation, id)
                ? Boolean(widgetActivation[id])
                : true;
            item.toggleClass('widget-inactive', !active);
            item.find('form').append('<input type="hidden" name="is_active" value="' + (active ? 1 : 0) + '">');
            actions.prepend(
                '<button class="btn btn-sm btn-toggle-widget ' + (active ? 'btn-success' : 'btn-warning') + '" ' +
                'data-active="' + (active ? 1 : 0) + '" title="' +
                (active ? @json(__('db.Disable')) : @json(__('db.Enable'))) + '">' +
                (active ? @json(__('db.Active')) : @json(__('db.Inactive'))) +
                '</button> '
            );
        });
    }

    function persistWidgetOrder() {
        var ids = [];
        var hasUnsavedWidget = false;
        $('#layout li').each(function() {
            var id = $(this).find('input[name="id"]').val();
            if (!id) {
                hasUnsavedWidget = true;
                return;
            }
            ids.push(parseInt(id, 10));
        });

        if (hasUnsavedWidget) {
            return $.Deferred().resolve().promise();
        }

        return $.ajax({
            type: 'POST',
            url: "{{route('page.widget.order')}}",
            data: {
                page_id: $('#page_id').val(),
                widget_ids: ids
            }
        });
    }

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

    function saveSingleWidget(formElement) {
        var form = $(formElement);
        var widgetName = form.find('input[name="widget_name"]').val();
        var page_id = $('#page_id').val();

        var isMultipart = (widgetName == 'three-c-banner-widget' || widgetName == 'two-c-banner-widget' || widgetName == 'one-c-banner-widget' || widgetName == 'image-slider-widget');

        var ajaxData;
        var contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
        var processData = true;

        if (isMultipart) {
            ajaxData = new FormData(form[0]);
            ajaxData.append('page_id', page_id);
            contentType = false;
            processData = false;
        } else {
            var dataArray = form.find('input,select,textarea').serializeArray();
            dataArray.push({name: "page_id", value: page_id});
            ajaxData = $.param(dataArray);
        }

        return $.ajax({
            type: "post",
            data: ajaxData,
            url: "{{url('pages/widget/update')}}",
            contentType: contentType,
            processData: processData,
            success: function(data) {
                var message = '<div class="ajax-message"><div class="alert alert-success alert-dismissible text-center mar-bot-30"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{trans("db.Widget saved")}}</div></div>';
                $('body section').append(message);
                $("div.alert").delay(3000).slideUp(800);

                if (data && data.id) {
                    form.find('input[name="id"]').val(data.id);
                    ensureActivationButtons();
                }
            }
        });
    }

    function widgetUpdate() {
        var promises = [];
        $('#layout li').each(function(){
            var item = $(this);
            if (item.hasClass('new')) {
                return;
            }
            var form = item.find('form');
            if (form.length > 0) {
                promises.push(saveSingleWidget(form[0]));
            }
        });
        return promises;
    }

    function widgetAdd() {
        var promises = [];
        $('#layout li.new').each(function(){
            var item = $(this);
            var order = item.index();
            item.find('input[name="order"]').val(order);
            var data = item.find('input,select,textarea').serializeArray();
            var page_id = $('#page_id').val();

            data.push({name: "page_id", value: page_id});

            var promise = $.ajax({
                type: "post",
                data: $.param(data),
                url: "{{url('pages/widget/store')}}",
                success: function(data) {
                    item.removeClass('new');
                    item.find('input[name="id"]').val(data);
                    ensureActivationButtons();
                    var message = '<div class="ajax-message"><div class="alert alert-success alert-dismissible text-center mar-bot-30"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{trans("db.Widget saved")}}</div></div>';
                    $('body section').append(message);
                    $("div.alert").delay(3000).slideUp(800);
                }
            });
            promises.push(promise);
        });
        return promises;
    }

    function pageUpdate() {
        tinyMCE.triggerSave();

        var form = $('#add-page-form')[0];

        var formData = new FormData(form);

        $.ajax({
            type: "post",
            data: formData,
            dataType: 'JSON',
            processData: false,
            contentType: false,
            url: "{{route('page.update')}}",
            success: function(data) {
                var message = '<div class="ajax-message"><div class="alert alert-success alert-dismissible text-center mar-bot-30"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{trans("db.Page saved")}}</div></div>';
                $('body section').append(message);
                $("div.alert").delay(3000).slideUp(800);
                setTimeout(function () {
                    window.location.href = "{{route('page.index')}}";
                }, 3000);
            }
        });
    }

    function saveAll(statusValue, buttonEl) {
        $('input[name="status"]').val(statusValue);
        widgetOrder('layout');

        var updatePromises = widgetUpdate();
        var addPromises = widgetAdd();
        var allPromises = updatePromises.concat(addPromises);

        $.when.apply($, allPromises).then(function() {
            persistWidgetOrder().done(function() {
                pageUpdate();
            }).fail(function() {
                buttonEl.removeAttr('disabled');
            });
        }).fail(function() {
            var message = '<div class="ajax-message"><div class="alert alert-danger alert-dismissible text-center mar-bot-30"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Some widgets failed to save.</div></div>';
            $('body section').append(message);
            $("div.alert").delay(3000).slideUp(800);
            buttonEl.removeAttr('disabled').html(statusValue === 1 ? '<i class="ti ti-check"></i> {{__("db.Publish")}}' : '<i class="ti ti-save"></i> {{__("db.Save as Draft")}}');
        });
    }

    $('#publish').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.attr('disabled','true').html('<span class="spinner-border spinner-border-sm text-light" role="status"></span> {{trans("db.Saving")}}...');
        saveAll(1, btn);
    });

    $('#draft').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.attr('disabled','true').html('<span class="spinner-border spinner-border-sm text-light" role="status"></span> {{trans("db.Saving")}}...');
        saveAll(0, btn);
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
        var form = $('#widgetConfigModalBody').find('form');
        if (form.length > 0) {
            saveSingleWidget(form[0]);
        }
        $('#widgetConfigModal').modal('hide');
    });

    $(document).on('click', '.btn-delete-widget', function(e) {
        e.preventDefault();
        var li = $(this).closest('li');
        var id = li.find('input[name="id"]').val();
        li.remove();
        if(id) {
            $.ajax({
                type: "POST",
                url: "{{url('pages/widget')}}/" + id,
                data: {
                    _method: 'DELETE',
                    page_id: $('#page_id').val()
                },
                success: function(data) {
                    persistWidgetOrder();
                }
            })
        }
    });

    $(document).on('click', '.btn-toggle-widget', function(e) {
        e.preventDefault();
        var button = $(this);
        var item = button.closest('li');
        var id = item.find('input[name="id"]').val();
        if (!id) {
            return;
        }

        var nextActive = button.data('active') ? 0 : 1;
        $.ajax({
            type: 'POST',
            url: "{{url('pages/widget')}}/" + id + '/activation',
            data: {
                page_id: $('#page_id').val(),
                is_active: nextActive
            },
            success: function(data) {
                var active = Boolean(data.is_active);
                button.data('active', active ? 1 : 0)
                    .toggleClass('btn-success', active)
                    .toggleClass('btn-warning', !active)
                    .attr('title', active ? @json(__('db.Disable')) : @json(__('db.Enable')))
                    .text(active ? @json(__('db.Active')) : @json(__('db.Inactive')));
                item.toggleClass('widget-inactive', !active);
                item.find('input[name="is_active"]').val(active ? 1 : 0);
            }
        });
    });

    $(document).on('click', '.btn-duplicate-widget', function(e) {
        e.preventDefault();
        var li = $(this).closest('li');
        var clone = li.clone();
        // Remove the ID so it's treated as new
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
            url: "{{url('pages/edit')}}/{{$id}}/" + slug,
            success: function(data) {
                console.log(data);
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

    $('#template').change(function() {
        var temp = $(this).val();
        updateDisplay(temp);
    });

    text_editor('#description');

    $(document).on('click', '.add-more', function(){
        $('.add-img-slider').append('<div class="row"><div class="col-sm-4 mt-2"><label>{{ __('db.Image') }} ({{ __('db.Desktop') }})<span class="required">*</span></label><br><input class="form-control" type="file" name="slide_lg[]" required></div><div class="col-sm-4 mt-2"><label>{{ __('db.Image') }} ({{ __('db.Mobile') }})</label><br><input class="form-control" type="file" name="slide_m[]"></div><div class="col-sm-3 mt-2"><label>{{ __('db.Link') }}</label><br><input class="form-control" type="text" name="link[]"></div><div class="col-sm-1 mt-2"><label>{{ __('Remove') }}</label><a class="btn btn-danger btn-sm remove"><i class="ti ti-x"></i></a></div></div>');
    })

    $(document).on('click', '.remove', function(){
        $(this).closest('.row').remove();
    })

</script>
@endpush
