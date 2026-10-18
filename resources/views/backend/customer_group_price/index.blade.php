@extends('backend.layout.main')
@section('content')

<x-error-message key="not_permitted" />

@push('css')
<style>
    #group-price-table td { vertical-align: middle; }
    #group-price-table .group-price-input { max-width: 140px; }
    #group-price-table tr.row-changed { background: rgba(255, 193, 7, .12); }
    #group-price-table .price-diff { font-size: 12px; white-space: nowrap; }
    #group-price-table .below-cost { color: #d63031; font-size: 12px; white-space: nowrap; }
    .group-price-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
    .group-price-toolbar .form-group { margin-bottom: 0; }
    .group-price-savebar { position: sticky; bottom: 0; z-index: 5; background: #fff; border-top: 1px solid #e4e6fc; padding: 10px 15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }

    /* Dialogs: save result and discard confirmation */
    .gp-dialog { background-color: rgba(23, 32, 42, .45); }
    .gp-dialog .modal-dialog { max-width: 420px; margin-left: auto; margin-right: auto; padding: 0 16px; }
    .gp-dialog .modal-content { border-radius: 16px; box-shadow: 0 24px 64px rgba(23, 32, 42, .25); overflow: hidden; }
    .gp-dialog .modal-body { padding: 36px 32px 28px; text-align: center; }
    .gp-dialog .gp-result-icon { width: 72px; height: 72px; margin: 0 auto 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; }
    .gp-dialog.is-success .gp-result-icon { background: rgba(40, 167, 69, .12); color: #28a745; box-shadow: 0 0 0 8px rgba(40, 167, 69, .06); }
    .gp-dialog.is-error .gp-result-icon { background: rgba(214, 48, 49, .12); color: #d63031; box-shadow: 0 0 0 8px rgba(214, 48, 49, .06); }
    .gp-dialog.show .gp-result-icon { animation: gp-pop .35s ease-out; }
    @keyframes gp-pop { 0% { transform: scale(.6); opacity: 0; } 70% { transform: scale(1.08); opacity: 1; } 100% { transform: scale(1); } }
    @media (prefers-reduced-motion: reduce) { .gp-dialog.show .gp-result-icon { animation: none; } }
    .gp-dialog.is-warning .gp-result-icon { background: rgba(243, 156, 18, .14); color: #e67e22; box-shadow: 0 0 0 8px rgba(243, 156, 18, .07); }
    .gp-dialog .gp-result-count { display: inline-block; font-weight: 600; padding: 6px 14px; border-radius: 999px; background: rgba(243, 156, 18, .14); color: #b9670f; margin-bottom: 14px; }
    .gp-dialog .gp-dialog-actions { display: flex; gap: 10px; }
    .gp-dialog .gp-dialog-actions .btn { flex: 1; }
    .gp-dialog .gp-result-title { font-size: 20px; font-weight: 600; margin-bottom: 4px; }
    .gp-dialog .gp-result-group { color: #8a8d93; margin-bottom: 22px; }
    .gp-dialog .gp-result-group strong { color: inherit; font-weight: 600; }
    .gp-dialog .gp-result-stats { display: flex; gap: 12px; margin-bottom: 18px; }
    .gp-dialog .gp-result-stats > div { flex: 1; padding: 14px 8px; border-radius: 12px; background: rgba(124, 92, 196, .07); }
    .gp-dialog .gp-stat-value { display: block; font-size: 26px; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .gp-dialog .gp-stat-label { display: block; font-size: 12px; color: #8a8d93; margin-top: 4px; }
    .gp-dialog .gp-result-note { font-size: 13px; color: #8a8d93; margin-bottom: 24px; }
    .gp-dialog .gp-result-errors { text-align: left; font-size: 13px; color: #d63031; background: rgba(214, 48, 49, .06); border-radius: 10px; padding: 10px 14px 10px 30px; margin-bottom: 20px; }
    .gp-dialog .gp-result-errors:empty { display: none; }
    .gp-dialog .btn { border-radius: 10px; padding: 10px; font-weight: 600; }
    .dark-mode .gp-dialog .gp-result-stats > div { background: #343d55; }
</style>
@endpush

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4>{{__('db.Group Price List')}}</h4>
                <p class="italic mb-0"><small>{{__('db.Set a fixed sale price for many products at once for one customer group. Leave a price empty to use the group percentage instead.')}}</small></p>
            </div>
            <div class="card-body">
                @if(!count($lims_customer_group_list))
                    <p class="mb-0">{{__('db.No active customer group. Add one in Settings, Customer Group.')}}</p>
                @else
                <div class="group-price-toolbar">
                    <div class="form-group">
                        <label for="gp-group"><strong>{{__('db.Customer Group')}}</strong></label>
                        <select id="gp-group" class="form-control">
                            @foreach($lims_customer_group_list as $customer_group)
                                <option value="{{$customer_group->id}}" data-percentage="{{$customer_group->percentage}}">{{$customer_group->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gp-search">{{__('db.Search')}}</label>
                        <input type="text" id="gp-search" class="form-control" placeholder="{{__('db.Product name or code')}}">
                    </div>
                    <div class="form-group">
                        <label for="gp-category">{{__('db.category')}}</label>
                        <select id="gp-category" class="form-control">
                            <option value="">{{__('db.All')}}</option>
                            @foreach($lims_category_list as $category)
                                <option value="{{$category->id}}">{{$category->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gp-brand">{{__('db.Brand')}}</label>
                        <select id="gp-brand" class="form-control">
                            <option value="">{{__('db.All')}}</option>
                            @foreach($lims_brand_list as $brand)
                                <option value="{{$brand->id}}">{{$brand->title}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gp-only-priced">{{__('db.Show')}}</label>
                        <select id="gp-only-priced" class="form-control">
                            <option value="0">{{__('db.All products')}}</option>
                            <option value="1">{{__('db.With group price')}}</option>
                            <option value="2">{{__('db.Without group price')}}</option>
                        </select>
                    </div>
                </div>
                <p class="mt-3 mb-0" id="gp-summary"></p>
                @endif
            </div>
        </div>

        @if(count($lims_customer_group_list))
        <div class="card">
            <div class="card-body">
                <div class="group-price-toolbar mb-3">
                    <span id="gp-selected-count" class="mr-2">{{__('db.Selected')}}: 0</span>
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">{{__('db.Set selected to')}}</span></div>
                            <input type="number" id="gp-bulk-percent" class="form-control" style="max-width: 90px" step="any" min="0" placeholder="90">
                            <div class="input-group-append"><span class="input-group-text">{{__('db.% of regular price')}}</span>
                                <button type="button" id="gp-bulk-apply" class="btn btn-primary">{{__('db.Apply')}}</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="gp-bulk-clear" class="btn btn-outline-danger">{{__('db.Clear group price for selected')}}</button>
                </div>

                <div class="table-responsive">
                    <table id="group-price-table" class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 30px"><input type="checkbox" id="gp-select-all" aria-label="{{__('db.Select all')}}"></th>
                                <th>{{__('db.product')}}</th>
                                <th>{{__('db.category')}}</th>
                                <th>{{__('db.Brand')}}</th>
                                <th class="text-right">{{__('db.Cost')}}</th>
                                <th class="text-right">{{__('db.Price')}}</th>
                                <th>{{__('db.Customer Group Price')}}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px">
                    <span id="gp-page-info"></span>
                    <div>
                        <button type="button" id="gp-prev" class="btn btn-light btn-sm">&laquo; {{__('db.Previous')}}</button>
                        <button type="button" id="gp-next" class="btn btn-light btn-sm">{{__('db.Next')}} &raquo;</button>
                    </div>
                </div>
            </div>
            <div class="group-price-savebar">
                <span id="gp-change-count">{{__('db.No unsaved changes')}}</span>
                <div>
                    <button type="button" id="gp-discard" class="btn btn-light" disabled>{{__('db.Discard changes')}}</button>
                    <button type="button" id="gp-save" class="btn btn-success" disabled>{{__('db.Save changes')}}</button>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

<div class="modal fade gp-dialog" id="gpResultModal" tabindex="-1" role="dialog" aria-labelledby="gpResultTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="gp-result-icon" aria-hidden="true"><i class="fa fa-check"></i></div>
                <h5 class="gp-result-title" id="gpResultTitle"></h5>
                <p class="gp-result-group"></p>
                <div class="gp-result-success">
                    <div class="gp-result-stats">
                        <div><span class="gp-stat-value" id="gp-stat-saved">0</span><span class="gp-stat-label">{{__('db.Prices set')}}</span></div>
                        <div><span class="gp-stat-value" id="gp-stat-removed">0</span><span class="gp-stat-label">{{__('db.Prices removed')}}</span></div>
                    </div>
                    <p class="gp-result-note">{{__('db.New prices are used for products added to a sale from now on.')}}</p>
                </div>
                <ul class="gp-result-errors"></ul>
                <button type="button" class="btn btn-block" data-dismiss="modal">{{__('db.OK')}}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade gp-dialog is-warning" id="gpDiscardModal" tabindex="-1" role="dialog" aria-labelledby="gpDiscardTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="gp-result-icon" aria-hidden="true"><i class="fa fa-exclamation-triangle"></i></div>
                <h5 class="gp-result-title" id="gpDiscardTitle">{{__('db.Discard unsaved changes?')}}</h5>
                <span class="gp-result-count" id="gp-discard-count"></span>
                <p class="gp-result-note">{{__('db.Changes you have not saved will be lost.')}}</p>
                <div class="gp-dialog-actions">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{__('db.Keep editing')}}</button>
                    <button type="button" class="btn btn-danger" id="gp-discard-confirm">{{__('db.Discard changes')}}</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#product").siblings('a').attr('aria-expanded', 'true');
    $("ul#product").addClass("show");
    $("ul#product #group-price-menu").addClass("active");

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var gp_decimal = {{ (int) config('decimal') }};
    var gp_text = {
        selected: @json(__('db.Selected')),
        unsaved: @json(__('db.unsaved changes')),
        no_unsaved: @json(__('db.No unsaved changes')),
        page: @json(__('db.Page')),
        of: @json(__('db.of')),
        products: @json(__('db.products')),
        have_price: @json(__('db.products have a fixed price for this group. Others use the group percentage')),
        below_cost: @json(__('db.Below cost')),
        uses_percent: @json(__('db.Uses group percentage')),
        select_first: @json(__('db.Select products first')),
        enter_percent: @json(__('db.Enter a percentage, for example 90')),
        saved: @json(__('db.Group prices saved')),
        save_failed: @json(__('db.Could not save. Check the prices and try again.')),
        save_failed_title: @json(__('db.Could not save')),
        customer_group: @json(__('db.Customer Group')),
        no_products: @json(__('db.No products found'))
    };

    var gp_page = 1;
    var gp_last_page = 1;
    var gp_rows = {};       // product_id -> row data on the current page
    var gp_changes = {};    // product_id -> new price ('' removes the group price); kept across pages
    var gp_selected = {};   // product_id -> true
    var gp_current_group = $('#gp-group').val();

    function fmt(n) {
        return parseFloat(n).toFixed(gp_decimal);
    }
    function changeCount() {
        return Object.keys(gp_changes).length;
    }
    // Asks before dropping unsaved edits; closing the dialog any other way keeps them
    var gp_discard_yes = null, gp_discard_no = null, gp_discard_ok = false;
    function confirmDiscard(onYes, onNo) {
        if (changeCount() == 0) {
            onYes();
            return;
        }
        gp_discard_yes = onYes;
        gp_discard_no = onNo || null;
        gp_discard_ok = false;
        $('#gp-discard-count').text(changeCount() + ' ' + gp_text.unsaved);
        $('#gpDiscardModal').modal('show');
    }

    function updateBars() {
        var n = changeCount();
        $('#gp-change-count').text(n ? n + ' ' + gp_text.unsaved : gp_text.no_unsaved);
        $('#gp-save, #gp-discard').prop('disabled', n == 0);
        $('#gp-selected-count').text(gp_text.selected + ': ' + Object.keys(gp_selected).length);
    }

    // Difference from the regular price and a below-cost warning for one row
    function renderRowInfo(tr, row) {
        var raw = (row.id in gp_changes) ? gp_changes[row.id] : (row.group_price === null ? '' : row.group_price);
        var info = tr.find('.row-info').empty();
        tr.toggleClass('row-changed', row.id in gp_changes);
        if (raw === '' || raw === null) {
            var pct = parseFloat($('#gp-group option:selected').data('percentage')) || 0;
            info.append($('<span class="price-diff text-muted">').text(gp_text.uses_percent + ' (' + (pct > 0 ? '+' : '') + pct + '%)'));
            return;
        }
        var price = parseFloat(raw);
        if (parseFloat(row.price) > 0) {
            var diff = (price - row.price) / row.price * 100;
            info.append($('<span class="price-diff">').text((diff > 0 ? '+' : '') + diff.toFixed(1) + '%'));
        }
        if (row.cost !== null && price < parseFloat(row.cost))
            info.append($('<div class="below-cost">').text(gp_text.below_cost));
    }

    function renderTable(data) {
        var tbody = $('#group-price-table tbody').empty();
        gp_rows = {};
        if (!data.rows.length) {
            tbody.append($('<tr>').append($('<td colspan="8" class="text-center text-muted">').text(gp_text.no_products)));
        }
        $.each(data.rows, function(i, row) {
            gp_rows[row.id] = row;
            var value = (row.id in gp_changes) ? gp_changes[row.id] : (row.group_price === null ? '' : row.group_price);
            var tr = $('<tr>').attr('data-id', row.id);
            tr.append($('<td>').append($('<input type="checkbox" class="gp-select">').prop('checked', !!gp_selected[row.id])));
            tr.append($('<td>').append($('<strong>').text(row.name)).append('<br>').append($('<small class="text-muted">').text(row.code)));
            tr.append($('<td>').text(row.category || '-'));
            tr.append($('<td>').text(row.brand || '-'));
            tr.append($('<td class="text-right">').text(row.cost !== null ? fmt(row.cost) : '-'));
            tr.append($('<td class="text-right">').text(fmt(row.price)));
            tr.append($('<td>').append($('<input type="number" step="any" min="0" class="form-control form-control-sm group-price-input">').val(value)));
            tr.append($('<td class="row-info">'));
            tbody.append(tr);
            renderRowInfo(tr, row);
        });
        gp_page = data.page;
        gp_last_page = data.last_page;
        $('#gp-page-info').text(gp_text.page + ' ' + data.page + ' ' + gp_text.of + ' ' + data.last_page + ' · ' + data.total + ' ' + gp_text.products);
        $('#gp-prev').prop('disabled', gp_page <= 1);
        $('#gp-next').prop('disabled', gp_page >= gp_last_page);
        $('#gp-summary').text(data.priced_count + ' ' + gp_text.have_price + ' (' + $('#gp-group option:selected').data('percentage') + '%).');
        $('#gp-select-all').prop('checked', data.rows.length > 0 && data.rows.every(function(r) { return gp_selected[r.id]; }));
        updateBars();
    }

    function loadPage(page) {
        $.get('{{ route("customer_group_price.data") }}', {
            customer_group_id: $('#gp-group').val(),
            search: $('#gp-search').val(),
            category_id: $('#gp-category').val(),
            brand_id: $('#gp-brand').val(),
            only_priced: $('#gp-only-priced').val(),
            page: page
        }, renderTable);
    }

    // Changing the group or a filter reloads the list; changing the group drops unsaved edits
    $('#gp-group').on('change', function() {
        var select = $(this);
        confirmDiscard(function() {
            gp_current_group = select.val();
            gp_changes = {};
            gp_selected = {};
            loadPage(1);
        }, function() {
            select.val(gp_current_group);
        });
    });
    $('#gp-category, #gp-brand, #gp-only-priced').on('change', function() { loadPage(1); });
    var gp_search_timer;
    $('#gp-search').on('input', function() {
        clearTimeout(gp_search_timer);
        gp_search_timer = setTimeout(function() { loadPage(1); }, 300);
    });
    $('#gp-prev').on('click', function() { if (gp_page > 1) loadPage(gp_page - 1); });
    $('#gp-next').on('click', function() { if (gp_page < gp_last_page) loadPage(gp_page + 1); });

    // Track edits; a value equal to the saved one is not a change
    function setPrice(id, value) {
        var row = gp_rows[id];
        var saved = row.group_price === null ? '' : String(parseFloat(row.group_price));
        var norm = value === '' ? '' : String(parseFloat(value));
        if (norm === saved)
            delete gp_changes[id];
        else
            gp_changes[id] = value === '' ? '' : value;
    }
    $('#group-price-table').on('input', '.group-price-input', function() {
        var tr = $(this).closest('tr');
        var id = tr.data('id');
        setPrice(id, $(this).val().trim());
        renderRowInfo(tr, gp_rows[id]);
        updateBars();
    });

    $('#group-price-table').on('change', '.gp-select', function() {
        var id = $(this).closest('tr').data('id');
        if (this.checked) gp_selected[id] = true; else delete gp_selected[id];
        updateBars();
    });
    $('#gp-select-all').on('change', function() {
        var checked = this.checked;
        $('#group-price-table .gp-select').each(function() {
            $(this).prop('checked', checked).trigger('change');
        });
    });

    // Bulk actions apply to the selected products shown on this page
    function selectedOnPage() {
        return Object.keys(gp_rows).filter(function(id) { return gp_selected[id]; });
    }
    function bulkSet(valueFor) {
        var ids = selectedOnPage();
        if (!ids.length) {
            alert(gp_text.select_first);
            return;
        }
        $.each(ids, function(i, id) {
            var value = valueFor(gp_rows[id]);
            var tr = $('#group-price-table tr[data-id="' + id + '"]');
            tr.find('.group-price-input').val(value);
            setPrice(id, value);
            renderRowInfo(tr, gp_rows[id]);
        });
        updateBars();
    }
    $('#gp-bulk-apply').on('click', function() {
        var pct = parseFloat($('#gp-bulk-percent').val());
        if (isNaN(pct) || pct < 0) {
            alert(gp_text.enter_percent);
            return;
        }
        bulkSet(function(row) { return fmt(row.price * pct / 100); });
    });
    $('#gp-bulk-clear').on('click', function() {
        bulkSet(function() { return ''; });
    });

    $('#gp-discard').on('click', function() {
        confirmDiscard(function() {
            gp_changes = {};
            loadPage(gp_page);
        });
    });

    $('#gp-discard-confirm').on('click', function() {
        gp_discard_ok = true;
        $('#gpDiscardModal').modal('hide');
    });
    $('#gpDiscardModal').on('shown.bs.modal', function() {
        $(this).find('[data-dismiss="modal"]').trigger('focus');
    }).on('hidden.bs.modal', function() {
        var cb = gp_discard_ok ? gp_discard_yes : gp_discard_no;
        gp_discard_yes = gp_discard_no = null;
        if (cb) cb();
    });

    // Result dialog after saving
    function showResult(ok, res) {
        var modal = $('#gpResultModal');
        modal.toggleClass('is-success', ok).toggleClass('is-error', !ok);
        modal.find('.gp-result-icon i').attr('class', ok ? 'fa fa-check' : 'fa fa-exclamation');
        modal.find('.gp-result-title').text(ok ? gp_text.saved : gp_text.save_failed_title);
        modal.find('.gp-result-group').empty()
            .append(document.createTextNode(gp_text.customer_group + ': '))
            .append($('<strong>').text($('#gp-group option:selected').text()));
        modal.find('.gp-result-success').toggle(ok);
        modal.find('.btn').toggleClass('btn-success', ok).toggleClass('btn-danger', !ok);
        var errors = modal.find('.gp-result-errors').empty();
        if (ok) {
            $('#gp-stat-saved').text(res.saved || 0);
            $('#gp-stat-removed').text(res.removed || 0);
        }
        else {
            var messages = [];
            // Show "prices.69" as the product name when that product is on this page
            $.each(res.errors || {}, function(field, list) {
                var id = field.split('.')[1];
                var label = id && gp_rows[id] ? gp_rows[id].name : null;
                $.each(list, function(i, m) { messages.push(label ? m.split(field).join(label) : m); });
            });
            if (!messages.length)
                messages.push(res.error || res.message || gp_text.save_failed);
            $.each(messages.slice(0, 3), function(i, m) { errors.append($('<li>').text(m)); });
        }
        modal.modal('show');
    }
    $('#gpResultModal').on('shown.bs.modal', function() {
        $(this).find('.btn').trigger('focus');
    });

    $('#gp-save').on('click', function() {
        var btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: '{{ route("customer_group_price.save") }}',
            data: { customer_group_id: $('#gp-group').val(), prices: gp_changes },
            success: function(res) {
                gp_changes = {};
                loadPage(gp_page);
                showResult(true, res);
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                showResult(false, xhr.responseJSON || {});
            }
        });
    });

    window.addEventListener('beforeunload', function(e) {
        if (changeCount()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    if ($('#gp-group').length)
        loadPage(1);
</script>
@endpush
