<div class="tab-pane fade show" id="list-order-sync-settings" role="tabpanel"
    aria-labelledby="list-order-sync-settings-list">
    <form action="{{ route('woocommerce.store') }}" method="POST">
        @csrf
        <input type="hidden" name="order_sync_settings" value="1">
        <div class="row">
            <div class="form-group col-md-4">
                <label><b>{{__('db.Default Customer Group')}}</b></label>
                @if(!empty($customer_group))
                <select name="customer_group_id" class="form-control">
                    @foreach($customer_group as $id => $name)
                        <option value="{{ $id }}" @selected(isset($woocommerce_setting->customer_group_id) && (string) $woocommerce_setting->customer_group_id === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="form-group col-md-4">
                <label><b>{{__('db.Default Warehouse')}}</b></label>
                @if(!empty($warehouse))
                <select name="warehouse_id" class="form-control">
                    @foreach($warehouse as $id => $name)
                        <option value="{{ $id }}" @selected(isset($woocommerce_setting->warehouse_id) && (string) $woocommerce_setting->warehouse_id === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div class="form-group col-md-4">
                <label><b>{{__('db.Default Biller')}}</b></label>
                @if(!empty($biller))
                <select name="biller_id" class="form-control">
                    @foreach($biller as $id => $name)
                        <option value="{{ $id }}" @selected(isset($woocommerce_setting->biller_id) && (string) $woocommerce_setting->biller_id === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @endif
            </div>
        </div>
        <div class="row">
            <table class="table">
                <tr>
                    <th>{{__('db.WooCommerce Order Status')}}</th>
                    <th>{{__('db.Equivalent POS Sell Status')}}</th>
                </tr>
                <tr>
                    <td>Pending</td>
                    <td>
                        <select name="sell_status[]">
                            <option value="">--Select--</option>
                            <option value="1" @isset($woocommerce_setting->order_status_pending) {{ $woocommerce_setting->order_status_pending == 1 ? 'selected' : '' }} @endisset>Completed</option>
                            <option value="2" @isset($woocommerce_setting->order_status_pending) {{ $woocommerce_setting->order_status_pending == 2 ? 'selected' : '' }} @endisset>Pending</option>
                            <option value="3" @isset($woocommerce_setting->order_status_pending) {{ $woocommerce_setting->order_status_pending == 3 ? 'selected' : '' }} @endisset>Draft</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Processing</td>
                    <td>
                        <select name="sell_status[]">
                            <option value="">--Select--</option>
                            <option value="1" @isset($woocommerce_setting->order_status_processing) {{ $woocommerce_setting->order_status_processing == 1 ? 'selected' : '' }} @endisset>Completed</option>
                            <option value="2" @isset($woocommerce_setting->order_status_processing) {{ $woocommerce_setting->order_status_processing == 2 ? 'selected' : '' }} @endisset>Pending</option>
                            <option value="3" @isset($woocommerce_setting->order_status_processing) {{ $woocommerce_setting->order_status_processing == 3 ? 'selected' : '' }} @endisset>Draft</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>On hold</td>
                    <td>
                        <select name="sell_status[]">
                            <option value="">--Select--</option>
                            <option value="1" @isset($woocommerce_setting->order_status_on_hold) {{ $woocommerce_setting->order_status_on_hold == 1 ? 'selected' : '' }} @endisset>Completed</option>
                            <option value="2" @isset($woocommerce_setting->order_status_on_hold) {{ $woocommerce_setting->order_status_on_hold == 2 ? 'selected' : '' }} @endisset>Pending</option>
                            <option value="3" @isset($woocommerce_setting->order_status_on_hold) {{ $woocommerce_setting->order_status_on_hold == 3 ? 'selected' : '' }} @endisset>Draft</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Completed</td>
                    <td>
                        <select name="sell_status[]">
                            <option value="">--Select--</option>
                            <option value="1" @isset($woocommerce_setting->order_status_completed) {{ $woocommerce_setting->order_status_completed == 1 ? 'selected' : '' }} @endisset>Completed</option>
                            <option value="2" @isset($woocommerce_setting->order_status_completed) {{ $woocommerce_setting->order_status_completed == 2 ? 'selected' : '' }} @endisset>Pending</option>
                            <option value="3" @isset($woocommerce_setting->order_status_completed) {{ $woocommerce_setting->order_status_completed == 3 ? 'selected' : '' }} @endisset>Draft</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Draft</td>
                    <td>
                        <select name="sell_status[]">
                            <option value="">--Select--</option>
                            <option value="1" @isset($woocommerce_setting->order_status_draft) {{ $woocommerce_setting->order_status_draft == 1 ? 'selected' : '' }} @endisset>Completed</option>
                            <option value="2" @isset($woocommerce_setting->order_status_draft) {{ $woocommerce_setting->order_status_draft == 2 ? 'selected' : '' }} @endisset>Pending</option>
                            <option value="3" @isset($woocommerce_setting->order_status_draft) {{ $woocommerce_setting->order_status_draft == 3 ? 'selected' : '' }} @endisset>Draft</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <button type="submit" class="btn btn-primary">{{__('db.Submit')}}</button>
    </form>
</div>
