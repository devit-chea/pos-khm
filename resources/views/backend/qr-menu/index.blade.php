@extends('backend.layout.main') @section('content')

<div class="container-fluid mt-5">

    <!-- <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" href="#qr-code" role="tab"
                data-toggle="tab">{{ __('db.QR Code') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#settings" role="tab"
                data-toggle="tab">{{ __('db.catalogue settings') }}</a>
        </li>
    </ul> -->

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade show active" id="qr-code">
            
            @include('backend.qr-menu.includes')

        </div>
        <div role="tabpanel" class="tab-pane fade" id="settings">
            <div class="card p-4 mb-4">
                <div class="card p-4 mb-4">
                    <label>{{ __('db.Out of Stock Products') }}</label>
                    <div>
                        <label><input type="radio" name="stock" value="show"> {{ __('db.Show') }}</label>
                        <label class="ml-3"><input type="radio" name="stock" value="hide" checked> {{ __('db.Hide') }}</label>
                    </div>

                    <button class="btn btn-success mt-3" onclick="saveSettings()">{{ __('db.Save') }}</button>
                </div>

                <!-- WHATSAPP -->
                <div class="card p-4">

                    <div class="form-check mb-2">
                        <input type="checkbox" id="enable_whatsapp" checked>
                        <label>{{ __('db.Enable WhatsApp Ordering') }}</label>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.WhatsApp Number') }}</label>
                        <input type="text" id="whatsapp_number" class="form-control" placeholder="e.g. +1234567890">
                    </div>

                    <button class="btn btn-success mt-2" onclick="saveWhatsapp()">{{ __('db.Save') }}</button>
                </div>
            </div>
        </div>
    </div>

</div>


@endsection