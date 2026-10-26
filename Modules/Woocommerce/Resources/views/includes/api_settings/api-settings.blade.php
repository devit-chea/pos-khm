<div class="tab-pane fade" id="list-api-settings" role="tabpanel"
                                aria-labelledby="list-api-settings-list">

        <form action="{{route('woocommerce.store')}}" method="POST">
            @csrf
            <input type="hidden" name="api_settings" value="1">
            <div class="form-check mb-3">
                <input type="hidden" name="sync_enabled" value="0">
                <input class="form-check-input" type="checkbox" name="sync_enabled" value="1" id="woocommerceSyncEnabled" {{ old('sync_enabled', $woocommerce_setting->sync_enabled ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="woocommerceSyncEnabled">{{ __('db.Enable WooCommerce synchronization') }}</label>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="inputEmail4"><b>{{__('db.WooCommerce App URL')}}</b></label>
                    <input type="text" @if($woocommerce_setting) value="{{$woocommerce_setting->woocomerce_app_url}}" @endif name="woocomerce_app_url" class="form-control" id="inputEmail4"
                        placeholder="WooCommerce App URL">
                </div>
                <div class="form-group col-md-4">
                    <label for="inputPassword4"><b>{{__('db.WooCommerce Consumer Key')}}</b></label>
                    <input type="password" name="woocomerce_consumer_key" class="form-control" id="woocommerceConsumerKey"
                        autocomplete="new-password" placeholder="{{ $woocommerce_setting && $woocommerce_setting->woocomerce_consumer_key ? 'Saved — enter a new key to replace it' : 'WooCommerce Consumer Key' }}">
                </div>
                <div class="form-group col-md-4">
                    <label for="inputPassword4"><b>{{__('db.WooCommerce Consumer Secret')}}</b></label>
                    <input type="password" name="woocomerce_consumer_secret" class="form-control" id="woocommerceConsumerSecret"
                        autocomplete="new-password" placeholder="{{ $woocommerce_setting && $woocommerce_setting->woocomerce_consumer_secret ? 'Saved — enter a new secret to replace it' : 'WooCommerce Consumer Secret' }}">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{__('db.Submit')}}</button>
        </form>
    </div>
